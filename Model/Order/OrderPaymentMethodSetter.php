<?php
namespace Svea\SveaPayment\Model\Order;

use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee;

class OrderPaymentMethodSetter
{
    /**
     * @param Quote $quote
     * @param Order $order
     */
    public function setOrderPaymentMethod(Quote $quote, Order $order): void
    {
        if (\strpos($quote->getPayment()->getData('method'), 'svea') !== false) {
            $additionalInfo = $quote->getPayment()->getData('additional_information');
            $this->setValueToOrder($additionalInfo, $order);
        }
    }

    /**
     * @param array $additionalInformation
     * @param Order $order
     */
    private function setValueToOrder(array $additionalInformation, Order $order): void
    {
        if ($additionalInformation && isset($additionalInformation['svea_preselected_payment_method'])) {
            $order->setSveaPreselectedPaymentMethod($additionalInformation['svea_preselected_payment_method']);

            return;
        }

        $order->setSveaPreselectedPaymentMethod('');
    }
}
