<?php

namespace Transbank\Plugin\Repository;

use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\Helpers\WoocommerceInfoUtil;
use Transbank\Plugin\IRepository\IConfigRepository;

abstract class WoocommerceConfigRepository extends BaseConfigRepository implements IConfigRepository {
    public function getEcommerce(){
        return TbkConstants::ECOMMERCE_WOOCOMMERCE;
    }
    public function getEcommerceSummary(){
        return WoocommerceInfoUtil::getSummary();
    }
}
