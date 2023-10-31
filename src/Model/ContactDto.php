<?php

namespace Transbank\Plugin\Model;

class ContactDto
{
    public $commerceName;
    public $commerceEmail;
    public $commerceResponsible;
    public $integratorName;
    public $integratorEmail;

    public function __construct($data = null) {
        if (!is_null($data)){
            $this->setCommerceName($data['commerceName']);
            $this->setCommerceEmail($data['commerceEmail']);
            $this->setCommerceResponsible($data['commerceResponsible']);
            $this->setIntegratorName($data['integratorName']);
            $this->setIntegratorEmail($data['integratorEmail']);
        }
    }

    public function getCommerceName() {
        return $this->commerceName;
    }

    public function setCommerceName($commerceName) {
        $this->commerceName = $commerceName;
    }

    public function getCommerceEmail() {
        return $this->commerceEmail;
    }

    public function setCommerceEmail($commerceEmail) {
        $this->commerceEmail = $commerceEmail;
    }

    public function getCommerceResponsible() {
        return $this->commerceResponsible;
    }

    public function setCommerceResponsible($commerceResponsible) {
        $this->commerceResponsible = $commerceResponsible;
    }

    public function getIntegratorName() {
        return $this->integratorName;
    }

    public function setIntegratorName($integratorName) {
        $this->integratorName = $integratorName;
    }

    public function getIntegratorEmail() {
        return $this->integratorEmail;
    }

    public function setIntegratorEmail($integratorEmail) {
        $this->integratorEmail = $integratorEmail;
    }
}
