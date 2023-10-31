<?php

namespace Transbank\Plugin\Service;

use Exception;
use Transbank\Plugin\Helpers\ArrayUtils;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\Helpers\InfoUtil;
use Transbank\Plugin\IRepository\IConfigRepository;
use Transbank\Plugin\Model\WebpayplusConfig;
use Transbank\Plugin\Model\OneclickConfig;
use Transbank\Plugin\Model\LogConfig;
use Transbank\Plugin\Helpers\StringUtils;
use Transbank\Webpay\Oneclick;
use Transbank\Webpay\WebpayPlus;
use Transbank\Utils\HttpClient;
use Transbank\Plugin\Helpers\TbkValidationUtil;
use Transbank\Plugin\Model\ContactDto;
use Transbank\Plugin\Model\PaginatedListRequest;
use Transbank\Plugin\Model\WebpayplusMallConfig;
use Transbank\Webpay\Options;

class TbkAdminService {
    
    /**
     * @var ILogger
     */
    private $logger;
    /**
     * @var IConfigRepository
     */
    private $configRepository;
    /**
     * @var ApiServiceLogService
     */
    private $apiServiceLogService;
    /**
     * @var ExecutionErrorLogService
     */
    private $executionErrorLogService;
    /**
     * @var TransactionService
     */
    private $transactionService;
    /**
     * @var InscriptionService
     */
    private $inscriptionService;
    /**
     * @var TbkWebpayplusService
     */
    private $tbkWebpayplusService;
    /**
     * @var TbkWebpayplusMallService
     */
    private $tbkWebpayplusMallService;
    /**
     * @var TbkOneclickService
     */
    private $tbkOneclickService;

    private $methodsWithDataParameter = [
        "getListTransaction",
        "getListInscription",
        "getListApiServiceLog",
        "getListExecutionErrorLog",
        "getLogDetail",
        "saveWebpayplusConfig",
        "saveWebpayplusMallConfig",
        "saveOneclickConfig",
        "saveLogConfig",
        "executeTbkStatus",
        "executeTbkRefund",
        "saveContact",
        "lastTransactionsByPeriod",
    ];

    private $methodsWithoutDataParameter = [
        "getLogInfo",
        "getDiagnosticoInfo",
        "getPhpInfo",
        "getEcommerceConfig",
        "getWebpayplusConfig",
        "resetWebpayplusConfig",
        "getWebpayplusMallConfig",
        "resetWebpayplusMallConfig",
        "getOneclickConfig",
        "resetOneclickConfig",
        "getLogConfig",
        "checkPlugin",
        "createTables",
        "getContact",
        "lastTransactionsOk",
        "pluginStatusSummary",
    ];

    public function __construct(ILogger $logger, IConfigRepository $configRepository,
        ApiServiceLogService $apiServiceLogService, ExecutionErrorLogService $executionErrorLogService,
        TransactionService $transactionService, InscriptionService $inscriptionService) {
        $this->logger = $logger;
        $this->configRepository = $configRepository;
        $this->apiServiceLogService = $apiServiceLogService;
        $this->executionErrorLogService = $executionErrorLogService;
        $this->transactionService = $transactionService;
        $this->inscriptionService = $inscriptionService;
        $this->tbkWebpayplusService = new TbkWebpayplusService($logger, $configRepository, $apiServiceLogService, $executionErrorLogService, $transactionService);
        $this->tbkWebpayplusMallService = new TbkWebpayplusMallService($logger, $configRepository, $apiServiceLogService, $executionErrorLogService, $transactionService);
        $this->tbkOneclickService = new TbkOneclickService($logger, $configRepository, $apiServiceLogService, $executionErrorLogService, $transactionService, $inscriptionService);
    }

    public function getListTransaction($params){
        return $this->transactionService->getList(new PaginatedListRequest($params));
    }

    public function getListInscription($params){
        return $this->inscriptionService->getList(new PaginatedListRequest($params));
    }

    public function getListApiServiceLog($params){
        return $this->apiServiceLogService->getList(new PaginatedListRequest($params));
    }

    public function getListExecutionErrorLog($params){
        return $this->executionErrorLogService->getList(new PaginatedListRequest($params));
    }

    public function getLogInfo(){
        return $this->logger->getInfo();
    }

