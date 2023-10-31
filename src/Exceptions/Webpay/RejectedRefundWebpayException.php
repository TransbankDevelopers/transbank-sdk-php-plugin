<?php

namespace Transbank\Plugin\Exceptions\Webpay;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;
use Transbank\Webpay\WebpayPlus\Responses\TransactionRefundResponse;

class RejectedRefundWebpayException extends WithTransactionExceptionBase
{
    /**
     * @var TransactionRefundResponse
     */
    private $refundResponse;

    public function __construct($message, TransbankTransactionDto $transaction, TransactionRefundResponse $refundResponse, \Exception $previous = null) {
        $this->refundResponse = $refundResponse;
        parent::__construct($message, $transaction, $previous);
    }

    public function getRefundResponse() {
        return $this->refundResponse;
    }
}
