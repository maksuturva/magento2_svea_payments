<?php

namespace Svea\SveaPayment\Gateway\Validator;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Svea\SveaPayment\Api\Order\OrderValidatorInterface;
use Svea\SveaPayment\Gateway\Validator\OrderValidatorInterface as GatewayOrderValidatorInterface;
use function __;

class OrderValidator implements GatewayOrderValidatorInterface
{
    /**
     * @var array
     */
    private array $validators;

    /**
     * @param array $validators
     */
    public function __construct(
        array $validators = []
    ) {
        $this->validators = $validators;
    }

    /**
     * @param OrderInterface $order
     *
     * @return void
     * @throws LocalizedException
     */
    public function validate(OrderInterface $order): void
    {
        foreach ($this->validators as $validator) {
            if (!($validator instanceof OrderValidatorInterface)) {
                continue;
            }
            if (!$validator->isValid($order)) {
                throw new LocalizedException(__($validator->getErrorMessage()));
            }
        }
    }
}
