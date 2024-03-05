<?php

namespace Svea\SveaPayment\Test\Unit\Model\PartPaymentCalculator\ScriptModifier;

use PHPUnit\Framework\TestCase;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifier;

class ModifierTest extends TestCase
{
    /**
     * @param string $script
     * @param string $attributeName
     * @param string $value
     * @param string $expectedResult
     *
     * @return void
     * @dataProvider setAttributeDataProvider
     */
    public function testSetAttribute(string $script, string $attributeName, string $value, string $expectedResult)
    {
        $model = new Modifier();
        $this->assertEquals($expectedResult, $model->setAttribute($script, $attributeName, $value));
    }

    public function setAttributeDataProvider(): array
    {
        return [
            'set_attribute_test' => [
                'script' => '<script data-sellerid="TEST" data-price="329" data-locale="fi"></script>',
                'attributeName' => 'data-test',
                'value' => '123',
                'expectedResult' => '<script data-sellerid="TEST" data-price="329" data-locale="fi" data-test="123"/>'
            ],
            'modify_attribute_sellerid' => [
                'script' => '<script data-sellerid="TEST" data-price="329" data-locale="fi"></script>',
                'attributeName' => 'data-sellerid',
                'value' => 'TEST123',
                'expectedResult' => '<script data-sellerid="TEST123" data-price="329" data-locale="fi"/>'
            ],
            'empty_script' => [
                'script' => '',
                'attributeName' => 'data-sellerid',
                'value' => 'TEST123',
                'expectedResult' => ''
            ],
        ];
    }
}
