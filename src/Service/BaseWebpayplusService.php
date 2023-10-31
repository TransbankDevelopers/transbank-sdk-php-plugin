<?php

namespace Transbank\Plugin\Service;

use Exception;
use Transbank\Plugin\Helpers\TbkValidationUtil;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Model\TransbankTransactionDto;
use Transbank\Plugin\Model\ExecutionErrorLogDto;

use Transbank\Plugin\Exceptions\Webpay\StatusTbkWebpayException;
use Transbank\Plugin\Exceptions\Webpay\RefundTbkWebpayException;

use Transbank\Plugin\Exceptions\Webpay\TimeoutWebpayException;
use Transbank\Plugin\Exceptions\Webpay\UserCancelWebpayException;
use Transbank\Plugin\Exceptions\Webpay\DoubleTokenWebpayException;
use Transbank\Plugin\Exceptions\Webpay\RejectedRefundWebpayException;
use Transbank\Plugin\Exceptions\Webpay\InvalidPluginParamWebpayException;

abstract class BaseWebpayplusService extends TbkProductService {

    abstract public function sdkStatus($token);
    abstract public function sdkRefund(TransbankTransactionDto $tx, $amount);
    abstract protected function createTransactionInTbk(TransbankTransactionDto $tx, $returnUrl);

