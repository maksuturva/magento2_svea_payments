<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator\Validators;

use Svea\SveaPayment\Api\PartPaymentCalculator\ValidatorInterface;
use function str_contains;
use function strlen;
use function strpos;
use function strrpos;

class ClosingTag implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $value): string
    {
        $closingTags = [
            '</script>',
            '/>'
        ];
        $closingTagFound = false;
        foreach ($closingTags as $closingTag) {
            if (str_contains($value, $closingTag)) {
                $closingTagFound = true;
                $scriptOpen = '<script';
                $scriptCloseLength = strlen($closingTag);
                $stringLength = strlen($value);
                $scriptOpenPos = strpos($value, $scriptOpen);
                $scriptClosePos = strrpos($value, $closingTag, $scriptOpenPos);
                if ($scriptOpenPos === false || $scriptClosePos + $scriptCloseLength !== $stringLength) {
                    return 'The script closing is invalid.';
                }
            }
        }
        if (!$closingTagFound) {
            return 'The script closing is invalid.';
        }

        return '';
    }
}
