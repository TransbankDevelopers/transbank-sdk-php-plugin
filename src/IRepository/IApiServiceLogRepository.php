<?php

namespace Transbank\Plugin\IRepository;

use Transbank\Plugin\Model\ApiServiceLogDto;

interface IApiServiceLogRepository extends ITableRepository {
    function create(ApiServiceLogDto $data);
    function createError(ApiServiceLogDto $data);
}
