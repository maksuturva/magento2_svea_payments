<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator\Validators;

use Svea\SveaPayment\Api\PartPaymentCalculator\ValidatorInterface;
use function strpos;

class OpeningTag implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $value): string
    {
        if (strpos($value, '<script') !== 0) {
            return 'The script opening tag is invalid.';
        }

        return '';
    }
}
