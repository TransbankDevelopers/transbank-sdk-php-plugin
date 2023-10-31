<?php

namespace Transbank\Plugin\Service;

use Exception;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\IRepository\ITransactionRepository;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Exceptions\GetTransactionDbException;
use Transbank\Plugin\Exceptions\NotFoundTransactionDbException;
use Transbank\Plugin\Exceptions\CreateTransactionDbException;
use Transbank\Plugin\Exceptions\UpdateTransactionDbException;
use Transbank\Plugin\Exceptions\Webpay\AlreadyApprovedWebpayException;
use Transbank\Plugin\Exceptions\Webpay\InvalidStatusWebpayException;
use Transbank\Plugin\Exceptions\Webpay\OrderAlreadyPaidException;
use Transbank\Plugin\Helpers\DateUtils;
use Transbank\Plugin\Model\DetailAdmin;
use Transbank\Plugin\Model\DetailClient;
use Transbank\Plugin\Model\TransbankTransactionDto;
use Transbank\Plugin\Model\ExecutionErrorLogDto;
use Transbank\Plugin\Model\TransbankTransactionDetail;
use Transbank\Plugin\IRepository\IConfigRepository;

/**
 * Esta clase tiene el propósito de encapsular la lógica de negocio
 * referente a la persistencia en la tabla de transacciones
 *
 * @version 1.0
 */
final class TransactionService extends BaseTableService {

    private $transactionRepository;
    private $executionErrorLogService;
    private $configRepository;

    public function __construct(ILogger $logger, IConfigRepository $configRepository, ITransactionRepository $transactionRepository, ExecutionErrorLogService $executionErrorLogService) {
        parent::__construct($logger, $transactionRepository);
        $this->configRepository = $configRepository;
        $this->executionErrorLogService = $executionErrorLogService;
        $this->transactionRepository = $transactionRepository;
    }

    protected function createTableDto($data){
        return new TransbankTransactionDto($data);
    }

    /**
     * Este método retorna una transacción única por buyOrder.
     *
     * @param string $buyOrder
     * @throws GetTransactionDbException Cuando algo falla al ejecutar la consulta que obtiene la transacción.
     * @throws NotFoundTransactionDbException Cuando no se encuentra la transacción.
     * @return TransbankTransactionDto
     */
    public function getByBuyOrder($buyOrder){
        $tx = null;
        try {
            $tx = $this->transactionRepository->getByBuyOrder($buyOrder);
        } catch (Exception $e) {
            $errorMessage = "Ocurrió un error al tratar de obtener la transacción, BUY_ORDER: {$buyOrder}, ERROR: {$e->getMessage()}";
            throw new GetTransactionDbException($errorMessage);
        }
        if (!$tx) {
            $errorMessage = "No se encontró una transacción, BUY_ORDER: {$buyOrder}";
            throw new NotFoundTransactionDbException($errorMessage);
        }
        return $tx;
    }

    /**
     * Este método retorna una transacción única por Token.
     *
     * @param string $token
     * @throws GetTransactionDbException Cuando algo falla al ejecutar la consulta que obtiene la transacción.
     * @throws NotFoundTransactionDbException Cuando no se encuentra la transacción.
     * @return TransbankTransactionDto
     */
    public function getByToken($token){
        $tx = null;
        try {
            $tx = $this->transactionRepository->getByToken($token);
        } catch (Exception $e) {
            $errorMessage = "Ocurrió un error al tratar de obtener la transacción, TOKEN: {$token}, ERROR: {$e->getMessage()}";
            throw new GetTransactionDbException($errorMessage);
        }
        if (!$tx) {
            $errorMessage = "No se encontró una transacción, TOKEN: {$token}";
            throw new NotFoundTransactionDbException($errorMessage);
        }
        return $tx;
    }