    public function testCreateTransaction(){
        try {
            $errorMessage = null;
            $randomNumber = $this->getRandomNumber();
            $tx = new TransbankTransactionDto();
            $tx->setOrderId('0');
            $tx->setBuyOrder('tbk:'.$randomNumber.':0');
            $tx->setAmount(50);
            $tx->setSessionId('tbk:sessionId:'.$randomNumber.':0');
            $tx->setCommerceCode($this->getCommerceCode());
            $tx->setEnvironment($this->getEnvironment());
            $response = $this->createTransactionInTbk($tx, 'http://test.com/test');
            if (!isset($response) || !isset($response->url) || !isset($response->token)) {
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
    private function statusInTbk($buyOrder, $token)
    {
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::WEBPAYPLUS_STATUS);
        $asl->setBuyOrder($buyOrder);
        $asl->setInput(json_encode(['token'  => $token]));
        try {
            $response = $this->sdkStatus($token);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $asl->setOriginalError($e->getMessage());
            if (TbkValidationUtil::isApiMismatchError($e)) {
                $errorMessage = 'Esta utilizando una version de api distinta a la utilizada para crear la transacción';
                $asl->setError('StatusTbkWebpayException');
                $asl->setCustomError($errorMessage);
                $this->errorExecutionTbkApi($asl);
                throw new StatusTbkWebpayException($errorMessage, $token);
            } elseif (TbkValidationUtil::isMaxTimeError($e)) {
                $errorMessage = 'Ya pasaron mas de 7 dias desde la creacion de la transacción, ya no es posible consultarla por este medio';
                $asl->setError('StatusTbkWebpayException');
                $asl->setCustomError($errorMessage);
                $this->errorExecutionTbkApi($asl);
                throw new StatusTbkWebpayException($errorMessage, $token);
            }
            $errorMessage = 'Ocurrió un error al tratar de obtener el status ( token: '.$token.') de la transacción Webpay en Transbank: '.$e->getMessage();
            $asl->setCustomError($errorMessage);
            $this->errorExecutionTbkApi($asl);
            throw new StatusTbkWebpayException($errorMessage, $token);
        }
    }

    public function status($token)
    {
        $tx = $this->transactionService->getByToken($token);
        return $this->statusInTbk($tx->getBuyOrder(), $token);
    }


    /* Metodo COMMIT  */

    private function getTransactionFromParam($buyOrder, $tokenWsOrTbkToken, ExecutionErrorLogDto $eel){
        $tx = null;
        if ($buyOrder === '0'){
            $errorMessage = 'La transacción no cuenta con el parámetro plugin, es inválida.';
            $eel->setError('InvalidPluginParamWebpayException');
            $eel->setCustomError($errorMessage);
            $this->errorExecution($eel);
            throw new InvalidPluginParamWebpayException($errorMessage);
        }
        
        try{
            $tx = $this->transactionService->getByBuyOrder($buyOrder);
        }
        catch(Exception $e){
            $errorMessage = 'La transacción no cuenta con el parámetro plugin, es inválida.';
            $eel->setError('InvalidPluginParamWebpayException');
            $eel->setOriginalError($e->getMessage());
            $eel->setCustomError($errorMessage);
            $this->errorExecution($eel);
            throw new InvalidPluginParamWebpayException($errorMessage);
        }

        if (!is_null($tokenWsOrTbkToken) && $tx->getToken() !== $tokenWsOrTbkToken){
            $errorMessage = 'El token no coincide con el token del parámetro plugin';
            $eel->setError('InvalidPluginParamWebpayException');
            $eel->setCustomError($errorMessage);
            $this->errorExecution($eel);
            throw new InvalidPluginParamWebpayException($errorMessage);
        }
        return $tx;
    }

    /**
     * Este método procesa el retorno desde el formulario de pago de Transabnk antes del commit.
     *
     * @param array $server
     * @param array $get
     * @param array $post
     * @throws AlreadyApprovedWebpayException Cuando la transacción ya se encuentra autorizada.
     * @throws InvalidStatusWebpayException Cuando la transacción se encuentra en otro estado distinto al 'initialized'.
     * @throws OrderAlreadyPaidException Cuando se encuentra otra transacción autorizada en Transbank ('transbankStatus' = 'AUTHORIZED') para el mismo orderId.
     * @return TransbankTransactionDto
     */
    public function processRequestFromTbkReturn($server, $get, $post)
    {
        $method = $server['REQUEST_METHOD'];
        $params = $method === 'GET' ? $get : $post;
        $tbkToken = isset($params["TBK_TOKEN"]) ? $params['TBK_TOKEN'] : null;
        $tbkSessionId = isset($params["TBK_ID_SESION"]) ? $params['TBK_ID_SESION'] : null;
        $tbkOrdenCompra = isset($params["TBK_ORDEN_COMPRA"]) ? $params['TBK_ORDEN_COMPRA'] : null;
        $tokenWs = isset($params["token_ws"]) ? $params['token_ws'] : null;
        $tokenWsOrTbkToken = is_null($tokenWs) ? $tokenWs : $tbkToken;
        $buyOrderFromParam =  isset($params[TbkConstants::RETURN_URL_PARAM]) ? base64_decode($params[TbkConstants::RETURN_URL_PARAM]) : '0';
        $params1 = [
            'method' => $method,
            'params' => $params,
            'buyOrderFromParam' => $buyOrderFromParam
        ];

        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::WEBPAYPLUS_COMMIT);
        $eel->setBuyOrder($buyOrderFromParam);
        $eel->setData(json_encode($params1));

        $this->logInfoWithBuyOrder($eel->getBuyOrder(), TbkConstants::WEBPAYPLUS_COMMIT, "Retornando desde el sitio de Transbank para realizar el commit", $params1);
        $transaction = $this->getTransactionFromParam($buyOrderFromParam, $tokenWsOrTbkToken, $eel);
        if (!isset($tokenWs) && !isset($tbkToken)) {
            $errorMessage = 'La transacción fue cancelada automáticamente por estar inactiva mucho tiempo en el formulario de pago de Webpay. Puede reintentar el pago';
            $eel->setError('TimeoutWebpayException');
            $eel->setCustomError($errorMessage);
            $this->transactionService->updateInitializedWithErrorForCommit($transaction, $eel);
            throw new TimeoutWebpayException($errorMessage, $tbkOrdenCompra, $tbkSessionId, $transaction);
        }
        elseif(!isset($tokenWs) && isset($tbkToken)) {
            $errorMessage = 'La transacción fue anulada por el usuario.';
            $eel->setError('UserCancelWebpayException');
            $eel->setCustomError($errorMessage);
            $eel->setBuyOrder($transaction->getBuyOrder());
            $this->transactionService->updateInitializedWithAbortedByUserForCommit($transaction, $eel);
            throw new UserCancelWebpayException($errorMessage, $tbkToken, $transaction);
        }
        elseif(isset($tbkToken) && isset($tokenWs)) {
            $errorMessage = 'El pago es inválido.';
            $eel->setError('DoubleTokenWebpayException');
            $eel->setCustomError($errorMessage);
            $eel->setBuyOrder($transaction->getBuyOrder());
            $this->transactionService->updateInitializedWithErrorForCommit($transaction, $eel);
            throw new DoubleTokenWebpayException($errorMessage, $tbkToken, $tokenWs, $transaction);
        }
        return $this->transactionService->getByTokenForCommit($tokenWs, $eel);
    }

    public function commitTransactionEcommerce($buyOrder)
    {
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::WEBPAYPLUS_COMMIT);
        $eel->setBuyOrder($buyOrder);
        $tx = $this->transactionService->updateCommitEcommerce($buyOrder, $eel);
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::WEBPAYPLUS_COMMIT, "***** COMMIT ECOMMERCE OK *****", [
            'token'  => $tx->getToken()
        ]);
    }

    /* Metodo REFUND  */
    

    private function refundTransactionInTbk(TransbankTransactionDto $tx, $amount)
    {
        $param = [
            'transaction'  => $tx,
            'refundAmount' => $amount
        ];
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::WEBPAYPLUS_REFUND);
        $asl->setBuyOrder($tx->getBuyOrder());
        $asl->setInput(json_encode($param));
        try {
            $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::WEBPAYPLUS_REFUND, 'Preparando datos antes de hacer refund a la transacción en Transbank', $param);
            $response = $this->sdkRefund($tx, $amount);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $errorMessage = 'Ocurrió un error al ejecutar el refund de la transacción en Webpay con el "token": "'.$tx->getToken().'" y "monto": "'.$amount;
            $asl->setError('RefundTbkWebpayException');
            $asl->setOriginalError($e->getMessage());
            $asl->setCustomError($errorMessage);
            $this->errorExecutionTbkApi($asl);
            throw new RefundTbkWebpayException($errorMessage, $tx);
        }
    }

    public function refundTransaction($orderId, $amount)
    {
        $orderId = (string)$orderId;
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::WEBPAYPLUS_REFUND);
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
        $eel->setService(TbkConstants::WEBPAYPLUS_REFUND);
        $eel->setBuyOrder($buyOrder);
        $eel->setData(json_encode(['amount'  => $amount]));
        $tx = $this->transactionService->getTbkAuthorizedByBuyOrderForRefund($buyOrder, $eel);
        return $this->refundTransactionBase($tx, $amount, $eel);
    }

    private function refundTransactionBase(TransbankTransactionDto $tx, $amount, ExecutionErrorLogDto $eel)
    {
        $response = $this->refundTransactionInTbk($tx, $amount);
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::WEBPAYPLUS_REFUND, 'Se hizo el refund a la transacción en Transbank', [
            'transaction'  => $tx,
            'refundAmount' => $amount,
            'response'  => $response
        ]);

        /*3. Validamos si fue exitoso */
        if (!(($response->getType() === TbkConstants::TRANSACTION_TBK_REFUND_REVERSED || $response->getType() === TbkConstants::TRANSACTION_TBK_REFUND_NULLIFIED) && (int) $response->getResponseCode() === 0)) {
            $errorMessage = 'El refund de la transacción ha sido rechazado por Transbank (código de respuesta: "'.$response->getResponseCode().'")';
            $eel->setError('RejectedRefundWebpayException');
            $eel->setCustomError($errorMessage);
            $eel->setBuyOrder($tx->getBuyOrder());
            $eel->setData(json_encode([
                'token' => $tx->getToken(),
                'response'  => $response
            ]));
            $this->errorExecution($eel);
            throw new RejectedRefundWebpayException($errorMessage, $tx, $response);
        }
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::WEBPAYPLUS_REFUND, '***** REFUND TBK OK *****', [
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


    /*Metodos para el Ecommerce*/
    public function saveTransactiondFailed(TransbankTransactionDto $tx, $service, $originalError, $customError)
    {
        $buyOrder = $tx!=null ? $tx->getBuyOrder() : '0';
        $eel = $this->newExecutionErrorLog();
        $eel->setService($service);
        $eel->setBuyOrder($buyOrder);
        $eel->setError('EcommerceException');
        $eel->setOriginalError($originalError);
        $eel->setCustomError($customError);
        $this->transactionService->updateWithErrorForCommitEcommerce($tx, $eel);
    }
}
