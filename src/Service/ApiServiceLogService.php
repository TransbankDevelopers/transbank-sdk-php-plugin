<?php

namespace Transbank\Plugin\Service;

use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\IRepository\IApiServiceLogRepository;
use Transbank\Plugin\Model\ApiServiceLogDto;
use Transbank\Plugin\Model\ExecutionErrorLogDto;
use Transbank\Plugin\Service\ExecutionErrorLogService;

/**
 * Esta clase tiene el propósito de encapsular la lógica de negocio
 * referente a la persistencia de las invocaciones del api de Transbank
 *
 * @version 1.0
 */
final class ApiServiceLogService extends BaseTableService {

    private $apiServiceLogRepository;
    private $executionErrorLogService;

    public function __construct(ILogger $logger, IApiServiceLogRepository $apiServiceLogRepository, ExecutionErrorLogService $executionErrorLogService) {
        parent::__construct($logger, $apiServiceLogRepository);
        $this->apiServiceLogRepository = $apiServiceLogRepository;
        $this->executionErrorLogService = $executionErrorLogService;
    }

    protected function createTableDto($data){
        return new ApiServiceLogDto($data);
    }

    public function create(ApiServiceLogDto $data)
    {
        $this->logger->logInfo("BUY_ORDER: {$data->getBuyOrder()}, SERVICE: {$data->getService()}, INPUT: {$data->getInput()}, RESPONSE: {$data->getResponse()}");
        $this->apiServiceLogRepository->create($data);
    }

    public function createError(ApiServiceLogDto $data)
    {
        $this->logger->logError("BUY_ORDER: {$data->getBuyOrder()}, SERVICE: {$data->getService()}, INPUT: {$data->getInput()}, ERROR: {$data->getError()} , ORIGINAL_ERROR: {$data->getOriginalError()}, CUSTOM_ERROR: {$data->getCustomError()}");
        $this->apiServiceLogRepository->createError($data);
        $eel = new ExecutionErrorLogDto();
        $eel->setStoreId($data->getStoreId());
        $eel->setProduct($data->getProduct());
        $eel->setEnvironment($data->getEnvironment());
        $eel->setCommerceCode($data->getCommerceCode());
        $eel->setService($data->getService());
        $eel->setBuyOrder($data->getBuyOrder());
        $eel->setData($data->getInput());
        $eel->setError($data->getError());
        $eel->setOriginalError($data->getOriginalError());
        $eel->setCustomError($data->getCustomError());
        $this->executionErrorLogService->createWhitoutLog($eel);
    }

    public function createOnlyError(ApiServiceLogDto $data)
    {
        $this->apiServiceLogRepository->createError($data);
    }
}
