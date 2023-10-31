<?php

namespace Transbank\Plugin\Exceptions\Webpay;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;

class TimeoutWebpayException extends WithTransactionExceptionBase
{
    private $buyOrder;
    private $sessionId;

    public function __construct($message, $buyOrder, $sessionId, TransbankTransactionDto $transaction = null, \Exception $previous = null) {
        $this->buyOrder = $buyOrder;
        $this->sessionId = $sessionId;
        parent::__construct($message, $transaction, $previous);
    }

    public function getBuyOrder() {
        return $this->buyOrder;
    }

    public function getSessionId() {
        return $this->sessionId;
    }
}
