<?php

namespace Transbank\Plugin\Model;

abstract class TbkLogDtoBase extends TbkDtoBase
{
    public $buyOrder;
    public $service;
    public $product;

    public function getBuyOrder() {
        return $this->buyOrder;
    }

    public function setBuyOrder($buyOrder) {
        $this->buyOrder = $buyOrder;
    }

    public function getService() {
        return $this->service;
    }

    public function setService($service) {
        $this->service = $service;
    }

    public function getProduct() {
        return $this->product;
    }

    public function setProduct($product) {
        $this->product = $product;
    }
}
