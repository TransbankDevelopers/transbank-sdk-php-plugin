<?php

namespace Transbank\Plugin\IRepository;

use Transbank\Plugin\Model\ExecutionErrorLogDto;

interface IExecutionErrorLogRepository extends ITableRepository {
    function create(ExecutionErrorLogDto $data);
}
