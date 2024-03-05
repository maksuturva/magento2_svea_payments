<?php

namespace Svea\SveaPayment\Test\Unit\Model\PartPaymentCalculator\SrciptValidator\Validators;

use PHPUnit\Framework\TestCase;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator\Validators\ClosingTag;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator\Validators\OpeningTag;

class ClosingTagTest extends TestCase
{
    /**
     * @var ClosingTag
     */
    private ClosingTag $model;

    public function setUp(): void
    {
        $this->model = new ClosingTag();
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
            'missing_closing_tag' =>
                [
                    'value' => '<script class="test">',
                    'expectedResult' => 'The script closing is invalid.'
                ],
            'invalid_closing_tag_src' =>
                [
                    'value' => '<script class="test"><src>',
                    'expectedResult' => 'The script closing is invalid.'
                ],
            'invalid_opening_tag_and_valid_closing_tag' =>
                [
                    'value' => '<src class="test" />',
                    'expectedResult' => 'The script closing is invalid.'
                ],
            'text_after_closing_tag' =>
                [
                    'value' => '<script class="test"></script>here is something extra',
                    'expectedResult' => 'The script closing is invalid.'
                ],
            'valid_closing_script_tag' =>
                [
                    'value' => '<script class="test"></script>',
                    'expectedResult' => ''
                ],
            'valid_closing_script_tag_2' =>
                [
                    'value' => '<script class="test"/>',
                    'expectedResult' => ''
                ],
        ];
    }
}