    /**
     * Este método busca una transacción única con el campo 'transbankStatus' = 'AUTHORIZED' por OrderId.
     *
     * @param string $orderId
     * @throws GetTransactionDbException Cuando algo falla al ejecutar la consulta que obtiene la transacción.
     * @return TransbankTransactionDto
     */
    public function getTbkAuthorizedByOrderId($orderId){
        try {
            return $this->transactionRepository->getTbkAuthorizedByOrderId($orderId);
        } catch (Exception $e) {
            $errorMessage = "Ocurrió un error al tratar de obtener una transacción con el campo 'transbankStatus' = 'AUTHORIZED' (autorizada en Transbank), ORDER_ID: {$orderId}, ERROR: {$e->getMessage()}";
            throw new GetTransactionDbException($errorMessage);
        }
    }

    /**
     * Este método busca una transacción única con el campo 'transbankStatus' = 'AUTHORIZED' por OrderId para ejecutar un refund sobre ella
     * En caso de no encontrar la transacción retorna una excepción
     *
     * @param string $token
     * @throws NotFoundTransactionDbException Cuando no se encuentra la transacción.
     * @return TransbankTransactionDto
     */
    public function getTbkAuthorizedByOrderIdForRefund($orderId, ExecutionErrorLogDto $eel){
        $tx = $this->getTbkAuthorizedByOrderId($orderId);
        if (!$tx) {
            $errorMessage = "No se encontró una transacción con el campo 'transbankStatus' = 'AUTHORIZED' (autorizada en Transbank) habilitada para el refund, ORDER_ID: {$orderId}";
            $eel->setError('NotFoundTransactionDbException');
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new NotFoundTransactionDbException($errorMessage);
        }
        return $tx;
    }

    /**
     * Este método busca una transacción única con el campo 'transbankStatus' = 'AUTHORIZED' por buyOrder para ejecutar un refund sobre ella
     * En caso de no encontrar la transacción retorna una excepción
     *
     * @param string $buyOrder
     * @throws NotFoundTransactionDbException Cuando no se encuentra la transacción.
     * @return TransbankTransactionDto
     */
    public function getTbkAuthorizedByBuyOrderForRefund($buyOrder, ExecutionErrorLogDto $eel){
        $tx = $this->getByBuyOrder($buyOrder);
        if ($tx->getTransbankStatus() !== TbkConstants::TRANSACTION_TBK_STATUS_AUTHORIZED) {
            $errorMessage = "No se encontró una transacción con el campo 'transbankStatus' = 'AUTHORIZED' (autorizada en Transbank) habilitada para el refund, BUY_ORDER: {$buyOrder}";
            $eel->setError('NotFoundTransactionDbException');
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new NotFoundTransactionDbException($errorMessage);
        }
        return $tx;
    }

    /**
     * Este método actualiza la transacción al estado 'failed' por un error producido durante el commit Webpay
     * Valida que la transacción se encuentre en estado 'initialized', porque podria existir otro proceso paralelo tratando de hacer lo mismo
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     * @return TransbankTransactionDto
     */
    public function updateInitializedWithErrorForCommit(TransbankTransactionDto $data, ExecutionErrorLogDto $eel)
    {
        return $this->updateInitializedWithErrorBase($data, $eel);
    }

    /**
     * Este método actualiza la transacción al estado 'aborted_by_user' por la cancelación del usuario producida durante el commit Webpay
     * Valida que la transacción se encuentre en estado 'initialized', porque podria existir otro proceso paralelo tratando de hacer lo mismo
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     * @return TransbankTransactionDto
     */
    public function updateInitializedWithAbortedByUserForCommit(TransbankTransactionDto $data, ExecutionErrorLogDto $eel)
    {
        return $this->updateInitializedWithErrorBase($data, $eel, TbkConstants::TRANSACTION_STATUS_ABORTED_BY_USER);
    }

