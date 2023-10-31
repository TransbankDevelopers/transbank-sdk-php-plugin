<?php

namespace Transbank\Plugin\Service;

use Exception;
use Transbank\Plugin\Exceptions\Webpay\CommitTbkWebpayException;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\Service\TransactionService;
use Transbank\Plugin\Service\ApiServiceLogService;
use Transbank\Plugin\Service\ExecutionErrorLogService;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Plugin\Helpers\TbkValidationUtil;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Model\WebpayplusConfig;
use Transbank\Plugin\Model\TransbankTransactionDto;

use Transbank\Plugin\Exceptions\Webpay\CreateTbkWebpayException;
use Transbank\Plugin\Exceptions\Webpay\InvalidCreateWebpayException;
use Transbank\Plugin\Exceptions\Webpay\RejectedCommitWebpayException;
use Transbank\Plugin\IRepository\IConfigRepository;
use Transbank\Webpay\WebpayPlus\Responses\TransactionCommitResponse;

final class TbkWebpayplusService extends BaseWebpayplusService {
    
    /**
     * @var \Transbank\Webpay\WebpayPlus\Transaction
     */
    private $webpayplusTransaction;

    public function __construct(ILogger $logger, IConfigRepository $configRepository, ApiServiceLogService $apiServiceLogService, ExecutionErrorLogService $executionErrorLogService, TransactionService $transactionService, $storeId = '0') {
        parent::__construct($logger, $configRepository, $apiServiceLogService, $executionErrorLogService, $transactionService, $storeId);
        $config = $this->configRepository->getWebpayplusConfig();
        $this->options = $this->createOptions($config);
        $this->config = $config;
        $this->webpayplusTransaction = new Transaction($this->options);
        $this->product = TbkConstants::TRANSACTION_WEBPAY_PLUS;
    }

    /**
     * @return Options
    */
    private function createOptions(WebpayplusConfig $config)
    {
        $options = Transaction::getDefaultOptions();
        if ($config->isProduction()) {
            $options = Options::forProduction($config->getCommerceCode(), $config->getApikey());
        }
        return $options;
    }

    public function checkConfiguration(){
        return TbkValidationUtil::validateProductConfig($this->config);
    }

    public function sdkStatus($token){
        return $this->webpayplusTransaction->status($token);
    }

    public function sdkRefund(TransbankTransactionDto $tx, $amount){
        return $this->webpayplusTransaction->refund($tx->getToken(), $amount);
    }

