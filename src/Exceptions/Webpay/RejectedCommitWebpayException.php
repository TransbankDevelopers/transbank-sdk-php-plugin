<?php

namespace Transbank\Plugin\Exceptions\Webpay;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;
use Transbank\Webpay\WebpayPlus\Responses\MallTransactionCommitResponse;
use Transbank\Webpay\WebpayPlus\Responses\TransactionCommitResponse;

class RejectedCommitWebpayException extends WithTransactionExceptionBase
{

    /**
     * @var TransactionCommitResponse | MallTransactionCommitResponse
     */
    private $commitResponse;

    public function __construct($message, TransbankTransactionDto $transaction, $commitResponse, \Exception $previous = null) {
        $this->commitResponse = $commitResponse;
        parent::__construct($message, $transaction, $previous);
    }

    public function getCommitResponse() {
        return $this->commitResponse;
    }
}
