<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator\Validators;

use Svea\SveaPayment\Api\PartPaymentCalculator\ValidatorInterface;
use function implode;
use function sprintf;
use function str_contains;

class Attributes implements ValidatorInterface
{
    /**
     * @inheritDoc
     */
    public function validate(string $value): string
    {
        $errors = [];
        $requiredAttributes = [
            'data-price',
            'data-sellerid',
        ];
        foreach ($requiredAttributes as $attribute) {
            if (!str_contains($value, $attribute)) {
                $errors[] = $attribute;
            }
        }
        if (!empty($errors)) {
            $errorsStr = implode(',', $errors);

            return sprintf('The script is missing attributes: %s.', $errorsStr);
        }

        return '';
    }
}
