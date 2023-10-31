<?php

namespace Transbank\Plugin\IRepository;

use Transbank\Plugin\Model\ContactDto;
use Transbank\Plugin\Model\OneclickConfig;
use Transbank\Plugin\Model\WebpayplusConfig;
use Transbank\Plugin\Model\LogConfig;
use Transbank\Plugin\Model\OrderStatusAfterPaymentDto;
use Transbank\Plugin\Model\WebpayplusMallConfig;

interface IConfigRepository {
    function getEcommerce();
    function getSummary();
    function getTimezone();
    /**
     * @return StoreDto[]
    */
    function getAllStores();
    /**
     * @return WebpayplusConfig
    */
    function getWebpayplusConfig();
    /**
     * @return WebpayplusConfig
    */
    function saveWebpayplusConfig(WebpayplusConfig $data);
    /**
     * @return WebpayplusMallConfig
    */
    function getWebpayplusMallConfig();
    /**
     * @return WebpayplusMallConfig
    */
    function saveWebpayplusMallConfig(WebpayplusMallConfig $data);
    /**
     * @return OneclickConfig
    */
    function getOneclickConfig();
    /**
     * @return OneclickConfig
    */
    function saveOneclickConfig(OneclickConfig $data);
    /**
     * @return LogConfig
    */
    function getLogConfig();
    /**
     * @return LogConfig
    */
    function saveLogConfig(LogConfig $data);
    function getDefaultOrderStatusAfterPayment();
    /**
     * @return OrderStatusAfterPaymentDto[]
    */
    function getListOrderStatusAfterPayment();
    function getPreviousWebpayplusConfigIfProduction();
    function getPreviousWebpayplusMallConfigIfProduction();
    function getPreviousOneclickConfigIfProduction();
    /**
     * @return ContactDto
    */
    function getContact();
    /**
     * @return ContactDto
    */
    function saveContact(ContactDto $data);
}
