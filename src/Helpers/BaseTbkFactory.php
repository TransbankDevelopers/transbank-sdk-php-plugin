<?php

namespace Transbank\Plugin\Helpers;

use Transbank\Plugin\IRepository\IApiServiceLogRepository;
use Transbank\Plugin\IRepository\IConfigRepository;
use Transbank\Plugin\IRepository\IExecutionErrorLogRepository;
use Transbank\Plugin\IRepository\IInscriptionRepository;
use Transbank\Plugin\IRepository\ITransactionRepository;
use Transbank\Plugin\IRepository\IUtilRepository;
use Transbank\Plugin\Service\ApiServiceLogService;
use Transbank\Plugin\Service\ExecutionErrorLogService;
use Transbank\Plugin\Service\InscriptionService;
use Transbank\Plugin\Service\TransactionService;
use Transbank\Plugin\Service\TbkWebpayplusService;
use Transbank\Plugin\Service\TbkOneclickService;
use Transbank\Plugin\Service\TbkAdminService;
use Transbank\Plugin\Service\TbkWebpayplusMallService;

abstract class BaseTbkFactory {

    /**
     * @var ILogger
     */
    private $logger;
    /**
     * @var IConfigRepository
     */
    private $configRepository;
    /**
     * @var IUtilRepository
     */
    private $utilRepository;
    /**
     * @var IApiServiceLogRepository
     */
    private $apiServiceLogRepository;
    /**
     * @var IExecutionErrorLogRepository
     */
    private $executionErrorLogRepository;
    /**
     * @var IInscriptionRepository
     */
    private $inscriptionRepository;
    /**
     * @var ITransactionRepository
     */
    private $transactionRepository;
    /**
     * @var ExecutionErrorLogService
     */
    private $executionErrorLogService;
    /**
     * @var ApiServiceLogService
     */
    private $apiServiceLogService;
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
    /**
     * @var TbkAdminService
     */
    private $tbkAdminService;
        
    public function __construct(){

    }

    abstract public function createUtilRepository();
    abstract public function createConfigRepository();
    abstract public function createExecutionErrorLogRepository($logger, $utilRepository);
    abstract public function createApiServiceLogRepository($logger, $utilRepository);
    abstract public function createInscriptionRepository($logger, $utilRepository);
    abstract public function createTransactionRepository($logger, $utilRepository);

    public function newBaseWebpayplusService()
    {
        $this->configRepository = $this->createConfigRepository();
        $this->logger = new PluginLogger($this->configRepository->getLogConfig());
        $this->utilRepository = $this->createUtilRepository();
        $this->executionErrorLogRepository = $this->createExecutionErrorLogRepository($this->logger,
            $this->utilRepository);
        $this->apiServiceLogRepository = $this->createApiServiceLogRepository($this->logger,
            $this->utilRepository);
        $this->transactionRepository = $this->createTransactionRepository($this->logger,
            $this->utilRepository);
        $this->executionErrorLogService = new ExecutionErrorLogService($this->logger,
            $this->executionErrorLogRepository);
        $this->apiServiceLogService = new ApiServiceLogService($this->logger,
            $this->apiServiceLogRepository, $this->executionErrorLogService);
        $this->transactionService = new TransactionService($this->logger,
            $this->configRepository, $this->transactionRepository, $this->executionErrorLogService);
    }

    public function newTbkWebpayplusService($storeId = '0')
    {
        $this->newBaseWebpayplusService();
        $this->tbkWebpayplusService = new TbkWebpayplusService($this->logger,
            $this->configRepository, $this->apiServiceLogService,
            $this->executionErrorLogService, $this->transactionService, $storeId);
        return $this->tbkWebpayplusService;
    }

    public function newTbkWebpayplusMallService($storeId = '0')
    {
        $this->newBaseWebpayplusService();
        $this->tbkWebpayplusMallService = new TbkWebpayplusMallService($this->logger,
            $this->configRepository, $this->apiServiceLogService,
            $this->executionErrorLogService, $this->transactionService, $storeId);
        return $this->tbkWebpayplusMallService;
    }

