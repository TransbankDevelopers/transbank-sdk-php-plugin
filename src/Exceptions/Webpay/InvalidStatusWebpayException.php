<?php

namespace Transbank\Plugin\Exceptions\Webpay;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;

class InvalidStatusWebpayException extends WithTransactionExceptionBase
{
    private $tokenWs;

    public function __construct($message, $tokenWs, TransbankTransactionDto $transaction, \Exception $previous = null) {
        $this->tokenWs = $tokenWs;
        parent::__construct($message, $transaction, $previous);
    }

    public function getTokenWs() {
        return $this->tokenWs;
    }

}
