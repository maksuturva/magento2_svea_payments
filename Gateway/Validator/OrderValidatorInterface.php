<?php

namespace Svea\SveaPayment\Gateway\Validator;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;

interface OrderValidatorInterface
{
    /**
     * @param OrderInterface $order
     *
     * @return void
     * @throws LocalizedException
     */
    public function validate(OrderInterface $order): void;
}
