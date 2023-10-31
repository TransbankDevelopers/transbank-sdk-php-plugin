<?php

namespace Transbank\Plugin\Model;

class StoreDto
{
    public $storeId;
    public $storeName;

    public function __construct($storeId, $storeName) {
        $this->storeId = (string)$storeId;
        $this->storeName = (string)$storeName;
    }

    public function getStoreId() {
        return $this->storeId;
    }

    public function setStoreId($storeId) {
        $this->storeId = $storeId;
    }

    public function getStoreName() {
        return $this->storeName;
    }

    public function setStoreName($storeName) {
        $this->storeName = $storeName;
    }
}
