<?php

namespace Svea\SveaPayment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ResultInterface;

class DeliveryResponseValidator extends ResponseValidator
{
    const PKG_RESULT_CODE_SUCCESS = 00;
    const PKG_RESULT_CODE_ALREADY_SUBMITTED = 30;

    /**
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $result = parent::validate($validationSubject);

        if ($result->isValid()) {
            $response = $this->subjectReader->readResponseObject($validationSubject);
            if ($this->isErroneous($response['pkg_resultcode'])) {
                $result = $this->createResult(false, [$response['pkg_resulttext']], [$response['pkg_resultcode']]);
            }
        }

        return $result;
    }

    /**
     * @param int $code
     *
     * @return bool
     */
    private function isErroneous(int $code): bool
    {
        return !\in_array($code, [
            self::PKG_RESULT_CODE_SUCCESS,
            self::PKG_RESULT_CODE_ALREADY_SUBMITTED,
        ]);
    }
}
