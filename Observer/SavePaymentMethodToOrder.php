<?php
namespace Svea\SveaPayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Model\Order\OrderPaymentMethodSetter;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee;

class SavePaymentMethodToOrder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var OrderPaymentMethodSetter
     */
    private $paymentSetter;

    /**
     * @var HandlingFee
     */
    private $handlingFee;

    public function __construct(
        OrderPaymentMethodSetter $paymentSetter,
        HandlingFee $handlingFee
    ) {
        $this->paymentSetter = $paymentSetter;
        $this->handlingFee = $handlingFee;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getOrder();
        /** @var Quote $quote */
        $quote = $observer->getQuote();

        $this->paymentSetter->setOrderPaymentMethod($quote, $order);

        $handlingFee = $this->handlingFee->getValue($quote);
        $this->handlingFee->setValue($order, $handlingFee);

        $handlingFeeTax = $this->handlingFee->getTaxAmount($quote);
        $this->handlingFee->setTaxAmount($order, $handlingFeeTax);

        $handlingFeeTaxPercentage = $this->handlingFee->getTaxAmountPercentage($quote);
        $this->handlingFee->setTaxAmountPercentage($order, $handlingFeeTaxPercentage);

        return $this;
    }
}
