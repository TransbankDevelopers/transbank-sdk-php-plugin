<?php

namespace Transbank\Plugin\Exceptions;

use Transbank\Plugin\Model\TransbankTransactionDto;

class UpdateTransactionDbException extends WithTransactionExceptionBase
{
    public function __construct($message, TransbankTransactionDto $transaction, \Exception $previous = null) {
        parent::__construct($message, $transaction, $previous);
    }
}
