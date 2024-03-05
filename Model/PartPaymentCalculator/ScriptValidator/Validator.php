<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptValidator;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Svea\SveaPayment\Api\PartPaymentCalculator\ValidatorInterface;

class Validator
{
    /**
     * @var array
     */
    private array $validators;

    /**
     * @var ErrorHandler
     */
    private ErrorHandler $errorHandler;

    /**
     * @var Http
     */
    private Http $request;

    /**
     * @param ErrorHandler $errorHandler
     * @param \Magento\Framework\App\Request\Http $request
     * @param array $validators
     */
    public function __construct(ErrorHandler $errorHandler, Http $request, array $validators = [])
    {
        $this->validators = $validators;
        $this->errorHandler = $errorHandler;
        $this->request = $request;
    }

    /**
     * @param string $value
     *
     * @return void
     * @throws LocalizedException
     */
    public function validate(string $value): void
    {
        $group = $this->request->getParam('groups');
        $calculatorEnabled = (int)$group['calculator_config']['fields']['enabled']['value'] === 1;

        foreach ($this->validators as $validator) {
            if (!$validator instanceof ValidatorInterface || !$calculatorEnabled) {
                continue;
            }
            $error = $validator->validate($value);
            if ($error) {
                $this->errorHandler->addError($error);
            }
        }
        $this->errorHandler->handleErrors();
    }
}
