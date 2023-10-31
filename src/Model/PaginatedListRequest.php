<?php

namespace Transbank\Plugin\Model;

use Transbank\Plugin\Helpers\ArrayUtils;
use Transbank\Plugin\Helpers\StringUtils;

class PaginatedListRequest  {
    public $sortField;
    public $sortOrder;
    /**
    * @var ItemFilter[]
    */
    public $filters = [];
    public $pageSize;
    public $firstRow;

    /**
     * Este es el método constructor que recibe un objeto json e inicializa el objeto.
     * el objeto recibido tiene la siguiente estructura:
     * {"pageSize":10,"firstRow":0,"sortField":"id","sortOrder":-1,
     * "filters":[{"field":"environment","value":"TEST","mode":"equals"},
     * {"field":"product","value":"webpay_oneclick","mode":"equals"}]}
     *
     * @param $data
     */
    public function __construct($data) {
        if (isset($data)){
            $this->setSortField(ArrayUtils::getValue($data, 'sortField'));
            $this->setSortField(StringUtils::camelToSnake($this->getSortField()));
            $this->setSortOrder(ArrayUtils::getValue($data, 'sortOrder', -1));
            $this->setPageSize(ArrayUtils::getValue($data, 'pageSize', 10));
            $this->setFirstRow(ArrayUtils::getValue($data, 'firstRow', 0));
            $filters =  [];
            foreach ($data['filters'] as $filter) {
                $filters[] = new ItemFilter($filter);
            }
            $this->setFilters($filters);
        }
    }

    public function getSortField() {
        return $this->sortField;
    }

    public function setSortField($sortField) {
        $this->sortField = $sortField;
    }

    public function getSortOrder() {
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder) {
        $this->sortOrder = $sortOrder;
    }

    public function getFilters() {
        return $this->filters;
    }

    public function setFilters($filters) {
        $this->filters = $filters;
    }

    public function getPageSize() {
        return $this->pageSize;
    }

    public function setPageSize($pageSize) {
        $this->pageSize = $pageSize;
    }

    public function getFirstRow() {
        return $this->firstRow;
    }

    public function setFirstRow($firstRow) {
        $this->firstRow = $firstRow;
    }
}
