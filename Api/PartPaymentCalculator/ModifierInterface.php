<?php

namespace Svea\SveaPayment\Api\PartPaymentCalculator;

interface ModifierInterface
{
    /**
     * @param string $script
     *
     * @return string
     */
    public function modify(string $script): string;
}