    public function newTbkOneclickService($storeId = '0')
    {
        $this->configRepository = $this->createConfigRepository();
        $this->logger = new PluginLogger($this->configRepository->getLogConfig());
        $this->utilRepository = $this->createUtilRepository();
        $this->executionErrorLogRepository = $this->createExecutionErrorLogRepository($this->logger,
            $this->utilRepository);
        $this->apiServiceLogRepository = $this->createApiServiceLogRepository($this->logger,
            $this->utilRepository);
        $this->transactionRepository = $this->createTransactionRepository($this->logger,
            $this->utilRepository);
        $this->inscriptionRepository = $this->createInscriptionRepository($this->logger,
            $this->utilRepository);
        $this->executionErrorLogService = new ExecutionErrorLogService($this->logger,
            $this->executionErrorLogRepository);
        $this->apiServiceLogService = new ApiServiceLogService($this->logger,
            $this->apiServiceLogRepository, $this->executionErrorLogService);
        $this->transactionService = new TransactionService($this->logger,
            $this->configRepository, $this->transactionRepository, $this->executionErrorLogService);
        $this->inscriptionService = new InscriptionService($this->logger,
            $this->inscriptionRepository, $this->executionErrorLogService);
        $this->tbkOneclickService = new TbkOneclickService($this->logger, $this->configRepository,
            $this->apiServiceLogService, $this->executionErrorLogService,
            $this->transactionService, $this->inscriptionService, $storeId);
        return $this->tbkOneclickService;
    }

    public function newTbkAdminService()
    {
        $this->configRepository = $this->createConfigRepository();
        $this->logger = new PluginLogger($this->configRepository->getLogConfig());
        $this->utilRepository = $this->createUtilRepository();
        $this->executionErrorLogRepository = $this->createExecutionErrorLogRepository($this->logger,
            $this->utilRepository);
        $this->apiServiceLogRepository = $this->createApiServiceLogRepository($this->logger,
            $this->utilRepository);
        $this->transactionRepository = $this->createTransactionRepository($this->logger,
            $this->utilRepository);
        $this->inscriptionRepository = $this->createInscriptionRepository($this->logger,
            $this->utilRepository);
        $this->executionErrorLogService = new ExecutionErrorLogService($this->logger,
            $this->executionErrorLogRepository);
        $this->apiServiceLogService = new ApiServiceLogService($this->logger,
            $this->apiServiceLogRepository, $this->executionErrorLogService);
        $this->transactionService = new TransactionService($this->logger,
            $this->configRepository, $this->transactionRepository, $this->executionErrorLogService);
        $this->inscriptionService = new InscriptionService($this->logger,
            $this->inscriptionRepository, $this->executionErrorLogService);
        $this->tbkAdminService = new TbkAdminService($this->logger,
            $this->configRepository, $this->apiServiceLogService, $this->executionErrorLogService,
            $this->transactionService, $this->inscriptionService);
        return $this->tbkAdminService;
    }

    public function newTransactionService()
    {
        $this->configRepository = $this->createConfigRepository();
        $this->logger = new PluginLogger($this->configRepository->getLogConfig());
        $this->utilRepository = $this->createUtilRepository();
        $this->executionErrorLogRepository = $this->createExecutionErrorLogRepository($this->logger,
            $this->utilRepository);
        $this->apiServiceLogRepository = $this->createApiServiceLogRepository($this->logger,
            $this->utilRepository);
        $this->transactionRepository = $this->createTransactionRepository($this->logger,
            $this->utilRepository);
        $this->executionErrorLogService = new ExecutionErrorLogService($this->logger,
            $this->executionErrorLogRepository);
        $this->apiServiceLogService = new ApiServiceLogService($this->logger,
            $this->apiServiceLogRepository, $this->executionErrorLogService);
        $this->transactionService = new TransactionService($this->logger,
            $this->configRepository, $this->transactionRepository, $this->executionErrorLogService);
        return $this->transactionService;
    }

    public function newLogger()
    {
        $this->configRepository = $this->createConfigRepository();
        $this->logger = new PluginLogger($this->configRepository->getLogConfig());
        return $this->logger;
    }
}

