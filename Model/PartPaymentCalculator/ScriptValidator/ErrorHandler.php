<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator;

use Magento\Framework\Exception\LocalizedException;

class ErrorHandler
{
    private array $errors = [];

    /**
     * @param string $message
     *
     * @return void
     */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function handleErrors(): void
    {
        if (!empty($this->getErrors())) {
            throw new LocalizedException(
                __(
                    'Inserted script is invalid. Errors: %1',
                    \join(' ', $this->getErrors())
                )
            );
        }
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