    /**
     * Este método actualiza la transacción al estado 'failed' por un error producido durante la autorización Oneclick
     * Valida que la transacción se encuentre en estado 'failed', porque podria existir otro proceso paralelo tratando de hacer lo mismo
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     * @return TransbankTransactionDto
     */
    public function updateInitializedWithErrorForAuthorize(TransbankTransactionDto $data, ExecutionErrorLogDto $eel)
    {
        return $this->updateInitializedWithErrorBase($data, $eel);
    }

    private function updateInitializedWithErrorBase(TransbankTransactionDto $data, ExecutionErrorLogDto $eel, $status = TbkConstants::TRANSACTION_STATUS_FAILED)
    {
        if ($data->getStatus() !== TbkConstants::TRANSACTION_STATUS_INITIALIZED) {
            $errorMessage = "Se quiso guardar la excepción: '{$eel->getError()}' en la tabla y el registro no tiene 'status'='initialized'";
            $eel->setError('GenericException');
            $eel->setOriginalError(null);
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            return null;
        }
        $data->setError($eel->getError());
        $data->setOriginalError($eel->getOriginalError());
        $data->setCustomError($eel->getCustomError());
        $data->setStatus($status);
        $data->setUpdatedAt(DateUtils::getNow());
        $this->transactionRepository->update($data);
        $this->executionErrorLogService->create($eel);
        return $data;
    }

    /**
     * Este método actualiza los campos de error de la transacción y en caso de encontrarse en el estado 'initialized' cambia a 'failed'
     * Valida que la transacción se encuentre en estado 'initialized' o en estado 'approved', porque podria existir otro proceso paralelo tratando de hacer lo mismo
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     * @return null|TransbankTransactionDto
     */
    public function updateWithErrorForCommitEcommerce(TransbankTransactionDto $data, ExecutionErrorLogDto $eel)
    {
        if (is_null($data)){
            $this->executionErrorLogService->create($eel);
            return null;
        }
        if ($data->getStatus() !== TbkConstants::TRANSACTION_STATUS_INITIALIZED && $data->getStatus() !== TbkConstants::TRANSACTION_STATUS_APPROVED) {
            $errorMessage = "Se quiso guardar una excepción (lógica ecommerce) en la tabla y el registro no tiene 'status'='initialized' o  'status'='approved'";
            $eel->setError('GenericException');
            $eel->setOriginalError(null);
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            return null;
        }
        $data->setError($eel->getError());
        $data->setOriginalError($eel->getOriginalError());
        $data->setCustomError($eel->getCustomError());
        if ($data->getStatus() === TbkConstants::TRANSACTION_STATUS_INITIALIZED && $data->getStatus()){
            $data->setStatus(TbkConstants::TRANSACTION_STATUS_FAILED);
        }
        $data->setUpdatedAt(DateUtils::getNow());
        $this->transactionRepository->update($data);
        $this->executionErrorLogService->create($eel);
        return $data;
    }

