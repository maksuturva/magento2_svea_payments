<?php

namespace Svea\SveaPayment\Model\Order\Validators;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Svea\SveaPayment\Api\Order\OrderValidatorInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Payment\Method;
use function sprintf;

class SellerIdValidator implements OrderValidatorInterface
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Method
     */
    private Method $method;

    /**
     * @var string
     */
    private string $errorMessage;

    /**
     * @param Config $config
     * @param Method $method
     */
    public function __construct(
        Config $config,
        Method $method
    ) {
        $this->config = $config;
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
        $orderSellerId = (string)$payment->getData(Config::SVEA_SELLER_ID);
        $configSellerId = $this->config->getSellerId();
        $this->setErrorMessage($orderSellerId, $configSellerId);

        return $orderSellerId === $configSellerId;
    }

    /**
     * @param string $orderSellerId
     * @param string $configSellerId
     *
     * @return void
     */
    private function setErrorMessage(string $orderSellerId, string $configSellerId): void
    {
        $this->errorMessage = sprintf(
            'The seller ID of the order payment (%s) is different from the seller ID of the configuration (%s).',
            $orderSellerId, $configSellerId
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
