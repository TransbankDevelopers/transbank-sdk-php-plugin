<?php

namespace Transbank\Plugin\Service;

use \Exception;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\Service\TransactionService;
use Transbank\Plugin\Service\ApiServiceLogService;
use Transbank\Plugin\Service\ExecutionErrorLogService;
use Transbank\Plugin\Service\InscriptionService;
use Transbank\Webpay\Oneclick\MallInscription;
use Transbank\Webpay\Oneclick\MallTransaction;
use Transbank\Webpay\Oneclick\Responses\InscriptionStartResponse;
use Transbank\Webpay\Oneclick\Responses\MallTransactionAuthorizeResponse;
use Transbank\Webpay\Options;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Helpers\TbkValidationUtil;

use Transbank\Plugin\Exceptions\Oneclick\StatusTbkOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\StartTbkOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\FinishTbkOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\AuthorizeTbkOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\RefundTbkOneclickException;

use Transbank\Plugin\Exceptions\Oneclick\RejectedRefundOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\RejectedAuthorizeOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\ConstraintsViolatedAuthorizeOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\WithoutTokenInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\TimeoutInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\UserCancelInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\InvalidStatusInscriptionOneclickException;
use Transbank\Plugin\Exceptions\Oneclick\RejectedInscriptionOneclickException;
use Transbank\Plugin\Model\ExecutionErrorLogDto;
use Transbank\Plugin\Model\MallProductConfig;
use Transbank\Plugin\Model\TransbankInscriptionDto;
use Transbank\Plugin\Model\TransbankTransactionDto;
use Transbank\Plugin\IRepository\IConfigRepository;

final class TbkOneclickService extends TbkProductService  {
      
    /**
     * @var MallTransaction
     */
    protected $mallTransaction;

    /**
     * @var MallInscription
     */
    protected $mallInscription;

    protected $childCommerceCode;

    protected $inscriptionService;

    public function __construct(ILogger $logger, IConfigRepository $configRepository, ApiServiceLogService $apiServiceLogService, ExecutionErrorLogService $executionErrorLogService, TransactionService $transactionService, InscriptionService $inscriptionService, $storeId = '0') {
        parent::__construct($logger, $configRepository, $apiServiceLogService, $executionErrorLogService, $transactionService, $storeId);
        $config = $this->configRepository->getOneclickConfig()->getMallProductConfigByStoreId($storeId);
        $this->options = $this->createOptions($config);
        $this->config = $config;
        $this->inscriptionService = $inscriptionService;
        $this->mallTransaction = new MallTransaction($this->options);
        $this->mallInscription = new MallInscription($this->options);
        $this->product = TbkConstants::TRANSACTION_WEBPAY_ONECLICK;
    }

    /**
     * @return Options
    */
    private function createOptions(MallProductConfig $config)
    {
        $options = MallTransaction::getDefaultOptions();
        if ($config->isProduction()) {
            $options = Options::forProduction($config->getCommerceCode(), $config->getApikey());
        }
        return $options;
    }

    /**
     * @return MallProductConfig
    */
    public function getConfig(){
        return $this->config;
    }

    public function getChildCommerceCode()
    {
        return $this->getConfig()->getChildCommerceCode();
    }

    public function checkConfiguration(){
        return TbkValidationUtil::validateMallProductConfig($this->config);
    }

    public function testStartInscription(){
        try {
            $errorMessage = null;
            $randomNumber = $this->getRandomNumber();
            $orderId = '0';
            $userId = '0';
            $username = 'tbk:'.$randomNumber.':'.$userId;
            $email = 'test@test.com';
            $response =  $this->startInscriptionInTbk($orderId, $username, $email, 'http://test.com/test');
            if (!isset($response) || !isset($response->urlWebpay) || !isset($response->token)) {
                $errorMessage = "No se pudo crear una transacción válida en Transbank";
            }
            return [
                "error" => $errorMessage,
                "result" => $response,
                "ok" => is_null($errorMessage)
            ];
        } catch (Exception $e) {
            return [
                "error" => "Ocurrio un error al ejecutar el test: {$e->getMessage()}",
                "result" => $response,
                "ok" => false
            ];
        }
    }

