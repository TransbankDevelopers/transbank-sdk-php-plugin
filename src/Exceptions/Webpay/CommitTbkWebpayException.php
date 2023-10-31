<?php

namespace Transbank\Plugin\Exceptions\Webpay;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;

class CommitTbkWebpayException extends WithTransactionExceptionBase
{
    public function __construct($message, TransbankTransactionDto $transaction, \Exception $previous = null) {
        parent::__construct($message, $transaction, $previous);
    }
}
