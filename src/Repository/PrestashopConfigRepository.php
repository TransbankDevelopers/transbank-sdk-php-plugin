<?php

namespace Transbank\Plugin\Repository;

use Transbank\Plugin\Helpers\PrestashopInfoUtil;
use Transbank\Plugin\Helpers\TbkConstants;
use Transbank\Plugin\IRepository\IConfigRepository;

abstract class PrestashopConfigRepository extends BaseConfigRepository implements IConfigRepository {
    public function getEcommerce(){
        return TbkConstants::ECOMMERCE_PRESTASHOP;
    }
    public function getEcommerceSummary(){
        return PrestashopInfoUtil::getSummary();
    }
}
