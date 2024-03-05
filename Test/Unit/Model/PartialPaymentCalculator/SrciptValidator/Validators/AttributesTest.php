<?php

namespace Svea\SveaPayment\Test\Unit\Model\PartPaymentCalculator\SrciptValidator\Validators;

use PHPUnit\Framework\TestCase;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator\Validators\Attributes;

class AttributesTest extends TestCase
{
    /**
     * @var Attributes
     */
    private Attributes $model;

    public function setUp(): void
    {
        $this->model = new Attributes();
    }

    /**
     * @param string $value
     * @param string $expectedResult
     *
     * @return void
     * @dataProvider validateDataProvider
     */
    public function testValidate(string $value, string $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->model->validate($value));
    }

    /**
     * @return array
     */
    public function validateDataProvider(): array
    {
        return [
            'data-price_and_data-sellerid_missing' =>
                [
                    'value' => '<script class="test"></script>',
                    'expectedResult' => 'The script is missing attributes: data-price,data-sellerid.'
                ],
            'valid_required_attributes' =>
                [
                    'value' => '<script data-price="test" data-sellerid="asd"></script>',
                    'expectedResult' => ''
                ],
        ];
    }
}