    /**
     * Este método retorna una transacción única por Token lista para ejecutar un commit sobre ella.
     *
     * @param string $token
     * @param ExecutionErrorLogDto $eel
     * @throws AlreadyApprovedWebpayException Cuando la transacción ya se encuentra autorizada.
     * @throws InvalidStatusWebpayException Cuando la transacción se encuentra en otro estado distinto al 'initialized'.
     * @throws OrderAlreadyPaidException Cuando se encuentra otra transacción autorizada en Transbank ('transbankStatus' = 'AUTHORIZED') para el mismo orderId.
     * @return TransbankTransactionDto
     */
    public function getByTokenForCommit($token, ExecutionErrorLogDto $eel){

        $tx = $this->getByToken($token);
        $eel->setBuyOrder($tx->getBuyOrder());

        if ($tx->getTransbankStatus() == TbkConstants::TRANSACTION_TBK_STATUS_AUTHORIZED) {
            $errorMessage = 'La transacción ya fue autorizada por Transbank anteriormente: '.$tx->token;
            $eel->setError('AlreadyApprovedWebpayException');
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new AlreadyApprovedWebpayException($errorMessage, $tx->getToken());
        }

        if ($tx->getStatus() !== TbkConstants::TRANSACTION_STATUS_INITIALIZED) {
            $errorMessage = 'La transacción no se encuentra en estado inicializada: '.$tx->token.', status: '.$tx->status;
            $eel->setError('InvalidStatusWebpayException');
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new InvalidStatusWebpayException($errorMessage, $tx->getToken(), $tx);
        }

        /*Buscamos alguna otra transacción que tenga la misma orden y se encuentre autorizada por Tbk, para ello debe tener otro token */
        $otherApprovedTx = $this->getTbkAuthorizedByOrderId($tx->getOrderId());
        if ($otherApprovedTx!=null && $otherApprovedTx->getToken() != $tx->getToken()) {
            /*Cuando la orden ya fue pagada con otra transacción se marca la transacción actual con error y se arroja error */
            $errorMessage = 'La orden ya fue pagada anteriormente con el token:'.$otherApprovedTx->getToken();
            $eel->setError('OrderAlreadyPaidException');
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new OrderAlreadyPaidException($errorMessage, $tx->getToken());
        }
        return $tx;
    }

    /**
     * Este método crea una transacción del tipo Webpayplus.
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     * @throws CreateTransactionDbException Cuando no puede crear la transacción.
     * @return TransbankTransactionDto
     */
    public function createForWebpayplus(TransbankTransactionDto $data, ExecutionErrorLogDto $eel){
        return $this->createForWebpayBase($data, TbkConstants::TRANSACTION_WEBPAY_PLUS, $eel);
    }

    /**
     * Este método crea una transacción del tipo WebpayplusMall.
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     * @throws CreateTransactionDbException Cuando no puede crear la transacción.
     * @return TransbankTransactionDto
     */
    public function createForWebpayplusMall(TransbankTransactionDto $data, ExecutionErrorLogDto $eel){
        return $this->createForWebpayBase($data, TbkConstants::TRANSACTION_WEBPAY_PLUS_MALL, $eel);
    }

