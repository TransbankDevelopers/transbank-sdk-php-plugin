<?php

use PHPUnit\Framework\TestCase;
use Transbank\Plugin\Helpers\ArrayUtils;

class ArrayUtilsTest extends TestCase
{
    public function testGetValueIfExists()
    {
        $array = ['key' => 'value'];
        $result = ArrayUtils::getValue($array, 'key');
        $this->assertEquals('value', $result);
    }

    public function testGetValueDefaultIfNotExists()
    {
        $array = ['key' => 'value'];
        $defaultValue = 'default';
        $result = ArrayUtils::getValue($array, 'nonexistent_key', $defaultValue);
        $this->assertEquals($defaultValue, $result);
    }

    public function testGetValueDefaultIfNull()
    {
        $array = ['key' => null];
        $defaultValue = 'default';
        $result = ArrayUtils::getValue($array, 'key', $defaultValue);
        $this->assertEquals($defaultValue, $result);
    }
}
