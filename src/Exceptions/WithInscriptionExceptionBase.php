<?php

namespace Transbank\Plugin\Exceptions;

use Transbank\Plugin\Model\TransbankInscriptionDto;

abstract class WithInscriptionExceptionBase extends BaseException
{
    /**
     * @var TransbankInscriptionDto
     */
    private $inscription;

    public function __construct($message, TransbankInscriptionDto $inscription, \Exception $previous = null) {
        $this->inscription = $inscription;
        parent::__construct($message, $previous);
    }
    
    public function getInscription() {
        return $this->inscription;
    }
}
