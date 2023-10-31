<?php

namespace Transbank\Plugin\Model;

abstract class TbkDtoBase
{
    public $id;
    public $storeId;
    public $environment;
    public $commerceCode;
    public $error;
    public $originalError;
    public $customError;
    public $createdAt;

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }
    public function getStoreId() {
        return $this->storeId;
    }

    public function setStoreId($storeId) {
        $this->storeId = $storeId;
    }

    public function getEnvironment() {
        return $this->environment;
    }

    public function setEnvironment($environment) {
        $this->environment = $environment;
    }

    public function getCommerceCode() {
        return $this->commerceCode;
    }

    public function setCommerceCode($commerceCode) {
        $this->commerceCode = $commerceCode;
    }

    public function getError() {
        return $this->error;
    }

    public function setError($error) {
        $this->error = $error;
    }

    public function getOriginalError() {
        return $this->originalError;
    }

    public function setOriginalError($originalError) {
        $this->originalError = $originalError;
    }

    public function getCustomError() {
        return $this->customError;
    }

    public function setCustomError($customError) {
        $this->customError = $customError;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;
    }
}
