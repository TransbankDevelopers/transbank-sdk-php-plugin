<?php

use PHPUnit\Framework\TestCase;
use Transbank\Plugin\Helpers\PrestashopInfoUtil;

class PrestashopInfoUtilTest extends TestCase
{
    public function testGetVersion()
    {
        if (!defined('_PS_VERSION_')) define('_PS_VERSION_', '1.7.6.5');
        $version = PrestashopInfoUtil::getVersion();
        $this->assertEquals('1.7.6.5', $version);
    }

    public function testGetPluginVersion()
    {
        define('_PS_ROOT_DIR_', '/path/to/your/prestashop');
        // Mock para simular el archivo config.xml
        $configXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module>
    <version>1.2.3</version>
</module>
XML;

        // Simular la carga del archivo config.xml
        $this->mockFileExists(true);
        $this->mockSimpleXmlLoadFile($configXml);

        $version = PrestashopInfoUtil::getPluginVersion();
        $this->assertEquals('1.2.3', $version);
    }

    public function testGetEcommerceInfo()
    {
        // Mock para simular las versiones desde PrestashopInfoUtil y GitHubUtil
        if (!defined('_PS_VERSION_')) define('_PS_VERSION_', '1.7.6.5');
        $this->mockFileExists(true);
        $this->mockSimpleXmlLoadFile('<module><version>1.2.3</version></module>');
        GitHubUtil::shouldReceive('getLastGitHubReleaseVersion')
            ->with(TbkConstants::REPO_OFFICIAL_PRESTASHOP)
            ->andReturn('1.7.7.0');
        GitHubUtil::shouldReceive('getLastGitHubReleaseVersion')
            ->with(TbkConstants::REPO_PRESTASHOP)
            ->andReturn('1.3.0');

        $info = PrestashopInfoUtil::getEcommerceInfo();
        $expected = [
            'ecommerce' => TbkConstants::ECOMMERCE_PRESTASHOP,
            'currentEcommerceVersion' => '1.7.6.5',
            'lastEcommerceVersion' => '1.7.7.0',
            'currentPluginVersion' => '1.2.3',
            'lastPluginVersion' => '1.3.0',
        ];

        $this->assertEquals($expected, $info);
    }

    private function mockFileExists($returnValue)
    {
        $fileExistsMock = \Mockery::mock('overload:file_exists');
        $fileExistsMock->shouldReceive('file_exists')->andReturn($returnValue);
    }

    private function mockSimpleXmlLoadFile($xmlContent)
    {
        $simpleXmlMock = \Mockery::mock('overload:simplexml_load_file');
        $simpleXmlMock->shouldReceive('simplexml_load_file')->andReturn(
            simplexml_load_string($xmlContent, null, LIBXML_NOCDATA)
        );
    }
}
