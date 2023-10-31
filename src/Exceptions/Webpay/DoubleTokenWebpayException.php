<?php

namespace Transbank\Plugin\Exceptions\Webpay;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;

class DoubleTokenWebpayException extends WithTransactionExceptionBase
{
    private $tbkToken1;
    private $tbkToken2;

    public function __construct($message, $tbkToken1, $tbkToken2, TransbankTransactionDto $transaction, \Exception $previous = null) {
        $this->tbkToken1 = $tbkToken1;
        $this->tbkToken2 = $tbkToken2;
        parent::__construct($message, $transaction, $previous);
    }

    public function getTbkToken1() {
        return $this->tbkToken1;
    }

    public function getTbkToken2() {
        return $this->tbkToken2;
    }

}
