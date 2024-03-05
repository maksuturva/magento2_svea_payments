<?php

namespace Svea\SveaPayment\Test\Unit\Model\PartPaymentCalculator\SrciptValidator\Validators;

use PHPUnit\Framework\TestCase;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator\Validators\OpeningTag;

class OpeningTagTest extends TestCase
{
    /**
     * @var OpeningTag
     */
    private OpeningTag $model;

    public function setUp(): void
    {
        $this->model = new OpeningTag();
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
            'invalid_opening_script_tag' =>
                [
                    'value' => '<src',
                    'expectedResult' => 'The script opening tag is invalid.'
                ],
            'valid_opening_script_tag' =>
                [
                    'value' => '<script',
                    'expectedResult' => ''
                ],
        ];
    }
}