    /* Metodo CREATE  */
    protected function createTransactionInTbk(TransbankTransactionDto $tx, $returnUrl){
        $buyOrderBase64 = base64_encode($tx->getBuyOrder());
        $returnUrl = "{$returnUrl}?".TbkConstants::RETURN_URL_PARAM."={$buyOrderBase64}";
        $params = [
            'transaction'  => $tx,
            'returnUrl' => $returnUrl
        ];
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::WEBPAYPLUS_CREATE);
        $asl->setBuyOrder($tx->getBuyOrder());
        $asl->setInput(json_encode($params));
        try {
            $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::WEBPAYPLUS_CREATE, 'Preparando datos antes de crear la transacción en Transbank', $params);
            $response = $this->webpayplusTransaction->create($tx->getBuyOrder(), $tx->getSessionId(), $tx->getAmount(), $returnUrl);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $errorMessage = 'Ocurrió un error al tratar de crear la transacción en Transbank: '.$e->getMessage();
            $asl->setError('CreateTbkWebpayException');
            $asl->setOriginalError($e->getMessage());
            $asl->setCustomError($errorMessage);
            $this->errorExecutionTbkApi($asl);
            throw new CreateTbkWebpayException($errorMessage);
        }
    }

    public function createTransaction($orderId, $amount, $returnUrl){
        $randomNumber = $this->getRandomNumber();
        $tx = new TransbankTransactionDto();
        $tx->setStoreId($this->getStoreId());
        $tx->setOrderId((string)$orderId);
        $tx->setBuyOrder('tbk:'.$randomNumber.':'.$orderId);
        $tx->setAmount($amount);
        $tx->setSessionId('tbk:sessionId:'.$randomNumber.':'.$orderId);
        $tx->setCommerceCode($this->getCommerceCode());
        $tx->setEnvironment($this->getEnvironment());

        $params = [
            'transaction'  => $tx,
            'returnUrl' => $returnUrl
        ];

        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::WEBPAYPLUS_CREATE);
        $eel->setBuyOrder($tx->getBuyOrder());
        $eel->setData(json_encode($params));

        /*1. Creamos la transacción antes de crear la tx en TBK */
        $tx = $this->transactionService->createForWebpayplus($tx, $eel);

        /*2. Creamos la transaccion en tbk*/
        $createResponse = $this->createTransactionInTbk($tx, $returnUrl);

        /*3. Validamos si esta ok */
        if (!isset($createResponse) || !isset($createResponse->url) || !isset($createResponse->token)) {
            $errorMessage = 'No se pudo crear una transacción válida en Transbank';
            $eel->setError('InvalidCreateWebpayException');
            $eel->setCustomError($errorMessage);
            $this->errorExecution($eel);
            throw new InvalidCreateWebpayException($errorMessage, $tx);
        }

        /*4. Actualizamos el token y el estado del registro */
        $tx->setToken($createResponse->token);
        $this->transactionService->updatePostCreateTbk($tx, $eel);

        return $createResponse;
    }


    /* COMMIT */

    /**
     * @return TransactionCommitResponse
     */
    private function commitTransactionInTbk($buyOrder, $token, $tx)
    {
        $params = ['token'  => $token];
        $asl = $this->newApiServiceLogDto();
        $asl->setService(TbkConstants::WEBPAYPLUS_COMMIT);
        $asl->setBuyOrder($buyOrder);
        $asl->setInput(json_encode($params));
        try {
            $this->logInfoWithBuyOrder($buyOrder, TbkConstants::WEBPAYPLUS_COMMIT, "Preparando datos antes de hacer commit a la transacción en Transbank", $params);
            $response = $this->webpayplusTransaction->commit($token);
            $asl->setResponse(json_encode($response));
            $this->afterExecutionTbkApi($asl);
            return $response;
        } catch (Exception $e) {
            $errorMessage = 'Ocurrió un error al ejecutar el commit de la transacción: '.$e->getMessage();
            $asl->setError('CommitTbkWebpayException');
            $asl->setOriginalError($e->getMessage());
            $asl->setCustomError($errorMessage);
            $this->transactionService->updateInitializedWithErrorForCommit($tx, $this->apiServiceLogToExecutionErrorLog($asl));
            $this->apiServiceLogService->createOnlyError($asl);
            throw new CommitTbkWebpayException($errorMessage, $tx);
        }
    }

    /**
     * @return TransactionCommitResponse
     */
    public function commitTransaction($token)
    {
        $eel = $this->newExecutionErrorLog();
        $eel->setService(TbkConstants::WEBPAYPLUS_COMMIT);

        $tx = $this->transactionService->getByToken($token);
        $commitResponse = $this->commitTransactionInTbk($tx->getBuyOrder(), $token, $tx);
        if (!$commitResponse->isApproved()) {
            $errorMessage = 'El commit de la transacción ha sido rechazada en Transbank (código de respuesta: '.$commitResponse->getResponseCode().')';
            $eel->setError('RejectedCommitWebpayException');
            $eel->setCustomError($errorMessage);
            $eel->setBuyOrder($tx->getBuyOrder());
            $eel->setData(json_encode([
                'token' => $token,
                'response'  => $commitResponse
            ]));
            $this->transactionService->updateInitializedWithErrorForCommit($tx, $eel);
            throw new RejectedCommitWebpayException($errorMessage, $tx, $commitResponse);
        }
        $this->logInfoWithBuyOrder($tx->getBuyOrder(), TbkConstants::WEBPAYPLUS_COMMIT, "***** COMMIT TBK OK ***** SI NO ENCUENTRA COMMIT POR EL ECOMMERCE DEBE ANULARSE", [
            'token'  => $token,
            'response'  => $commitResponse
        ]);

        $tx->setTransbankStatus($commitResponse->getStatus());
        $tx->setTransbankResponse(json_encode($commitResponse));
        $this->transactionService->updatePostCommitTbk($tx, $eel);
        return $commitResponse;
    }

}
