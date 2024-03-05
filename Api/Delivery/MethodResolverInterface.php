<?php

namespace Svea\SveaPayment\Api\Delivery;

use Magento\Framework\Exception\LocalizedException;

interface MethodResolverInterface
{
    /**
     * @param array $buildSubject
     *
     * @return string
     * @throws LocalizedException
     */
    public function resolve(array $buildSubject): string;
}
