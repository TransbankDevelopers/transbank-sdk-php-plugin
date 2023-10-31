<?php

namespace Transbank\Plugin\Service;

use Exception;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\IRepository\IExecutionErrorLogRepository;
use Transbank\Plugin\Model\ExecutionErrorLogDto;

/**
 * Esta clase tiene el propósito de encapsular la lógica de negocio
 * referente a la persistencia de los errores producidos durante la ejecución de los pagos usando el plugin
 *
 * @version 1.0
 */
final class ExecutionErrorLogService extends BaseTableService {

    private $executionErrorLogRepository;

    public function __construct(ILogger $logger, IExecutionErrorLogRepository $executionErrorLogRepository) {
        parent::__construct($logger, $executionErrorLogRepository);
        $this->executionErrorLogRepository = $executionErrorLogRepository;
    }

    protected function createTableDto($data){
        return new ExecutionErrorLogDto($data);
    }

    public function create(ExecutionErrorLogDto $data)
    {
        $this->logger->logError("BUY_ORDER: {$data->getBuyOrder()}, SERVICE: {$data->getService()}, DATA: {$data->getData()}, ERROR: {$data->getError()} , ORIGINAL_ERROR: {$data->getOriginalError()}, CUSTOM_ERROR: {$data->getCustomError()}");
        $this->executionErrorLogRepository->create($data);
    }

    public function createWhitoutLog(ExecutionErrorLogDto $data)
    {
        $this->executionErrorLogRepository->create($data);
    }

}
