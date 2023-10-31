<?php

namespace Transbank\Plugin\Service;

use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\IRepository\ITableRepository;

abstract class BaseTableService {

    protected $logger;
    protected $tableRepository;

    public function __construct(ILogger $logger, ITableRepository $tableRepository) {
        $this->logger = $logger;
        $this->tableRepository = $tableRepository;
    }

    abstract protected function createTableDto($data);

    public function createTable(){
        return $this->tableRepository->createTable();
    }

    public function deleteTable(){
        return $this->tableRepository->deleteTable();
    }

    public function checkTable(){
        return $this->tableRepository->checkTable();
    }

    public function getList($params){
        $result = [];
        $paginated = $this->tableRepository->getList($params);
        foreach ($paginated->getData() as $item) {
            $result[] = $this->createTableDto($item);
        }
        $paginated->setData($result);
        return $paginated;
    }

    protected function logInfoWithBuyOrder($buyOrder, $service, $message, $data){
        $this->logger->logInfo("BUY_ORDER: {$buyOrder}, SERVICE: {$service}, MESSAGE: {$message}, DATA: ".json_encode($data));
    }
}
