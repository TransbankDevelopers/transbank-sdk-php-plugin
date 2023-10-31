<?php

namespace Transbank\Plugin\Repository;

use Exception;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Helpers\TbkDatabaseConstants;
use Transbank\Plugin\IRepository\IUtilRepository;
use Transbank\Plugin\Model\ColumnTable;
use Transbank\Plugin\Model\TbkTable;
use Transbank\Plugin\Model\TransbankInscriptionDto;

abstract class BaseInscriptionRepository extends BaseTableRepository {
    protected $logger;
    protected $utilRepository;

    public function __construct(ILogger $logger, IUtilRepository $utilRepository) {
        $this->logger = $logger;
        $this->utilRepository = $utilRepository;
    }

    public function getTableName(){
        return $this->utilRepository->getDbPrefix().TbkConstants::INSCRIPTIONS_TABLE_NAME;
    }

    public function getTbkTable(){
        $table = new TbkTable($this->getTableName(),
            new ColumnTable("id", TbkDatabaseConstants::COLUMN_TYPE_BIGINT20, true)
        );
        $table->addColumn("store_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("token", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR100, true);
        $table->addColumn("username", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("email", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR100, true);
        $table->addColumn("user_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("tbk_user", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("ecommerce_token_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR100);
        $table->addColumn("order_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("pay_after_inscription", TbkDatabaseConstants::COLUMN_TYPE_TINYINT1, true, "0");
        $table->addColumn("response_code", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("authorization_code", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("card_type", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("card_number", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("from", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("status", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("environment", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("commerce_code", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("transbank_response", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("error", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR255);
        $table->addColumn("original_error", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("custom_error", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("created_at", TbkDatabaseConstants::COLUMN_TYPE_TIMESTAMP, true, "NOW()");
        $table->addColumn("updated_at", TbkDatabaseConstants::COLUMN_TYPE_TIMESTAMP, true, "NOW()");
        return $table;
    }

    public function create(TransbankInscriptionDto $data){
        $sql = $this->getSqlInsert($data);
        return $this->utilRepository->executeWriteSql($sql);
    }

    public function update(TransbankInscriptionDto $data){
        $sql = $this->getSqlUpdate($data);
        return $this->utilRepository->executeWriteSql($sql);
    }

    public function getByToken($token){
        $tableName = $this->getTableName();
        $token = $this->utilRepository->sanitizeValue($token);
        $sql = "SELECT * FROM $tableName WHERE `token` = '$token'";
        return $this->rowToTransbankInscriptionDto($sql);
    }

    public function getByUsername($username){
        $tableName = $this->getTableName();
        $username = $this->utilRepository->sanitizeValue($username);
        $status = TbkConstants::INSCRIPTIONS_STATUS_COMPLETED;
        $sql = "SELECT * FROM $tableName WHERE `status` = '$status' AND `username` = '$username'";
        return $this->rowToTransbankInscriptionDto($sql);
    }

    public function getListByUserId($userId){
        $tableName = $this->getTableName();
        $userId = $this->utilRepository->sanitizeValue($userId);
        $status = TbkConstants::INSCRIPTIONS_STATUS_COMPLETED;
        $sql = "SELECT * FROM $tableName WHERE `status` = '$status' AND `user_id` = '$userId'";
        $r = $this->executeSql($sql);
        if (!isset($r)){
            return [];
        }
        return $r;
    }

    public function getCountByUserId($userId){
        $tableName = $this->getTableName();
        $userId = $this->utilRepository->sanitizeValue($userId);
        $status = TbkConstants::INSCRIPTIONS_STATUS_COMPLETED;
        $sql = "SELECT count(1) FROM $tableName WHERE `status` = '$status' AND `user_id` = '$userId'";
        return $this->getValue($sql);
    }

    private function rowToTransbankInscriptionDto($sql){
        $result = $this->getRow($sql);
        if ($result === false) {
            return null;
        }
        return new TransbankInscriptionDto($result);
    }

}