    public function getLogDetail($params){
        return $this->logger->getLogDetail($params['filename']);
    }

    public function getDiagnosticoInfo(){
        return $this->configRepository->getSummary();
    }

    public function getPhpInfo(){
        return InfoUtil::getPhpInfo();
    }

    /**
     * @return WebpayplusConfig
    */
    public function getWebpayplusConfig(){
        return $this->configRepository->getWebpayplusConfig();
    }

    public function saveWebpayplusConfig($data){
      $product = new WebpayplusConfig($data);
      TbkValidationUtil::validateProductConfigThrowException($product);
      return $this->configRepository->saveWebpayplusConfig($product);
    }

    public function resetWebpayplusConfig(){
      $config = new WebpayplusConfig();
      $config->setActive(true);
      $config->setProduction(false);
      $config->setCommerceCode(WebpayPlus::DEFAULT_COMMERCE_CODE);
      $config->setApikey(WebpayPlus::DEFAULT_API_KEY);
      $config->setOrderStatusAfterPayment($this->configRepository->getDefaultOrderStatusAfterPayment());
      return $this->configRepository->saveWebpayplusConfig($config);
    }

    /**
     * @return WebpayplusMallConfig
    */
    public function getWebpayplusMallConfig(){
      return $this->configRepository->getWebpayplusMallConfig();
    }

    public function saveWebpayplusMallConfig($data){
      $product = new WebpayplusMallConfig($data);
      TbkValidationUtil::validateMallProductConfigThrowException($product);
      return $this->configRepository->saveWebpayplusMallConfig($product);
    }

    public function resetWebpayplusMallConfig(){
      $config = new WebpayplusMallConfig();
      $config->setActive(true);
      $config->setProduction(false);
      $config->setMultiStore(false);
      $config->setCommerceCode(WebpayPlus::DEFAULT_MALL_COMMERCE_CODE);
      $config->setChildCommerceCode(WebpayPlus::DEFAULT_MALL_CHILD_COMMERCE_CODE_1);
      $stores = $this->configRepository->getAllStores();
      if (!is_null($stores) && count($stores) > 1){
        $config->addItemChildCommerceCode($stores[0]->storeId, WebpayPlus::DEFAULT_MALL_CHILD_COMMERCE_CODE_1);
        $config->addItemChildCommerceCode($stores[1]->storeId, WebpayPlus::DEFAULT_MALL_CHILD_COMMERCE_CODE_2);
      }
      $config->setApikey(WebpayPlus::DEFAULT_API_KEY);
      $config->setOrderStatusAfterPayment($this->configRepository->getDefaultOrderStatusAfterPayment());
      return $this->configRepository->saveWebpayplusMallConfig($config);
    }

    public function getEcommerceConfig(){
      return [
        "ecommerceInstalationId" => "xxx",
        "timezone" => $this->configRepository->getTimezone(),
        "listOrderStatusAfterPayment" => $this->configRepository->getListOrderStatusAfterPayment(),
        "stores" => $this->configRepository->getAllStores()
      ];
    }

    /**
     * @return OneclickConfig
    */
    public function getOneclickConfig(){
        return $this->configRepository->getOneclickConfig();
    }

    public function saveOneclickConfig($data){
      $product = new OneclickConfig($data);
      TbkValidationUtil::validateMallProductConfigThrowException($product);
      return $this->configRepository->saveOneclickConfig($product);
    }

    public function resetOneclickConfig(){
      $config = new OneclickConfig();
      $config->setActive(false);
      $config->setProduction(false);
      $config->setMultiStore(false);
      $config->setCommerceCode(Oneclick::DEFAULT_COMMERCE_CODE);
      $config->setChildCommerceCode(Oneclick::DEFAULT_CHILD_COMMERCE_CODE_1);
      $stores = $this->configRepository->getAllStores();
      if (!is_null($stores) && count($stores) > 1){
        $config->addItemChildCommerceCode($stores[0]->storeId, WebpayPlus::DEFAULT_MALL_CHILD_COMMERCE_CODE_1);
        $config->addItemChildCommerceCode($stores[1]->storeId, WebpayPlus::DEFAULT_MALL_CHILD_COMMERCE_CODE_2);
      }
      $config->setApikey(Oneclick::DEFAULT_API_KEY);
      $config->setOrderStatusAfterPayment($this->configRepository->getDefaultOrderStatusAfterPayment());
      return $this->configRepository->saveOneclickConfig($config);
    }

