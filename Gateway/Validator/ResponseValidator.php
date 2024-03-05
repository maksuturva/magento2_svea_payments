<?php

namespace Svea\SveaPayment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Svea\SveaPayment\Gateway\SubjectReader;

class ResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = $this->subjectReader->readResponseObject($validationSubject);
        $isValid = true;
        $errorMessages = [];

        // If the service is temporarily down due to maintenance, the response can contain html
        if (isset($response['BODY']) || isset($response['HEAD']['TITLE'])) {
            $isValid = false;
            $errorMessages[] = $response['BODY'] ?? $response['HEAD']['TITLE'];
        }

        if (isset($response['error'])) {
            if (!\is_array($response['error'])) {
                $response['error'] = (array)$response['error'];
            }

            $isValid = false;
            foreach ($response['error'] as $error) {
                $errorMessages[] = $error;
            }
        }

        return $this->createResult($isValid, $errorMessages, []);
    }
}
