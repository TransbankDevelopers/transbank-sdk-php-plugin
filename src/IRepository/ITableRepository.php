<?php

namespace Transbank\Plugin\IRepository;

use Transbank\Plugin\Model\PaginatedList;

interface ITableRepository {
    function getTableName();
    function createTable();
    function deleteTable();
    function checkTable();
    /**
     * Este método retorna una lista paginada.
     *
     * @param array $params
     * @return PaginatedList
    */
    function getList($params);
}