    /**
     * @return LogConfig
    */
    public function getLogConfig(){
        return $this->configRepository->getLogConfig();
    }

    public function saveLogConfig($data){
        return $this->configRepository->saveLogConfig($data);
    }

    public function sendMetrics($phpVersion, $plugin, $pluginVersion, $ecommerceVersion, $ecommerceId, $product, $enviroment, $commerceCode, $meta) {
      $client = new HttpClient();
      $headers = ['Content-Type' => 'application/json'];
      $response = $client->request('POST','https://tbk-app-y8unz.ondigitalocean.app/records/newRecord', [
          'phpVersion' => $phpVersion,
          'plugin' => $plugin,
          'pluginVersion' => $pluginVersion,
          'ecommerceVersion' => $ecommerceVersion,
          'ecommerceId' => $ecommerceId,
          'product' => $product,
          'environment' => $enviroment,
          'commerceCode' => $commerceCode,
          'metadata' => json_encode($meta)
      ], $headers);
      return json_decode($response->getBody(), true);
    }

    public function executeTbkStatus($params){
      $buyOrder = ArrayUtils::getValue($params, 'buyOrder');
      if (StringUtils::isBlankOrNull($buyOrder)){
        throw new Exception('Debe enviar un buyOrder válido');
      }

      $tx = $this->transactionService->getByBuyOrder($buyOrder);
      $errorStatus = TbkValidationUtil::validateStatusDate($tx->getCreatedAt());
      if (StringUtils::isBlankOrNull($errorStatus)){
          throw new Exception($errorStatus);
      }

      if ($tx->isWebpayplus()){
        if ($tx->getCommerceCode() != $this->tbkWebpayplusService->getCommerceCode()){
          throw new Exception('La transacción no pertenece a la configuración WebpayPlus actual');
        }
        return $this->tbkWebpayplusService->status($tx->getToken());
      }
      if ($tx->isOneclick()){
        if ($tx->getCommerceCode() != $this->tbkOneclickService->getCommerceCode() || $tx->getChildCommerceCode() != $this->tbkOneclickService->getChildCommerceCode()){
          throw new Exception('La transacción no pertenece a la configuración Oneclick actual');
        }
        return $this->tbkOneclickService->status($buyOrder);
      }
      throw new Exception('Ocurrio un error ejecutando el status');
    }

    public function executeTbkRefund($params){
      $buyOrder = ArrayUtils::getValue($params, 'buyOrder');
      $amount = ArrayUtils::getValue($params, 'amount');
      if (StringUtils::isBlankOrNull($buyOrder)){
        throw new Exception('Debe enviar un buyOrder válido');
      }
      if ($amount <= 0){
        throw new Exception('Debe enviar un monto válido');
      }
      
      $tx = $this->transactionService->getByBuyOrder($buyOrder);
      if ($amount > ($tx->getAmount() - $tx->getRefundAmount())){
        throw new Exception('El monto para el refund es mayor al disponible');
      }

      $errorRefund = TbkValidationUtil::validateRefundDate($tx->getCreatedAt());
      if (StringUtils::isBlankOrNull($errorRefund)){
          throw new Exception($errorRefund);
      }

      if ($tx->isWebpayplus()){
        if ($tx->getCommerceCode() != $this->tbkWebpayplusService->getCommerceCode()){
          throw new Exception('La transacción no pertenece a la configuración WebpayPlus actual');
        }
        return $this->tbkWebpayplusService->refundTransactionByBuyOrder($buyOrder, $amount)['refundResponse'];
      }
      if ($tx->isWebpayplusMall()){
        if ($tx->getCommerceCode() != $this->tbkWebpayplusMallService->getCommerceCode() || $tx->getChildCommerceCode() != $this->tbkWebpayplusMallService->getChildCommerceCode()){
          throw new Exception('La transacción no pertenece a la configuración WebpayPlusMall actual');
        }
        return $this->tbkWebpayplusMallService->refundTransactionByBuyOrder($buyOrder, $amount)['refundResponse'];
      }
      if ($tx->isOneclick()){
        if ($tx->getCommerceCode() != $this->tbkOneclickService->getCommerceCode() || $tx->getChildCommerceCode() != $this->tbkOneclickService->getChildCommerceCode()){
          throw new Exception('La transacción no pertenece a la configuración Oneclick actual');
        }
        return $this->tbkOneclickService->refundTransactionByBuyOrder($buyOrder, $amount)['refundResponse'];
      }
      throw new Exception('Ocurrio un error ejecutando el refund');
    }