    private function createForWebpayBase(TransbankTransactionDto $data, $product, ExecutionErrorLogDto $eel){
        try {
            $data->setProduct($product);
            $data->setStatus(TbkConstants::TRANSACTION_STATUS_PREPARED);
            $data->setRefundAmount(0);
            $this->transactionRepository->create($data);
            $this->logInfoWithBuyOrder($data->getBuyOrder(), $eel->getService(), "Transacción creada en la base de datos con estado prepared", $data);
        } catch (Exception $e) {
            $errorMessage = "La transacción no se pudo crear en la tabla: '{$this->transactionRepository->getTableName()}', error: {$e->getMessage()}";
            $eel->setError('CreateTransactionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new CreateTransactionDbException($errorMessage, $data);
        }
        return $this->getByBuyOrder($data->getBuyOrder());
    }

    /**
     * Este método crea una transacción del tipo Oneclick.
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     * @throws CreateTransactionDbException Cuando no puede crear la transacción.
     * @return TransbankTransactionDto
     */
    public function createForOneclick(TransbankTransactionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setProduct(TbkConstants::TRANSACTION_WEBPAY_ONECLICK);
            $data->setStatus(TbkConstants::TRANSACTION_STATUS_INITIALIZED);
            $data->setRefundAmount(0);
            $this->transactionRepository->create($data);
            $this->logInfoWithBuyOrder($data->getBuyOrder(), $eel->getService(), "Transacción creada en la base de datos con estado 'initialized'", $data);
        } catch (Exception $e) {
            $errorMessage = "La transacción no se pudo crear en la tabla: '{$this->transactionRepository->getTableName()}', error: {$e->getMessage()}";
            $eel->setError('CreateTransactionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new CreateTransactionDbException($errorMessage, $data);
        }
        return $this->getByBuyOrder($data->getBuyOrder());
    }

    /**
     * Este método actualiza el registro de la transacción luego de crear la transaccion en Transbank.
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     */
    public function updatePostCreateTbk(TransbankTransactionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setStatus(TbkConstants::TRANSACTION_STATUS_INITIALIZED);
            $data->setUpdatedAt(DateUtils::getNow());
            $this->transactionRepository->update($data);
            $this->logInfoWithBuyOrder($data->getBuyOrder(), $eel->getService(), "Transacción actualizada en la base de datos con estado initialized", $data);
        } catch (Exception $e) {
            $errorMessage = "La transacción no se pudo actualizar al estado initialized, ERROR: {$e->getMessage()}";
            $eel->setError('UpdateTransactionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new UpdateTransactionDbException($errorMessage, $data);
        }
    }

    /**
     * Este método actualiza el registro de la transacción luego de un commit exitoso en Transbank.
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     */
    public function updatePostCommitTbk(TransbankTransactionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setStatus(TbkConstants::TRANSACTION_STATUS_APPROVED);
            $data->setUpdatedAt(DateUtils::getNow());
            $this->transactionRepository->update($data);
            $this->logInfoWithBuyOrder($data->getBuyOrder(), $eel->getService(), "Transacción actualizada en la base de datos con estado approved", $data);
        } catch (Exception $e) {
            $errorMessage = "La transacción no se pudo actualizar al estado approved, ERROR: {$e->getMessage()}";
            $eel->setError('UpdateTransactionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new UpdateTransactionDbException($errorMessage, $data);
        }
    }

    /**
     * Este método actualiza el registro de la transacción luego de un authorize exitoso en Transbank.
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     */
    public function updatePostAuthorizeTbk(TransbankTransactionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setStatus(TbkConstants::TRANSACTION_STATUS_APPROVED);
            $data->setUpdatedAt(DateUtils::getNow());
            $this->transactionRepository->update($data);
            $this->logInfoWithBuyOrder($data->getBuyOrder(), $eel->getService(), "Transacción actualizada en la base de datos con estado approved", $data);
        } catch (Exception $e) {
            $errorMessage = "La transacción no se pudo actualizar al estado approved, ERROR: {$e->getMessage()}";
            $eel->setError('UpdateTransactionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new UpdateTransactionDbException($errorMessage, $data);
        }
    }

    /**
     * Este método actualiza el registro de la transacción luego de un commit exitoso en Transbank y pasar el commit en el ecommerce.
     *
     * @param string $buyOrder
     * @param ExecutionErrorLogDto $eel
     */
    public function updateCommitEcommerce($buyOrder, ExecutionErrorLogDto $eel){
        $data = $this->transactionRepository->getByBuyOrder($buyOrder);
        try {
            $data->setStatus(TbkConstants::TRANSACTION_STATUS_ECOMMERCE_APPROVED);
            $data->setUpdatedAt(DateUtils::getNow());
            $this->transactionRepository->update($data);
            $this->logInfoWithBuyOrder($data->getBuyOrder(), $eel->getService(), "Transacción actualizada en la base de datos con estado ecommerce_approved", $data);
            return $data;
        } catch (Exception $e) {
            $errorMessage = "La transacción no se pudo actualizar al estado ecommerce_approved, ERROR: {$e->getMessage()}";
            $eel->setError('UpdateTransactionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new UpdateTransactionDbException($errorMessage, $data);
        }
    }

    /**
     * Este método actualiza el registro de la transacción luego de un refund exitoso en Transbank.
     *
     * @param TransbankTransactionDto $data
     * @param ExecutionErrorLogDto $eel
     */
    public function updatePostRefundTbk(TransbankTransactionDto $data, ExecutionErrorLogDto $eel){
        try {
            $data->setUpdatedAt(DateUtils::getNow());
            $this->transactionRepository->update($data);
            $this->logInfoWithBuyOrder($data->getBuyOrder(), $eel->getService(), "Transacción actualizada en la base de datos con el refund", $data);
            return $data;
        } catch (Exception $e) {
            $errorMessage = "La transacción no se pudo actualizar con el resultado del refund, ERROR: {$e->getMessage()}";
            $eel->setError('UpdateTransactionDbException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new UpdateTransactionDbException($errorMessage, $data);
        }
    }

    /**
     * Este método retorna la transacción autorizada en Transbank detallada por orderId.
     *
     * @param string $orderId
     * @return null|TransbankTransactionDetail
     */
    public function getTbkAuthorizedDetailByOrderId($orderId){
        $this->logger->logInfo("ORDER_ID: '{$orderId}', MESSAGE: Preparando la transacción para mostrarla al finalizar el pago");
        $tx = $this->transactionRepository->getTbkAuthorizedByOrderId($orderId);
        if (!$tx) {
            $this->logger->logError("ORDER_ID: '{$orderId}', ERROR: No se encuentra una transacción aprobada en Transbank para mostrarla al finalizar el pago");
            return null;
        }
        return $this->getDetailBase($tx);
    }

    /**
     * Este método retorna una clase con un array de [etiqueta, valor] de la transacción autorizada en Transbank
     * para mostrarse en la sección de administracion del Ecommerce.
     *
     * @param string $orderId
     * @return null|DetailAdmin
     */
    public function getDetailForAdmin($orderId){
        $this->logger->logInfo("ORDER_ID: '{$orderId}', MESSAGE: Preparando la transacción para mostrarla en administar ordenes");
        $tx = $this->transactionRepository->getTbkAuthorizedByOrderId($orderId);
        if (!$tx) {
            $this->logger->logError("ORDER_ID: '{$orderId}', ERROR: No se encuentra una transacción aprobada en Transbank para mostrarla en administar ordenes");
            return null;
        }
        $detail = $this->getDetailBase($tx);
        $result = new DetailAdmin();
        if ($detail->isAuthorized()){
            if ($detail->isWebpayplus()){
                $result->setTitle("Pago exitoso con Webpay Plus");
            }
            else{
                $result->setTitle("Pago exitoso con Webpay Oneclick");
            }
        }
        $result->addItem("Estado", $detail->getTransbankStatusDes());
        $result->addItem("Orden de compra Transbank", $detail->getBuyOrder());
        $result->addItem("Código de autorización", $detail->getAuthorizationCode());
        $result->addItem("Número de tarjeta", $detail->getCardNumber());
        $result->addItem("Monto", $detail->getAmountFormat());
        $result->addItem("Código de respuesta", $detail->getResponseCode());
        $result->addItem("Tipo de pago", $detail->getPaymentType());
        $result->addItem("Tipo de cuota", $detail->getPaymentTypeDes());
        $result->addItem("Número de cuotas", $detail->getInstallmentsNumber());
        $result->addItem("Monto de cada cuota", $detail->getInstallmentsAmountFormat());
        $result->addItem("Token", $detail->getToken());
        $result->addItem("Fecha", $detail->getTransactionDateHourTzFormat());
        $result->addItem("ID interno", $detail->getId());
        return $result;
    }

    /**
     * Este método retorna una clase con un array de [etiqueta, valor] de la transacción autorizada en Transbank
     * para mostrarse en al finalizar la compra en el Ecommerce.
     *
     * @param string $orderId
     * @return null|DetailClient
     */
    public function getDetailForClient($orderId){
        $this->logger->logInfo("ORDER_ID: '{$orderId}', MESSAGE: Preparando la transacción para mostrarla al finalizar el pago");
        $tx = $this->transactionRepository->getTbkAuthorizedByOrderId($orderId);
        if (!$tx) {
            $this->logger->logError("ORDER_ID: '{$orderId}', ERROR: No se encuentra una transacción aprobada en Transbank para mostrarla al finalizar el pago");
            return null;
        }
        $detail = $this->getDetailBase($tx);
        $result = new DetailClient();
        if ($detail->isAuthorized()){
            if ($detail->isWebpayplus()){
                $result->setTitle("Pago exitoso con Webpay Plus");
            }
            else{
                $result->setTitle("Pago exitoso con Webpay Oneclick");
            }
        }
        $result->addItem("Respuesta de la transacción", $detail->getTransbankStatusDes());
        $result->addItem("Orden de compra", $detail->getBuyOrder());
        $result->addItem("Código de autorización", $detail->getAuthorizationCode());
        $result->addItem("Fecha de transacción", $detail->getTransactionDateTzFormat());
        $result->addItem("Hora de transacción", $detail->getTransactionHourTzFormat());
        $result->addItem("Número de tarjeta", $detail->getCardNumberFull());
        $result->addItem("Tipo de pago", $detail->getPaymentType());
        $result->addItem("Tipo de cuota", $detail->getPaymentTypeDes());
        $result->addItem("Monto de compra", $detail->getAmountFormat());
        $result->addItem("Número de cuotas", $detail->getInstallmentsNumber());
        $result->addItem("Monto de cada cuota", $detail->getInstallmentsAmountFormat());
        $result->addItem("Código de respuesta", $detail->getResponseCode());
        return $result;
    }

    /**
     * Este método retorna la transacción detallada por buyOrder.
     *
     * @param string $buyOrder
     * @return null|TransbankTransactionDetail
     */
    public function getDetailByBuyOrder($buyOrder){
        $this->logger->logInfo("BUY_ORDER: '{$buyOrder}', MESSAGE: Preparando la transacción para mostrarla al finalizar el pago");
        $tx = $this->transactionRepository->getByBuyOrder($buyOrder);
        if (!$tx) {
            $this->logger->logError("BUY_ORDER: '{$buyOrder}', ERROR: No se encuentra una transacción para mostrarla el detalle");
            return null;
        }
        return $this->getDetailBase($tx);
    }

    /**
     * Este método retorna la transacción detallada.
     *
     * @param TransbankTransactionDto $tx
     * @return TransbankTransactionDetail
     */
    private function getDetailBase(TransbankTransactionDto $tx){
        try {
            return new TransbankTransactionDetail($tx, $this->configRepository->getTimezone());
        } catch (Exception $e) {
            $errorMessage = "No se pudo obtener una transacción detallada, ERROR: {$e->getMessage()}";
            $eel = new ExecutionErrorLogDto();
            $eel->setEnvironment($tx->getStoreId());
            $eel->setEnvironment($tx->getEnvironment());
            $eel->setProduct($tx->getProduct());
            $eel->setCommerceCode($tx->getCommerceCode());
            $eel->setService(TbkConstants::SERVICE_GENERIC);
            $eel->setBuyOrder($tx->getBuyOrder());
            $eel->setError('GenericException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->executionErrorLogService->create($eel);
            throw new Exception($errorMessage);
        }
    }

    public function getDateLastTransactionOk($enviroment){
        return $this->transactionRepository->getDateLastTransactionOk($enviroment);
    }

    public function lastTransactionsOk($environment, $total){
        return $this->transactionRepository->lastTransactionsOk($environment, $total);
    }

    public function lastTransactionsByHour($environment, $total){
        return $this->transactionRepository->lastTransactionsByHour($environment, $total);
    }

    public function lastTransactionsByDay($environment, $total){
        return $this->transactionRepository->lastTransactionsByDay($environment, $total);
    }

    public function lastTransactionsByWeek($environment, $total){
        return $this->transactionRepository->lastTransactionsByWeek($environment, $total);
    }

    public function lastTransactionsByMonth($environment, $total){
        return $this->transactionRepository->lastTransactionsByMonth($environment, $total);
    }

}


