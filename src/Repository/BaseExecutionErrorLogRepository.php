<?php

namespace Transbank\Plugin\Repository;

use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Helpers\TbkDatabaseConstants;
use Transbank\Plugin\Model\ColumnTable;
use Transbank\Plugin\Model\ExecutionErrorLogDto;
use Transbank\Plugin\Model\TbkTable;

abstract class BaseExecutionErrorLogRepository extends BaseTableRepository {

    public function getTableName(){
        return $this->utilRepository->getDbPrefix().TbkConstants::EXECUTION_ERROR_LOG_TABLE_NAME;
    }

    public function getTbkTable() {
        $table = new TbkTable($this->getTableName(),
            new ColumnTable("id", TbkDatabaseConstants::COLUMN_TYPE_BIGINT20, true)
        );
        $table->addColumn("store_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("buy_order", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("service", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("product", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("environment", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("commerce_code", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("data", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("error", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR255);
        $table->addColumn("original_error", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("custom_error", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("created_at", TbkDatabaseConstants::COLUMN_TYPE_TIMESTAMP, true, "NOW()");
        return $table;
    }

    public function create(ExecutionErrorLogDto $data){
        $sql = $this->getSqlInsert($data);
        return $this->utilRepository->executeWriteSql($sql);
    }
}
