<?php

namespace Transbank\Plugin\Exceptions;

use Transbank\Plugin\Model\TransbankInscriptionDto;

class UpdateInscriptionDbException extends WithInscriptionExceptionBase
{
    public function __construct($message, TransbankInscriptionDto $inscription, \Exception $previous = null) {
        parent::__construct($message, $inscription, $previous);
    }
}
