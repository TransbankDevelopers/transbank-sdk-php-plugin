<?php

use PHPUnit\Framework\TestCase;
use Transbank\Plugin\Helpers\StringUtils;

class StringUtilsTest extends TestCase
{
    public function testIsNotBlankOrNullWithNotBlankString()
    {
        $result = StringUtils::isNotBlankOrNull('Hello, World!');
        $this->assertTrue($result);
    }

    public function testIsNotBlankOrNullWithBlankString()
    {
        $result = StringUtils::isNotBlankOrNull('');
        $this->assertFalse($result);
    }

    public function testIsNotBlankOrNullWithNull()
    {
        $result = StringUtils::isNotBlankOrNull(null);
        $this->assertFalse($result);
    }

    public function testHasLengthWithMatchingLength()
    {
        $result = StringUtils::hasLength('abcdef', 6);
        $this->assertTrue($result);
    }

    public function testHasLengthWithNonMatchingLength()
    {
        $result = StringUtils::hasLength('abc', 4);
        $this->assertFalse($result);
    }

    public function testSnakeToCamel()
    {
        $result = StringUtils::snakeToCamel('my_variable_name');
        $this->assertEquals('myVariableName', $result);
    }

    public function testCamelToSnake()
    {
        $result = StringUtils::camelToSnake('myCamelCaseString');
        $this->assertEquals('my_camel_case_string', $result);
    }
}
