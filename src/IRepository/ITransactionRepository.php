<?php

namespace Transbank\Plugin\IRepository;

use Transbank\Plugin\Model\TransbankTransactionDto;

interface ITransactionRepository extends ITableRepository {
    /**
     * Este método crea una transacción.
     *
     * @param TransbankTransactionDto $data
    */
    function create(TransbankTransactionDto $data);

    function update(TransbankTransactionDto $data);

    /**
     * Este método retorna una transacción única por Token.
     *
     * @param string $token
     * @return TransbankTransactionDto
    */
    function getByToken($token);

    /**
     * Este método retorna una transacción única por buyOrder.
     *
     * @param string $token
     * @return TransbankTransactionDto
    */
    function getByBuyOrder($buyOrder);

    /**
     * Este método busca una transacción única con el campo 'transbankStatus' = 'approved' por OrderId.
     *
     * @param string $orderId
     * @return TransbankTransactionDto
    */
    function getTbkAuthorizedByOrderId($orderId);
    function getDateLastTransactionOk($environment);
    function lastTransactionsOk($environment, $total);
    function lastTransactionsByHour($environment, $total);
    function lastTransactionsByDay($environment, $total);
    function lastTransactionsByWeek($environment, $total);
    function lastTransactionsByMonth($environment, $total);
}
