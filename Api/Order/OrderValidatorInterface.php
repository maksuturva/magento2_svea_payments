<?php

namespace Svea\SveaPayment\Api\Order;

use Magento\Sales\Api\Data\OrderInterface;

interface OrderValidatorInterface
{
    /**
     * @param OrderInterface $order
     *
     * @return bool
     */
    public function isValid(OrderInterface $order): bool;

    /**
     * @return string
     */
    public function getErrorMessage(): string;
}
