<?php
namespace Svea\SveaPayment\Api\Checkout;

use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Model\Quote;

interface PaymentMethodCollectorInterface
{
    /**
     * @param Quote|null $quote
     *
     * @return array
     */
    public function getQuoteMethods(?Quote $quote = null);

    /**
     * @param MethodInterface $method
     * @param Quote|null $quote
     *
     * @return array
     */
    public function getAvailableQuoteMethods(MethodInterface $method, ?Quote $quote = null);
}
