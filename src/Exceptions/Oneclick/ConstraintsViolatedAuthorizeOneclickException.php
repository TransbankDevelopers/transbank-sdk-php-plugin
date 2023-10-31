<?php

namespace Transbank\Plugin\Exceptions\Oneclick;

use Transbank\Plugin\Exceptions\WithTransactionExceptionBase;
use Transbank\Plugin\Model\TransbankTransactionDto;
use Transbank\Webpay\Oneclick\Responses\MallTransactionAuthorizeResponse;

class ConstraintsViolatedAuthorizeOneclickException extends WithTransactionExceptionBase
{
    /**
     * @var MallTransactionAuthorizeResponse
     */
    private $authorizeResponse;

    public function __construct($message, TransbankTransactionDto $transaction,
        MallTransactionAuthorizeResponse $authorizeResponse,
        \Exception $previous = null) {
        $this->authorizeResponse = $authorizeResponse;
        parent::__construct($message, $transaction, $previous);
    }

    public function getAuthorizeResponse() {
        return $this->authorizeResponse;
    }
}