    public function loadDefaultConfig(){
      $webpayplusConfig = $this->configRepository->getPreviousWebpayplusConfigIfProduction();
      if ($webpayplusConfig!==null){
        $this->configRepository->saveWebpayplusConfig($webpayplusConfig);
      }
      else{
        $this->resetWebpayplusConfig();
      }
      $webpayplusMallConfig = $this->configRepository->getPreviousWebpayplusMallConfigIfProduction();
      if ($webpayplusMallConfig!==null){
        $this->configRepository->saveWebpayplusMallConfig($webpayplusConfig);
      }
      else{
        $this->resetWebpayplusMallConfig();
      }
      $oneclickConfig = $this->configRepository->getPreviousOneclickConfigIfProduction();
      if ($oneclickConfig!==null){
        $this->configRepository->saveOneclickConfig($oneclickConfig);
      }else{
        $this->resetOneclickConfig();
      }
    }

    public function createTables(){
      $errors = [];
      $errors["transactionTable"] = $this->transactionService->createTable();
      $errors["inscriptionTable"] = $this->inscriptionService->createTable();
      $errors["apiServiceLogTable"] = $this->apiServiceLogService->createTable();
      $errors["executionErrorLogTable"] = $this->executionErrorLogService->createTable();
      return TbkValidationUtil::proccessArrayErrors($errors);
    }

    public function deleteTables(){
      $errors = [];
      $errors["transactionTable"] = $this->transactionService->deleteTable();
      $errors["inscriptionTable"] = $this->inscriptionService->deleteTable();
      $errors["apiServiceLogTable"] = $this->apiServiceLogService->deleteTable();
      $errors["executionErrorLogTable"] = $this->executionErrorLogService->deleteTable();
      return TbkValidationUtil::proccessArrayErrors($errors);
    }

    public function checkTables(){
      $errors = [];
      $errors["transactionTable"] = $this->transactionService->checkTable();
      $errors["inscriptionTable"] = $this->inscriptionService->checkTable();
      $errors["apiServiceLogTable"] = $this->apiServiceLogService->checkTable();
      $errors["executionErrorLogTable"] = $this->executionErrorLogService->checkTable();
      return TbkValidationUtil::proccessArrayErrors($errors);
    }

    private function checkPluginWithoutTest(){
      $errors = [];
      $errors = array_merge($errors, $this->checkTables());
      if ($errors["ok"] !== true){
        /* Ya no se sigue con las validaciones porque sin tablas salen muchos errores */
        return $errors;
      }
      $errors["webpayplus"] = $this->tbkWebpayplusService->checkConfiguration();
      $errors["webpayplusmall"] = $this->tbkWebpayplusMallService->checkConfiguration();
      $errors["oneclick"] = $this->tbkOneclickService->checkConfiguration();
      $errors["integrationUrl"] = TbkValidationUtil::checkAccessibleIntegrationUrl();
      $errors["productionUrl"] = TbkValidationUtil::checkAccessibleProductionUrl();
      return TbkValidationUtil::proccessArrayErrors($errors);
    }

    public function checkPlugin(){
      $errors = $this->checkPluginWithoutTest();
      if ($errors["ok"] !== true){
        return $errors;
      }
      $errors["webpayplusCreateTransaction"] = $this->tbkWebpayplusService->testCreateTransaction();
      $errors["oneclickStartInscription"] = $this->tbkOneclickService->testStartInscription();
      return TbkValidationUtil::proccessArrayErrors($errors);
    }

    public function getContact(){
      return $this->configRepository->getContact();
    }

    public function saveContact($data){
        return $this->configRepository->saveContact(new ContactDto($data));
    }

    private function existProductActive(){
      return $this->tbkWebpayplusService->isActive() ||
        $this->tbkWebpayplusMallService->isActive() ||
        $this->tbkOneclickService->isActive();
    }

    private function existProductProduction(){
      return $this->tbkWebpayplusService->isProduction() ||
        $this->tbkWebpayplusMallService->isActive() ||
        $this->tbkOneclickService->isProduction();
    }

