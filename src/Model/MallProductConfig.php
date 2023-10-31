<?php

namespace Transbank\Plugin\Model;

use Transbank\Plugin\Helpers\ArrayUtils;

class MallProductConfig extends ProductConfig {

    public $multiStore = false;
    public $childCommerceCode = null;
    /**
     * @var ItemMallProductConfig
     */
    public $arrayChildCommerceCode = [];

    public function __construct($data = null) {
        parent::__construct($data);
        if (!is_null($data)){
            $this->setChildCommerceCode($data['childCommerceCode']);
            $this->setMultiStore($data['multiStore']);
            $arrayChilds = ArrayUtils::getValue($data, 'arrayChildCommerceCode');
            if (!is_null($arrayChilds)){
                foreach ($arrayChilds as $item) {
                    $this->addItemChildCommerceCode($item['storeId'], $item['commerceCode']);
                }
            }
        }
    }

    /**
     * @return bool
    */
    public function isMultiStore()
    {
        return $this->multiStore;
    }

    /**
     * @param bool $multiStore
    */
    public function setMultiStore($multiStore)
    {
        $this->multiStore = $multiStore;
    }

    public function getChildCommerceCode()
    {
        return $this->childCommerceCode;
    }

    public function setChildCommerceCode($childCommerceCode)
    {
        $this->childCommerceCode = $childCommerceCode;
    }

    public function getArrayChildCommerceCode()
    {
        return $this->arrayChildCommerceCode;
    }

    public function setArrayChildCommerceCode($arrayChildCommerceCode)
    {
        $this->arrayChildCommerceCode = $arrayChildCommerceCode;
    }

    public function addItemChildCommerceCode($storeId, $commerceCode)
    {
        $this->arrayChildCommerceCode[] = new ItemMallProductConfig($storeId, $commerceCode);
    }

    public function getItemChildCommerceCodeByStoreId($storeId)
    {
        foreach ($this->arrayChildCommerceCode as $item) {
            if ($item->getStoreId() === $storeId) {
                return $item;
            }
        }
        return null;
    }

    public function getChildCommerceCodeByStoreId($storeId)
    {
        $item = $this->getItemChildCommerceCodeByStoreId($storeId);
        if ($item != null){
            return $item->getCommerceCode();
        }
        return null;
    }

    public function getMallProductConfigByStoreId($storeId)
    {
        $config = new MallProductConfig();
        $config->setActive($this->isActive());
        $config->setProduction($this->isProduction());
        $config->setMultiStore($this->isMultiStore());
        $config->setCommerceCode($this->getCommerceCode());
        $config->setOrderStatusAfterPayment($this->getOrderStatusAfterPayment());
        $config->setApikey($this->getApikey());
        if ($this->isMultiStore()){
            $config->setChildCommerceCode($this->getChildCommerceCodeByStoreId($storeId));
        }
        else{
            $config->setChildCommerceCode($this->getChildCommerceCode());
        }
        return $config;
    }

}
