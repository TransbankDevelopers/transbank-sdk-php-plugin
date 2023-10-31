<?php

namespace Transbank\Plugin\Service;

use Exception;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Webpay\Options;
use Transbank\Plugin\Model\ApiServiceLogDto;
use Transbank\Plugin\Model\ExecutionErrorLogDto;
use Transbank\Plugin\Model\ProductConfig;
use Transbank\Plugin\Service\TransactionService;
use Transbank\Plugin\Service\ApiServiceLogService;
use Transbank\Plugin\Service\ExecutionErrorLogService;
use Transbank\Plugin\Model\TransbankTransactionDetail;
use Transbank\Plugin\IRepository\IConfigRepository;

abstract class TbkProductService {

    protected $storeId;
    protected $logger;
    /**
     * @var ProductConfig
    */
    protected $config;
    protected $configRepository;
    protected $apiServiceLogService;
    protected $executionErrorLogService;
    protected $transactionService;
    protected $product;

    /**
    * @var Options
    */
    protected $options;

    public function __construct(ILogger $logger, IConfigRepository $configRepository, ApiServiceLogService $apiServiceLogService, ExecutionErrorLogService $executionErrorLogService, TransactionService $transactionService, $storeId) {
        $this->storeId = !is_null($storeId) ? (string)$storeId : '0';
        $this->logger = $logger;
        $this->configRepository = $configRepository;
        $this->apiServiceLogService = $apiServiceLogService;
        $this->executionErrorLogService = $executionErrorLogService;
        $this->transactionService = $transactionService;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }
    public function isActive()
    {
        return $this->config->isActive();
    }

    public function isProduction()
    {
        return $this->config->isProduction();
    }

    public function getOrderStatusAfterPayment()
    {
        return $this->config->getOrderStatusAfterPayment();
    }

    public function getCommerceCode()
    {
        return $this->options->getCommerceCode();
    }

    protected function getApikey()
    {
        return $this->options->getApiKey();
    }

    public function getEnvironment()
    {
        return $this->options->getIntegrationType();
    }

    public function getRandomNumber(){
        return substr(uniqid(), 0, 10);
    }

    protected function newExecutionErrorLog(){
        $eel = new ExecutionErrorLogDto();
        $eel->setStoreId($this->getStoreId());
        $eel->setProduct($this->product);
        $eel->setEnvironment($this->getEnvironment());
        $eel->setCommerceCode($this->getCommerceCode());
        return $eel;
    }

    protected function newApiServiceLogDto(){
        $eel = new ApiServiceLogDto();
        $eel->setStoreId($this->getStoreId());
        $eel->setProduct($this->product);
        $eel->setEnvironment($this->getEnvironment());
        $eel->setCommerceCode($this->getCommerceCode());
        return $eel;
    }

    protected function apiServiceLogToExecutionErrorLog(ApiServiceLogDto $asl){
        $eel = $this->newExecutionErrorLog();
        $eel->setService($asl->getService());
        $eel->setBuyOrder($asl->getBuyOrder());
        $eel->setData($asl->getInput());
        $eel->setError($asl->getError());
        $eel->setOriginalError($asl->getOriginalError());
        $eel->setCustomError($asl->getCustomError());
        return $eel;
    }

    protected function logErrorWithBuyOrder($buyOrder, $service, $input, $error, $originalError, $customError){
        $this->logger->logError("BUY_ORDER: {$buyOrder}, SERVICE: {$service}, INPUT: ".json_encode($input).", ERROR: {$error} , ORIGINAL_ERROR: {$originalError}, CUSTOM_ERROR: {$customError}");
    }

    protected function logInfoWithBuyOrder($buyOrder, $service, $message, $data){
        $this->logger->logInfo("BUY_ORDER: {$buyOrder}, SERVICE: {$service}, MESSAGE: {$message}, DATA: ".json_encode($data));
    }

    protected function afterExecutionTbkApi(ApiServiceLogDto $data)
    {
        $this->apiServiceLogService->create($data);
    }

    protected function errorExecutionTbkApi(ApiServiceLogDto $data)
    {
        $this->apiServiceLogService->createError($data);
    }

    protected function errorExecution(ExecutionErrorLogDto $data)
    {
        $this->executionErrorLogService->create($data);
    }

    /**
     * Este método retorna la transacción detallada por orderId.
     *
     * @param string $orderId
     * @return TransbankTransactionDetail
     */
    public function getTbkAuthorizedDetailByOrderId($orderId){
        return $this->transactionService->getTbkAuthorizedDetailByOrderId($orderId);
    }

    /**
     * Este método retorna la transacción detallada por buyOrder.
     *
     * @param string $orderId
     * @return TransbankTransactionDetail
     */
    public function getDetailByBuyOrder($buyOrder){
        return $this->transactionService->getDetailByBuyOrder($buyOrder);
    }

}
