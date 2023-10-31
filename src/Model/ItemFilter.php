<?php

namespace Transbank\Plugin\Model;

use Transbank\Plugin\Helpers\ArrayUtils;
use Transbank\Plugin\Helpers\StringUtils;

class ItemFilter  {
    public $field;
    public $value;
    public $mode;

    /**
     * Este es el método constructor que recibe un objeto json e inicializa el objeto.
     * el objeto recibido tiene la siguiente estructura:
     * {"field":"environment","value":"TEST","mode":"equals"}
     *
     * @param $data
     */
    public function __construct($data) {
        if (isset($data)){
            $this->setField(ArrayUtils::getValue($data, 'field'));
            $this->setField(StringUtils::camelToSnake($this->getField()));
            $this->setValue(ArrayUtils::getValue($data, 'value'));
            $this->setMode(ArrayUtils::getValue($data, 'mode'));
        }
    }

    public function getField() {
        return $this->field;
    }

    public function setField($field) {
        $this->field = $field;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $this->value = $value;
    }

    public function getMode() {
        return $this->mode;
    }

    public function setMode($mode) {
        $this->mode = $mode;
    }
}