    /* Metodo STATUS  */
    private function statusInTbk($buyOrder)
    {
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::ONECLICK_STATUS);
        $asl->setBuyOrder($buyOrder);
        try {
            $response = $this->mallTransaction->status($buyOrder);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $asl->setOriginalError($e->getMessage());
            if (TbkValidationUtil::isApiMismatchError($e)) {
                $errorMessage = 'Esta utilizando una version de api distinta a la utilizada para crear la transacción';
                $asl->setError('StatusTbkOneclickException');
                $asl->setCustomError($errorMessage);
                $this->errorExecutionTbkApi($asl);
                throw new StatusTbkOneclickException($errorMessage, $buyOrder);
            } elseif (TbkValidationUtil::isMaxTimeError($e)) {
                $errorMessage = 'Ya pasaron mas de 7 dias desde la creacion de la transacción, ya no es posible consultarla por este medio';
                $asl->setError('StatusTbkOneclickException');
                $asl->setCustomError($errorMessage);
                $this->errorExecutionTbkApi($asl);
                throw new StatusTbkOneclickException($errorMessage, $buyOrder);
            }
            $errorMessage = 'Ocurrió un error al tratar de obtener el status ( buyOrder: '.$buyOrder.') de la transacción Webpay en Transbank: '.$e->getMessage();
            $asl->setCustomError($errorMessage);
            $this->errorExecutionTbkApi($asl);
            throw new StatusTbkOneclickException($errorMessage, $buyOrder);
        }
    }

    public function status($buyOrder)
    {
        $tx = $this->transactionService->getByBuyOrder($buyOrder);
        return $this->statusInTbk($tx->getBuyOrder());
    }

    /* Metodo START  */

    /**
     * @param $orderId
     * @param $username
     * @param $email
     * @param $returnUrl
     *
     * @throws StartTbkOneclickException
     *
     * @return InscriptionStartResponse
     */
    private function startInscriptionInTbk($orderId, $username, $email, $returnUrl)
    {
        $params = [
            'username'  => $username,
            'email'     => $email,
            'returnUrl' => $returnUrl
        ];
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::ONECLICK_START);
        $asl->setInput(json_encode($params));
        $asl->setBuyOrder($orderId);
        try {
            $this->logInfoWithBuyOrder($orderId, TbkConstants::ONECLICK_START, "Preparando datos antes de crear la inscripción en Transbank", $params);
            $response = $this->mallInscription->start($username, $email, $returnUrl);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $errorMessage = 'Ocurrió un error al tratar iniciar la inscripcion: '.$e->getMessage();
            $asl->setError('StartTbkOneclickException');
            $asl->setOriginalError($e->getMessage());
            $asl->setCustomError($errorMessage);
            $this->errorExecutionTbkApi($asl);
            throw new StartTbkOneclickException($errorMessage);
        }
    }

    private function createOneclickInscriptionDb($orderId, $token, $username, $userId, $email, $from){
        $data = new TransbankInscriptionDto();
        $data->setStoreId($this->getStoreId());
        $data->setToken($token);
        $data->setUsername($username);
        $data->setOrderId($orderId);
        $data->setUserId($userId);
        $data->setPayAfterInscription(false);
        $data->setEmail($email);
        $data->setFrom($from);
        $data->setEnvironment($this->getEnvironment());
        $data->setCommerceCode($this->getCommerceCode());

        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_START);
        $eel->setBuyOrder($orderId);
        $eel->setData(json_encode($data));

        return $this->inscriptionService->create($data, $eel);
    }

    public function startInscription($orderId, $userId, $email, $returnUrl, $from)
    {
        $randomNumber = $this->getRandomNumber();
        $orderId = (string)$orderId;
        $userId = (string)$userId;
        $orderId = $orderId !== null ? $orderId : '0';
        $username = 'tbk:'.$randomNumber.':'.$userId;

        /*1. Iniciamos la inscripcion */
        $response = $this->startInscriptionInTbk($orderId, $username, $email, $returnUrl);
        /*2. La guardamos en la base de datos */
        $this->createOneclickInscriptionDb($orderId, $response->getToken(), $username, $userId, $email, $from);
        return $response;
    }

    /* Metodo FINISH  */
    private function processRequestFromTbkReturn($server, $get, $post)
    {
        $method = $server['REQUEST_METHOD'];
        $params = $method === 'GET' ? $get : $post;
        $tbkToken = isset($params["TBK_TOKEN"]) ? $params['TBK_TOKEN'] : null;
        $tbkSessionId = isset($params["TBK_ID_SESION"]) ? $params['TBK_ID_SESION'] : null;
        $tbkOrdenCompra = isset($params["TBK_ORDEN_COMPRA"]) ? $params['TBK_ORDEN_COMPRA'] : null;

        $params1 = [
            'method' => $method,
            'params' => $params
        ];

        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_FINISH);
        $eel->setBuyOrder('0');
        $eel->setData(json_encode($params1));

        $this->logInfoWithBuyOrder('0', TbkConstants::ONECLICK_FINISH, "Iniciando validación luego de redirección desde tbk", $params1);

        if (!isset($tbkToken)) {
            $errorMessage = 'No se recibió el token de la inscripción.';
            $eel->setError('WithoutTokenInscriptionOneclickException');
            $eel->setCustomError($errorMessage);
            $this->errorExecution($eel);
            throw new WithoutTokenInscriptionOneclickException($errorMessage);
        }

        if ($tbkOrdenCompra && $tbkSessionId && !$tbkToken) {
            $inscription = $this->inscriptionService->getByToken($tbkToken);
            $errorMessage = 'La inscripción fue cancelada automáticamente por estar inactiva mucho tiempo.';
            $eel->setError('TimeoutInscriptionOneclickException');
            $eel->setCustomError($errorMessage);
            $this->inscriptionService->updateInitializedWithError($inscription, $eel);
            throw new TimeoutInscriptionOneclickException($errorMessage, $inscription);
        }
        
        if (isset($tbkOrdenCompra)) {
            $inscription = $this->inscriptionService->getByToken($tbkToken);
            $errorMessage = 'La inscripción fue anulada por el usuario o hubo un error en el formulario de inscripción.';
            $eel->setError('UserCancelInscriptionOneclickException');
            $eel->setCustomError($errorMessage);
            $this->inscriptionService->updateInitializedWithError($inscription, $eel);
            throw new UserCancelInscriptionOneclickException($errorMessage, $inscription);
        }

        return $tbkToken;
    }

    public function processTbkReturnAndFinishInscription($server, $get, $post)
    {
        $tbkToken = $this->processRequestFromTbkReturn($server, $get, $post);
        $params = [
            'tbkToken' => $tbkToken
        ];
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_FINISH);
        $eel->setBuyOrder('0');
        $eel->setData(json_encode($params));
        $inscription = $this->inscriptionService->getByToken($tbkToken);

        if ($inscription->getStatus() !== TbkConstants::INSCRIPTIONS_STATUS_INITIALIZED) {
            $errorMessage = 'La inscripción no se encuentra en estado inicializada: '.$tbkToken;
            $eel->setError('InvalidStatusInscriptionOneclickException');
            $eel->setCustomError($errorMessage);
            $this->errorExecution($eel);
            throw new InvalidStatusInscriptionOneclickException($errorMessage, $inscription);
        }
        $response = $this->finishInscriptionInTbk($inscription->getOrderId(), $tbkToken, $inscription);
        $inscription->setTbkUser($response->getTbkUser());
        $inscription->setAuthorizationCode($response->getAuthorizationCode());
        $inscription->setCardType($response->getCardType());
        $inscription->setCardNumber($response->getCardNumber());
        $inscription->setTransbankResponse(json_encode($response));
        $inscription->setResponseCode(strval($response->getResponseCode()));
        if (!$response->isApproved()) {
            $errorMessage = 'La inscripción de la tarjeta ha sido rechazada (código de respuesta: '.$response->getResponseCode().')';
            $eel->setError('RejectedInscriptionOneclickException');
            $eel->setCustomError($errorMessage);
            $this->errorExecution($eel);
            $this->inscriptionService->updateInitializedWithError($inscription, $eel);
            throw new RejectedInscriptionOneclickException($errorMessage, $inscription, $response);
        }
        $this->inscriptionService->updatePostFinishTbk($inscription, $eel);
        return array(
            'inscription' => $inscription,
            'finishInscriptionResponse' => $response
        );
    }

    private function finishInscriptionInTbk($orderId, $tbkToken, TransbankInscriptionDto $inscription)
    {
        $params = ['tbkToken'  => $tbkToken];
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::ONECLICK_FINISH);
        $asl->setInput(json_encode($params));
        $asl->setBuyOrder($orderId);
        try {
            $response = $this->mallInscription->finish($tbkToken);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $errorMessage = 'Ocurrió un error al ejecutar la inscripción: '.$e->getMessage();
            $asl->setError('FinishTbkOneclickException');
            $asl->setOriginalError($e->getMessage());
            $asl->setCustomError($errorMessage);
            $this->inscriptionService->updateInitializedWithError($inscription, $this->apiServiceLogToExecutionErrorLog($asl));
            $this->apiServiceLogService->createOnlyError($asl);
            throw new FinishTbkOneclickException($errorMessage, $inscription);
        }
    }

    /* Metodo AUTHORIZE  */
    private function authorizeInTbk(TransbankTransactionDto $tx, $tbkUser)
    {
        $params = [
            'transaction'  => $tx,
            'tbkUser'      => $tbkUser
        ];
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::ONECLICK_AUTHORIZE);
        $asl->setInput(json_encode($params));
        $asl->setBuyOrder($tx->getBuyOrder());
        try {
            $details = [
                [
                    'commerce_code'       => $tx->getChildCommerceCode(),
                    'buy_order'           => $tx->getChildBuyOrder(),
                    'amount'              => $tx->getAmount(),
                    'installments_number' => 1,
                ],
            ];
            /*3. Autorizamos el pago*/
            $response = $this->mallTransaction->authorize(
                $tx->getOneclickUsername(),
                $tbkUser,
                $tx->getBuyOrder(),
                $details
            );
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $errorMessage = 'Ocurrió un error al ejecutar la autorización: '.$e->getMessage();
            $asl->setError('AuthorizeTbkOneclickException');
            $asl->setOriginalError($e->getMessage());
            $asl->setCustomError($errorMessage);
            $this->transactionService->updateInitializedWithErrorForAuthorize($tx, $this->apiServiceLogToExecutionErrorLog($asl));
            $this->apiServiceLogService->createOnlyError($asl);
            throw new AuthorizeTbkOneclickException($e->getMessage(), $tx);
        }
    }

    /**
     * @param $username
     * @param $orderId
     * @param $amount
     *
     *
     * @return MallTransactionAuthorizeResponse
     */
    public function authorize($username, $orderId, $amount) {
        $orderId = (string)$orderId;

        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_AUTHORIZE);
        $eel->setBuyOrder($orderId);
        $eel->setData(json_encode([
            'username'  => $orderId,
            'orderId'  => $orderId,
            'amount'   => $amount
        ]));

        $inscription = $this->inscriptionService->getByUsernameForAuthorize($username, $eel);

        $randomNumber = $this->getRandomNumber();
        $tx = new TransbankTransactionDto();
        $tx->setStoreId($this->getStoreId());
        $tx->setOrderId((string)$orderId);
        $tx->setBuyOrder('tbk:'.$randomNumber.':'.$orderId);
        $tx->setChildBuyOrder($username.':'.$orderId);
        $tx->setAmount($amount);
        $tx->setCommerceCode($this->getCommerceCode());
        $tx->setChildCommerceCode($this->getChildCommerceCode());
        $tx->setEnvironment($this->getEnvironment());
        $tx->setOneclickUsername($inscription->getUsername());

        $params = [
            'transaction'  => $tx,
            'tbkUser'      => $inscription->getTbkUser()
        ];

        $eel->setBuyOrder($tx->getBuyOrder());
        $eel->setData(json_encode($params));

        /*1. Creamos la transacción antes de autorizar en TBK */
        $tx = $this->transactionService->createForOneclick($tx, $eel);
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::ONECLICK_AUTHORIZE, "Transacción creada en la base de datos con estado initialized", $params);

        /*2. Autorizamos el pago*/
        $authorizeResponse = $this->authorizeInTbk($tx, $inscription->getTbkUser(), $tx);
        $transbankStatus = $authorizeResponse->getDetails()[0]->getStatus() ?? null;
        /*3. Validamos si esta aprobada */
        if (!$authorizeResponse->isApproved()) {
            if ($transbankStatus === 'CONSTRAINTS_VIOLATED') {
                $errorMessage = 'La transacción ha sido rechazada porque se superó el monto máximo por transacción, el monto máximo diario o el número de transacciones diarias configuradas por el comercio para cada usuario';
                $eel->setError('ConstraintsViolatedAuthorizeOneclickException');
                $eel->setCustomError($errorMessage);
                $this->errorExecution($eel);
                $this->transactionService->updateInitializedWithErrorForAuthorize($tx, $eel);
                throw new ConstraintsViolatedAuthorizeOneclickException($errorMessage, $tx, $authorizeResponse);
            } else {
                $errorCode = $authorizeResponse->getDetails()[0]->getResponseCode() ?? null;
                $errorMessage = 'La transacción ha sido rechazada (Código de error: '.$errorCode.')';
                $eel->setError('RejectedAuthorizeOneclickException');
                $eel->setCustomError($errorMessage);
                $this->errorExecution($eel);
                $this->transactionService->updateInitializedWithErrorForAuthorize($tx, $eel);
                throw new RejectedAuthorizeOneclickException($errorMessage, $tx, $authorizeResponse);
            }
        }

        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::ONECLICK_AUTHORIZE, "***** COMMIT TBK OK ***** SI NO ENCUENTRA COMMIT POR EL ECOMMERCE DEBE ANULARSE", [
            'response'  => $authorizeResponse
        ]);

        $tx->setTransbankStatus($transbankStatus);
        $tx->setTransbankResponse(json_encode($authorizeResponse));
        $this->transactionService->updatePostAuthorizeTbk($tx, $eel);
        return $authorizeResponse;
    }

    /* Metodo REFUND  */

    private function refundTransactioninTbk(TransbankTransactionDto $tx, $amount /*$buyOrder, $childCommerceCode, $childBuyOrder, $amount, TransbankTransactionDto $transaction*/)
    {
        $param = [
            'transaction'  => $tx,
            'refundAmount' => $amount
        ];
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::ONECLICK_REFUND);
        $asl->setBuyOrder($tx->getBuyOrder());
        $asl->setInput(json_encode($param));
        try {
            $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::ONECLICK_REFUND, 'Preparando datos antes de hacer refund a la transacción en Transbank', $param);
            $response = $this->mallTransaction->refund($tx->getBuyOrder(), $tx->getChildCommerceCode(), $tx->getChildBuyOrder(), $amount);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $errorMessage = "Ocurrió un error al ejecutar el refund de la transacción en Oneclick,  BUY_ORDER: '{$tx->getBuyOrder()}', MONTO: '{$amount}'";
            $asl->setError('RefundTbkOneclickException');
            $asl->setOriginalError($e->getMessage());
            $asl->setCustomError($errorMessage);
            $this->errorExecutionTbkApi($asl);
            throw new RefundTbkOneclickException($errorMessage, $tx);
        }
    }

    public function refundTransaction($orderId, $amount)
    {
        $orderId = (string)$orderId;
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_REFUND);
        $eel->setBuyOrder($orderId);
        $eel->setData(json_encode(['amount'  => $amount]));
        $tx = $this->transactionService->getTbkAuthorizedByOrderIdForRefund($orderId, $eel);
        $eel->setBuyOrder($tx->getBuyOrder());
        return $this->refundTransactionBase($tx, $amount, $eel);
    }

    public function refundTransactionByBuyOrder($buyOrder, $amount)
    {
        /*1. Extraemos la transacción */
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_REFUND);
        $eel->setBuyOrder($buyOrder);
        $eel->setData(json_encode(['amount'  => $amount]));
        $tx = $this->transactionService->getTbkAuthorizedByBuyOrderForRefund($buyOrder, $eel);
        return $this->refundTransactionBase($tx, $amount, $eel);
    }

    private function refundTransactionBase(TransbankTransactionDto $tx, $amount, ExecutionErrorLogDto $eel)
    {
        $response = $this->refundTransactioninTbk($tx, $amount);
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::ONECLICK_REFUND, 'Se hizo el refund a la transacción en Transbank', [
            'transaction'  => $tx,
            'refundAmount'  => $amount,
            'response'  => $response
        ]);

        /*3. Validamos si fue exitoso */
        if (!(($response->getType() === TbkConstants::TRANSACTION_TBK_REFUND_REVERSED || $response->getType() === TbkConstants::TRANSACTION_TBK_REFUND_NULLIFIED) && (int) $response->getResponseCode() === 0)) {
            $errorMessage = 'El refund de la transacción ha sido rechazado por Transbank (código de respuesta: "'.$response->getResponseCode().'")';
            $eel->setError('RejectedRefundOneclickException');
            $eel->setCustomError($errorMessage);
            $eel->setBuyOrder($tx->getBuyOrder());
            $eel->setData(json_encode([
                'transaction'  => $tx,
                'refundAmount'  => $amount
            ]));
            $this->errorExecution($eel);
            throw new RejectedRefundOneclickException($errorMessage, $tx, $response);
        }
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::ONECLICK_REFUND, '***** REFUND TBK OK *****', [
            'token'  => $tx->token,
            'amount'  => $amount,
        ]);
        if (is_null($response->getBalance()) || $response->getBalance() == 0){
            $tx->setRefundAmount($tx->getAmount());
        }
        else {
            $tx->setRefundAmount($tx->getAmount() - $response->getBalance());
        }
        /*4. Si todo ok guardamos el estado */
        $tx->setLastRefundType($response->getType());
        $tx->setLastRefundResponse(json_encode($response));
        $arrayRefund = [];
        if (!is_null($tx->getAllRefundResponse())){
            $arrayRefund = json_decode($tx->getAllRefundResponse());
        }
        $arrayRefund[] = $tx->getLastRefundResponse();
        $tx->setAllRefundResponse(json_encode($arrayRefund));
        $this->transactionService->updatePostRefundTbk($tx, $eel);
        return array(
            'transaction' => $tx,
            'refundResponse' => $response
        );
    }

    public function getCountInscriptionByUserId($userId){
        return $this->inscriptionService->getCountByUserId($userId);
    }
    
    public function getListInscriptionByUserId($userId){
        return $this->inscriptionService->getListByUserId($userId);
    }

    /*Metodos para el Ecommerce*/
    public function commitTransactionEcommerce($buyOrder)
    {
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_AUTHORIZE);
        $eel->setBuyOrder($buyOrder);
        $tx = $this->transactionService->updateCommitEcommerce($buyOrder, $eel);
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::ONECLICK_AUTHORIZE, "***** COMMIT ECOMMERCE OK *****", []);
    }
    
    public function updateEcommerceToken(TransbankInscriptionDto $data, $tokenId)
    {
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::ONECLICK_FINISH);
        $eel->setBuyOrder($data->getOrderId());
        $data->setEcommerceTokenId($tokenId);
        $this->inscriptionService->updateEcommerceToken($data, $eel);
    }
}



