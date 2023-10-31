<?php

namespace Transbank\Plugin\Exceptions\Oneclick;

use Transbank\Plugin\Exceptions\WithInscriptionExceptionBase;
use Transbank\Plugin\Model\TransbankInscriptionDto;

class UserCancelInscriptionOneclickException extends WithInscriptionExceptionBase
{
    public function __construct($message, TransbankInscriptionDto $inscription,
        \Exception $previous = null) {
        parent::__construct($message, $inscription, $previous);
    }
}
