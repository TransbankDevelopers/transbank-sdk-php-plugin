<?php

namespace Transbank\Plugin\Exceptions;

use Transbank\Plugin\Model\TransbankTransactionDto;

class WithTransactionExceptionBase extends BaseException
{
    /**
     * @var TransbankTransactionDto
     */
    private $transaction;

    public function __construct($message, TransbankTransactionDto $transaction, \Exception $previous = null) {
        $this->transaction = $transaction;
        parent::__construct($message, $previous);
    }

    public function getTransaction() {
        return $this->transaction;
    }
}
