<?php

namespace Transbank\Plugin\IRepository;

interface IUtilRepository {
    function getDbPrefix();
    function getDbAditionalOptions();
    function getDbEngine();
    function executeSql($sql);
    function executeWriteSql($sql);
    function getRow($sql);
    function getValue($sql);
    function sanitizeValue($value);
}
