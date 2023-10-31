<?php

use PHPUnit\Framework\TestCase;
use Transbank\Plugin\Helpers\ObjectUtil;

class ObjectUtilTest extends TestCase
{
    public function testCopyPropertiesFromTo()
    {
        // Crear objetos de prueba
        $from = new stdClass();
        $from->name = 'John';
        $from->age = 30;

        $to = new stdClass();

        // Copiar propiedades de $from a $to
        ObjectUtil::copyPropertiesFromTo($from, $to);

        // Verificar que las propiedades se hayan copiado correctamente
        $this->assertEquals('John', $to->name);
        $this->assertEquals(30, $to->age);
    }

    public function testCopyPropertiesFromToWithEmptyObject()
    {
        // Crear objetos de prueba
        $from = new stdClass();
        $to = new stdClass();

        // Copiar propiedades de $from a $to (debería no hacer nada)
        ObjectUtil::copyPropertiesFromTo($from, $to);

        // Verificar que $to siga siendo un objeto vacío
        $this->assertEquals(new stdClass(), $to);
    }
}
