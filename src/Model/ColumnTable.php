<?php

namespace Transbank\Plugin\Model;

use Transbank\Plugin\Helpers\TbkDatabaseConstants;

class ColumnTable
{
    public $name;
    public $type;
    public $required;
    public $default;

    public function __construct($name, $type, $required = false, $default = null) {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->default = $default;
    }

    public function getSql(){
        $notNull = $this->isRequired() ? "NOT NULL" : "";
        $default = !is_null($this->getDefault()) ? "DEFAULT {$this->getDefault()}" : "";
        return "`{$this->getName()}` {$this->getType()} {$notNull} {$default} ";
    }

    public function useQuote(){
        return !($this->getType() === TbkDatabaseConstants::COLUMN_TYPE_BIGINT20 ||
            $this->getType() === TbkDatabaseConstants::COLUMN_TYPE_TINYINT1);
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    /**
     * @return bool
    */
    public function isRequired() {
        return $this->required;
    }

    /**
     * @param bool $required
    */
    public function setRequired($required) {
        $this->required = $required;
    }

    public function getDefault() {
        return $this->default;
    }

    public function setDefault($default) {
        $this->default = $default;
    }

}
