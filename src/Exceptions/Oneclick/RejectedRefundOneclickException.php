<?php

namespace Transbank\Plugin\Exceptions\Oneclick;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;
use Transbank\Webpay\Oneclick\Responses\MallTransactionRefundResponse;

class RejectedRefundOneclickException extends WithTransactionExceptionBase
{
    /**
     * @var MallTransactionRefundResponse
     */
    private $refundResponse;

    public function __construct($message, TransbankTransactionDto $transaction,
        MallTransactionRefundResponse $refundResponse,
        \Exception $previous = null) {
        $this->refundResponse = $refundResponse;
        parent::__construct($message, $transaction, $previous);
    }

    public function getRefundResponse() {
        return $this->refundResponse;
    }
}
