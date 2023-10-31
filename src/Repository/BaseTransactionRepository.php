<?php

namespace Transbank\Plugin\Repository;

use Exception;
use Transbank\Plugin\Helpers\ILogger;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Helpers\TbkDatabaseConstants;
use Transbank\Plugin\IRepository\IUtilRepository;
use Transbank\Plugin\Model\ColumnTable;
use Transbank\Plugin\Model\TbkTable;
use Transbank\Plugin\Model\TransbankTransactionDto;

abstract class BaseTransactionRepository extends BaseTableRepository {
    protected $logger;
    protected $utilRepository;

    public function __construct(ILogger $logger, IUtilRepository $utilRepository) {
        $this->logger = $logger;
        $this->utilRepository = $utilRepository;
    }

    public function getTableName(){
        return $this->utilRepository->getDbPrefix().TbkConstants::TRANSACTION_TABLE_NAME;
    }

    public function getTbkTable() {
        $table = new TbkTable($this->getTableName(),
            new ColumnTable("id", TbkDatabaseConstants::COLUMN_TYPE_BIGINT20, true)
        );
        $table->addColumn("store_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("buy_order", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("order_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("child_buy_order", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("commerce_code", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("child_commerce_code", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("amount", TbkDatabaseConstants::COLUMN_TYPE_BIGINT20, true);
        $table->addColumn("refund_amount", TbkDatabaseConstants::COLUMN_TYPE_BIGINT20, true);
        $table->addColumn("token", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR100);
        $table->addColumn("transbank_status", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("session_id", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("status", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("transbank_response", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("last_refund_type", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("last_refund_response", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("all_refund_response", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("oneclick_username", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50);
        $table->addColumn("product", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("environment", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR50, true);
        $table->addColumn("error", TbkDatabaseConstants::COLUMN_TYPE_VARCHAR255);
        $table->addColumn("original_error", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("custom_error", TbkDatabaseConstants::COLUMN_TYPE_LONGTEXT);
        $table->addColumn("created_at", TbkDatabaseConstants::COLUMN_TYPE_TIMESTAMP, true, "NOW()");
        $table->addColumn("updated_at", TbkDatabaseConstants::COLUMN_TYPE_TIMESTAMP, true, "NOW()");
        return $table;
    }

    public function create(TransbankTransactionDto $data){
        $sql = $this->getSqlInsert($data);
        return $this->utilRepository->executeWriteSql($sql);
    }

    public function update(TransbankTransactionDto $data){
        $sql = $this->getSqlUpdate($data);
        return $this->utilRepository->executeWriteSql($sql);
    }

    public function getSqlCreateTable() {
        return $this->getTbkTable()->getSqlCreateTable();
    }

    public function getByToken($token){
        $tableName = $this->getTableName();
        $token = $this->utilRepository->sanitizeValue($token);
        $sql = "SELECT * FROM $tableName WHERE `token` = '$token'";
        return $this->rowToTransbankTransactionDto($sql);
    }

    public function getByBuyOrder($buyOrder){
        $tableName = $this->getTableName();
        $buyOrder = $this->utilRepository->sanitizeValue($buyOrder);
        $sql = "SELECT * FROM $tableName WHERE `buy_order` = '$buyOrder'";
        return $this->rowToTransbankTransactionDto($sql);
    }

    public function getTbkAuthorizedByOrderId($orderId){
        $tableName = $this->getTableName();
        $status = TbkConstants::TRANSACTION_TBK_STATUS_AUTHORIZED;
        $orderId = $this->utilRepository->sanitizeValue($orderId);
        $sql = "SELECT * FROM $tableName WHERE `transbank_status` = '$status' AND `order_id` = '$orderId'";
        return $this->rowToTransbankTransactionDto($sql);
    }

    protected function rowToTransbankTransactionDto($sql){
        $result = $this->getRow($sql);
        if (is_null($result) || $result === false) {
            return null;
        }
        return new TransbankTransactionDto($result);
    }

    public function getDateLastTransactionOk($environment){
        $status = TbkConstants::TRANSACTION_TBK_STATUS_AUTHORIZED;
        $environment = $this->utilRepository->sanitizeValue($environment);
        $sql = "
        select
            max(t.created_at)
        from
            ps_transbank_transaction t
        where
            t.transbank_status = '{$status}'
            and t.environment = '{$environment}' ";
        return $this->getvalue($sql);
    }

    public function lastTransactionsOk($environment, $total){
        $status = TbkConstants::TRANSACTION_TBK_STATUS_AUTHORIZED;
        $environment = $this->utilRepository->sanitizeValue($environment);
        $total = $this->utilRepository->sanitizeValue($total);
        $sql = "
            select
                COUNT(1) as total,
                SUM(CASE WHEN t.transbank_status = '{$status}' THEN 1 ELSE 0 END) as ok
            from
                (
                    select
                        t1.transbank_status
                    from
                        ps_transbank_transaction t1
                    where
                        t1.environment = '{$environment}'
                    order by t1.created_at desc LIMIT {$total} OFFSET 0
                ) t";
        return $this->getRow($sql);
    }


    private function lastTransactionsByPeriodSql($environment, $total, $dateFormat, $period){
        $status = TbkConstants::TRANSACTION_TBK_STATUS_AUTHORIZED;
        $environment = $this->utilRepository->sanitizeValue($environment);
        $total = $this->utilRepository->sanitizeValue($total);
        $sql = "
        select
            t.period,
            COUNT(1) as total,
            SUM(CASE WHEN t.product = 'webpay_plus' THEN 1 ELSE 0 END) as webpay_plus,
            SUM(CASE WHEN t.product = 'webpay_plus_mall' THEN 1 ELSE 0 END) as webpay_plus_mall,
            SUM(CASE WHEN t.product = 'webpay_oneclick' THEN 1 ELSE 0 END) as webpay_oneclick
        from
            (
                select
                    DATE_FORMAT(t1.created_at, '{$dateFormat}') as period,
                    t1.product
                from
                    ps_transbank_transaction t1
                where
                    t1.transbank_status = '{$status}'
                    and t1.environment = '{$environment}'
                    and t1.created_at > DATE_SUB(NOW(), INTERVAL {$total} {$period})
                order by t1.created_at
            ) t
        group by t.period	";
        return $this->executeSql($sql);
    }

    public function lastTransactionsByHour($environment, $total){
        return $this->lastTransactionsByPeriodSql($environment, $total, "%H", "HOUR");
    }

    public function lastTransactionsByDay($environment, $total){
        return $this->lastTransactionsByPeriodSql($environment, $total, "%Y-%m-%d", "DAY");
    }

    public function lastTransactionsByWeek($environment, $total){
        return $this->lastTransactionsByPeriodSql($environment, $total, "%x%v", "WEEK");
    }

    public function lastTransactionsByMonth($environment, $total){
        return $this->lastTransactionsByPeriodSql($environment, $total, "%Y-%m", "MONTH");
    }


}
