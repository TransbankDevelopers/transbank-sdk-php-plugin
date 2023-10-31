<?php

namespace Transbank\Plugin\Repository;

use Transbank\Plugin\Helpers\InfoUtil;
use Transbank\Plugin\IRepository\IConfigRepository;

abstract class BaseConfigRepository implements IConfigRepository {
    abstract public function getEcommerceSummary();
    public function getSummary(){
        $result = InfoUtil::getSummary();
        $result['commerce'] = $this->getEcommerceSummary();
        return $result;
    }
}
