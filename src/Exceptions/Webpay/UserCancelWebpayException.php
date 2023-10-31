<?php

namespace Transbank\Plugin\Exceptions\Webpay;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;

class UserCancelWebpayException extends WithTransactionExceptionBase
{
    private $tbkToken;

    public function __construct($message, $tbkToken, TransbankTransactionDto $transaction, \Exception $previous = null) {
        $this->tbkToken = $tbkToken;
        parent::__construct($message, $transaction, $previous);
    }

    public function getTbkToken() {
        return $this->tbkToken;
    }

}
