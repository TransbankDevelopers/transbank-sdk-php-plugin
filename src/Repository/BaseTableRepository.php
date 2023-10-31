<?php

namespace Transbank\Plugin\Repository;

use Exception;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\Helpers\StringUtils;
use Transbank\Plugin\IRepository\ITableRepository;
use Transbank\Plugin\IRepository\IUtilRepository;
use Transbank\Plugin\Model\PaginatedList;
use Transbank\Plugin\Model\PaginatedListRequest;
use Transbank\Plugin\Model\TbkTable;

abstract class BaseTableRepository implements ITableRepository {
    
    protected $logger;
    protected $utilRepository;

    public function __construct(ILogger $logger, IUtilRepository $utilRepository) {
        $this->logger = $logger;
        $this->utilRepository = $utilRepository;
    }

    abstract public function getTableName();
    /**
     * @return TbkTable
    */
    abstract public function getTbkTable();

    protected function executeSql($sql){
        $result = $this->utilRepository->executeSql($sql);
        if (is_null($result) || $result === false || empty($result)){
            return null;
        }
        return $result;
    }

    protected function getRow($sql){
        $result = $this->utilRepository->getRow($sql);
        if (is_null($result) || $result === false || empty($result)){
            return null;
        }
        return $result;
    }

    protected function getValue($sql){
        return $this->utilRepository->getValue($sql);
    }

    protected function getSqlInsert($data){
        $tbkTable = $this->getTbkTable();
        $cols = [];
        $values = [];
        foreach (get_object_vars($data) as $property => $value) {
            if (StringUtils::isBlankOrNull($value)){
                continue;
            }
            $colName = StringUtils::camelToSnake($property);
            $colValue = $this->utilRepository->sanitizeValue($value);
            $colDef = $tbkTable->getColumnByName($colName);
            $cols[] = "`{$colName}`" ;
            $values[] = $colDef->useQuote() ? "'{$colValue}'" : $colValue;
        }

        $colsStr = implode(', ', $cols);
        $valuesStr = implode(', ', $values);
        return "INSERT INTO {$this->getTableName()} ({$colsStr}) VALUES ({$valuesStr});";
    }

    protected function getSqlUpdate($data){
        $tbkTable = $this->getTbkTable();
        $whereSql = null;
        $sets = [];
        foreach (get_object_vars($data) as $property => $value) {
            if (StringUtils::isBlankOrNull($value)){
                continue;
            }
            if ($property === $tbkTable->getColumnId()->getName()){
                $colValue = $this->utilRepository->sanitizeValue($value);
                $whereSql = "`{$tbkTable->getColumnId()->getName()}` = {$colValue}";
                continue;
            }
            $colName = StringUtils::camelToSnake($property);
            $colValue = $this->utilRepository->sanitizeValue($value);
            $colDef = $tbkTable->getColumnByName($colName);
            $colValue = $colDef->useQuote() ? "'{$colValue}'" : $colValue;
            $sets[] = "`{$colName}` = {$colValue}";
        }

        $setsStr = implode(', ', $sets);
        return "UPDATE {$this->getTableName()} SET {$setsStr} WHERE {$whereSql} ;";
    }

    public function getSqlCreateTable() {
        return $this->getTbkTable()->getSqlCreateTable();
    }

    private function getSqlOrder($sql, PaginatedListRequest $params){
        $sortField = $this->utilRepository->sanitizeValue($params->getSortField());
        if ($sortField != null){
            if ($params->getSortOrder() == -1){
                $sql = "$sql ORDER BY `{$sortField}` DESC";
            }
            else{
                $sql = "$sql ORDER BY `{$sortField}` ASC";
            }
        }
        return $sql;
    }

    private function getSqlWhere($sql, PaginatedListRequest $params){
        if ($params->getFilters() != null){
            $sql = "$sql WHERE 1=1 ";
            foreach ($params->getFilters() as $filter) {
                $field = $this->utilRepository->sanitizeValue($filter->getField());
                $value = $this->utilRepository->sanitizeValue($filter->getValue());
                if ($filter->getMode() === 'contains'){
                    $sql = "$sql AND `{$field}` LIKE '%{$value}%'";
                }
                elseif ($filter->getMode() === 'equals'){
                    $sql = "$sql AND `{$field}` = '{$value}'";
                }
            }
        }
        return $sql;
    }

    private function getSqlPaginated($sql, PaginatedListRequest $params){
        $pageSize = $this->utilRepository->sanitizeValue($params->getPageSize());
        $firstRow = $this->utilRepository->sanitizeValue($params->getFirstRow());
        return "$sql LIMIT {$pageSize} OFFSET {$firstRow}";
    }

    private function getTotalSql($sql){
        return "SELECT COUNT(1) FROM ($sql) T";
    }

    private function getPaginatedList($sqlBase, $params){
        $sqlWhere = $this->getSqlWhere($sqlBase, $params);
        $sqlOrder = $this->getSqlOrder($sqlWhere, $params);
        $total = $this->getTotal($sqlWhere);
        if ($total > 0){
            $sqlPaginated = $this->getSqlPaginated($sqlOrder, $params);
            $result = $this->utilRepository->executeSql($sqlPaginated);
            return new PaginatedList($result, $total);
        }
        return new PaginatedList([], 0);
    }

    private function getTotal($sql){
        $sql = $this->getTotalSql($sql);
        return $this->utilRepository->getValue($sql);
    }

    public function getList($params){
        $tableName = $this->getTableName();
        $sql = "SELECT * FROM $tableName";
        return $this->getPaginatedList($sql, $params);
    }

    public function createTable(){
        $tableName = $this->getTableName();
        try {
            $sql = $this->getSqlCreateTable();
            $engine = $this->utilRepository->getDbEngine();
            $aditional = $this->utilRepository->getDbAditionalOptions();
            $sql = "{$sql} ENGINE={$engine} {$aditional};";
            $this->utilRepository->executeWriteSql($sql);
            return $this->checkTable();
        } catch (Exception $e){
            $errorMessage = "Ocurrio un error creando la tabla: '{$tableName}', ERROR: {$e->getMessage()}";
            $this->logger->logError($errorMessage);
            return $errorMessage;
        }
    }

    public function deleteTable(){
        $tableName = $this->getTableName();
        try {
            $sql = "DROP TABLE IF EXISTS `$tableName`";
            return $this->utilRepository->executeWriteSql($sql);
        } catch (Exception $e){
            $errorMessage = "Ocurrio un error eliminando la tabla: '{$tableName}', ERROR: {$e->getMessage()}";
            $this->logger->logError($errorMessage);
            return $errorMessage;
        }
    }

    public function checkTable(){
        $tableName = $this->getTableName();
        $sql = "SELECT COUNT(1) as `total` FROM $tableName";
        try {
            $value = $this->utilRepository->getValue($sql);
            if ($value!='' && $value!=null && $value >= 0){
                return null;
            }
            return "La tabla '{$tableName}' no existe o no se cuenta con permisos para acceder a ella";
        } catch (Exception $e) {
            return "Se produjo un error al comprobar la existencia de la tabla '{$tableName}', excepción: {$e->getMessage()}";
        }
    }

}
