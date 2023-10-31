<?php

namespace Transbank\Plugin\Model;

class ItemMallProductConfig {

    public $commerceCode = null;
    public $storeId;

    public function __construct($storeId, $commerceCode) {
        $this->storeId = (string)$storeId;
        $this->commerceCode = (string)$commerceCode;
    }

    public function getCommerceCode()
    {
        return $this->commerceCode;
    }

    public function setCommerceCode($commerceCode)
    {
        $this->commerceCode = $commerceCode;
    }

    public function getStoreId() {
        return $this->storeId;
    }

    public function setStoreId($storeId) {
        $this->storeId = $storeId;
    }

}

