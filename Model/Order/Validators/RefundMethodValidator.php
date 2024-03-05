<?php

namespace Svea\SveaPayment\Model\Order\Validators;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Svea\SveaPayment\Api\Order\OrderValidatorInterface;
use Svea\SveaPayment\Model\Payment\Method;
use function in_array;
use function sprintf;

class RefundMethodValidator implements OrderValidatorInterface
{
    const BLOCKED_ONLINE_REFUND_METHODS = [
        'USPP',
        'PLDP',
        'EEMK'
    ];

    /**
     * @var string
     */
    private string $errorMessage;

    /**
     * @var Method
     */
    private Method $method;

    /**
     * @param Method $method
     */
    public function __construct(
        Method $method
    ) {
        $this->method = $method;
    }

    /**
     * @param OrderInterface $order
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isValid(OrderInterface $order): bool
    {
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        if ($this->method->isMaksuturva($method)) {
            return true;
        }
        $additionalInformation = $payment->getAdditionalInformation();
        $methodCode = $additionalInformation['svea_method_code'] ?? '';
        $this->setErrorMessage($methodCode);
        if ($methodCode === '') {
            return false;
        }

        return !in_array($methodCode, self::BLOCKED_ONLINE_REFUND_METHODS);
    }

    /**
     * @param string $methodCode
     *
     * @return void
     */
    private function setErrorMessage(string $methodCode): void
    {
        $this->errorMessage = sprintf(
            'Online refund is not available for the order payment method (%s).Use offline refund and finalize refund in Extranet.',
            $methodCode
        );
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
