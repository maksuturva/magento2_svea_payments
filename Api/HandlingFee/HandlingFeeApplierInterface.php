<?php
namespace Svea\SveaPayment\Api\HandlingFee;

use Magento\Quote\Model\Quote;

interface HandlingFeeApplierInterface
{
    /**
     * @param Quote $quote
     * @param string|null $paymentMethod            Payment method code in Magento
     * @param string|null $methodCode               Selected actual method code
     * @param string|null $methodGroup              Method group in specified payment method
     *
     * @return mixed
     */
    public function updateHandlingFee(
        Quote  $quote,
        string $paymentMethod = null,
        string $methodCode = null,
        string $methodGroup = null
    );
}