    public function lastTransactionsOk(){
      if (!$this->existProductActive()){
        return [ "active" => false ];
      }
      $enviroment = Options::ENVIRONMENT_INTEGRATION;
      $production = false;
      if ($this->existProductProduction()){
        $enviroment = Options::ENVIRONMENT_PRODUCTION;
        $production = true;
      }
      $data = $this->transactionService->lastTransactionsOk($enviroment, 10);
      $data["production"] = $production;
      $data["active"] = true;
      return $data;
    }

    public function lastTransactionsByPeriod($params){
      $period = $params['period'];
      if (!$this->existProductActive()){
        return [ "active" => false ];
      }
      $enviroment = Options::ENVIRONMENT_INTEGRATION;
      $production = false;
      if ($this->existProductProduction()){
        $enviroment = Options::ENVIRONMENT_PRODUCTION;
        $production = true;
      }
      $length = 5;
      switch ($period) {
        case 1:
          $data = $this->transactionService->lastTransactionsByDay($enviroment, $length);
          break;
        case 2:
          $data = $this->transactionService->lastTransactionsByWeek($enviroment, $length);
          break;
        case 3:
          $data = $this->transactionService->lastTransactionsByMonth($enviroment, $length);
          break;
        default:
          $data = null;
      }
      return [
        "active" => true,
        "data" => $data,
        "length" => $length,
        "production" => $production
      ];
    }

    public function getDateLastTransactionOk(){
      if (!$this->existProductActive()){
        return [ "active" => false ];
      }
      $enviroment = Options::ENVIRONMENT_INTEGRATION;
      $production = false;
      if ($this->existProductProduction()){
        $enviroment = Options::ENVIRONMENT_PRODUCTION;
        $production = true;
      }
      return [
        "active" => true,
        "production" => $production,
        "last" => $this->transactionService->getDateLastTransactionOk($enviroment)
      ];
    }

    public function pluginStatusSummary(){
      $result = [];

      $errors = $this->checkPluginWithoutTest();
      $result["plugin"] = [
        "ok" => $errors["ok"],
        "warning" => null //"Versión nueva disponible"
      ];

      $webpayplusConfig = $this->getWebpayplusConfig();
      $result["webpayplus"] = [
        "ok" => is_null($errors["webpayplus"]),
        "error" => $errors["webpayplus"],
        "production" => $webpayplusConfig->isProduction(),
        "active" => $webpayplusConfig->isActive()
      ];

      $webpayplusMallConfig = $this->getWebpayplusMallConfig();
      $result["webpayplusmall"] = [
        "ok" => is_null($errors["webpayplusmall"]),
        "error" => $errors["webpayplusmall"],
        "production" => $webpayplusMallConfig->isProduction(),
        "active" => $webpayplusMallConfig->isActive()
      ];

      $oneclickConfig = $this->getOneclickConfig();
      $result["oneclick"] = [
        "ok" => is_null($errors["oneclick"]),
        "error" => $errors["oneclick"],
        "production" => $oneclickConfig->isProduction(),
        "active" => $oneclickConfig->isActive()
      ];

      $contact = $this->configRepository->getContact();
      if (StringUtils::isNotBlankOrNull($contact->getCommerceEmail()) || StringUtils::isNotBlankOrNull($contact->getIntegratorEmail())){
        $result["contact"] = [
          "ok" => true
        ];
      }
      else {
        $result["contact"] = [
          "ok" => false,
          "warning" => "Falta completar"
        ];
      }
      
      $result["transaction"] = $this->getDateLastTransactionOk();
      return $result;
    }

    public function execute($data){
        $method = $data['method'];
        try {
          if (in_array($method, $this->methodsWithDataParameter)) {
            return $this->$method($data);
          } elseif (in_array($method, $this->methodsWithoutDataParameter)) {
              return $this->$method();
          } else {
              return [];
          }
        } catch (Exception $e) {
          $jsonData = json_encode($data);
          $errorMessage = "ERROR: ocurrió un error al ejecutar 'TbkAdminService.execute', DATA: {$jsonData}, ORIGINAL_ERROR: {$e->getMessage()}";
          $this->logger->logError($errorMessage);
          throw new Exception($e->getMessage());
        }
    }

}

