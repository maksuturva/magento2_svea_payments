<?php

namespace Svea\SveaPayment\Model\Checkout;

use Magento\Checkout\Model\Cart;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;
use Svea\SveaPayment\Api\Checkout\PaymentMethodCollectorInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Payment\MethodDataProvider;
use function explode;
use function in_array;
use function preg_replace;

class PaymentMethodCollector implements PaymentMethodCollectorInterface
{
    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var MethodDataProvider
     */
    private MethodDataProvider $methodProvider;

    /**
     * @var array
     */
    private array $methods = [];

    /**
     * @param Cart $cart
     * @param Config $config
     * @param MethodDataProvider $methodProvider
     */
    public function __construct(
        Cart               $cart,
        Config             $config,
        MethodDataProvider $methodProvider
    ) {
        $this->cart = $cart;
        $this->config = $config;
        $this->methodProvider = $methodProvider;
    }

    /**
     * @inheritDoc
     */
    public function getAvailableQuoteMethods(MethodInterface $method, ?Quote $quote = null): array
    {
        $methods = [];
        $allowedMethods = $this->getAllowedMethods($method);
        $quoteMethods = $this->getQuoteMethods($quote);
        $paymentMethods = $quoteMethods['paymentmethod'] ?? [];
        foreach ($paymentMethods as $method) {
            if (empty($allowedMethods) || in_array($method['code'], $allowedMethods)) {
                $methods[] = $method;
            }
        }

        return $methods;
    }

    /**
     * @param MethodInterface $method
     *
     * @return array
     */
    public function getAllowedMethods(MethodInterface $method): array
    {
        $allowedMethods = [];
        if ($method->getConfigData('method_filter')) {
            $methodFilter = $method->getConfigData('method_filter');
            $methodFilter = preg_replace('/\s+/', '', $methodFilter);
            $allowedMethods = explode(',', $methodFilter);
        }

        return $allowedMethods;
    }

    /**
     * @inheritDoc
     */
    public function getQuoteMethods(?Quote $quote = null): array
    {
        $quote = $quote ?? $this->cart->getQuote();
        if (!isset($this->methods[$quote->getId()])) {
            $quoteTotal = $quote->getGrandTotal();
            $locale = $this->config->getLocale($quote->getStoreId());
            $this->methods[$quote->getId()] = $this->methodProvider->request($locale, $quoteTotal);
        }

        return $this->methods[$quote->getId()];
    }
}
