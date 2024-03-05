<?php

namespace Svea\SveaPayment\Api\PartPaymentCalculator;

interface ValidatorInterface
{
    /**
     * @param string $value
     *
     * @return string
     */
    public function validate(string $value): string;
}
