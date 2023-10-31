<?php

namespace Transbank\Plugin\Model;

class OrderStatusAfterPaymentDto
{
    public $value;
    public $label;

    public function __construct($value, $label) {
        $this->value = (string)$value;
        $this->label = $label;
    }
    
    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $this->value = (string)$value;
    }

    public function getLabel() {
        return $this->label;
    }

    public function setLabel($label) {
        $this->label = $label;
    }

}
