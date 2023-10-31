<?php

namespace Transbank\Plugin\Exceptions\Oneclick;

use Transbank\Plugin\Exceptions\WithInscriptionExceptionBase;
use Transbank\Plugin\Model\TransbankInscriptionDto;
use Transbank\Webpay\Oneclick\Responses\InscriptionFinishResponse;

class RejectedInscriptionOneclickException extends WithInscriptionExceptionBase
{
    /**
     * @var InscriptionFinishResponse
     */
    private $finishInscriptionResponse;

    public function __construct($message, TransbankInscriptionDto $inscription,
        InscriptionFinishResponse $finishInscriptionResponse,
        \Exception $previous = null) {
        $this->finishInscriptionResponse = $finishInscriptionResponse;
        parent::__construct($message, $inscription, $previous);
    }

    public function getFinishInscriptionResponse() {
        return $this->finishInscriptionResponse;
    }
}
