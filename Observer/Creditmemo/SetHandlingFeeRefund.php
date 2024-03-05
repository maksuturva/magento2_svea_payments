<?php
namespace Svea\SveaPayment\Observer\Creditmemo;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee;

class SetHandlingFeeRefund implements ObserverInterface
{
    /**
     * @var HandlingFee
     */
    private $handlingFee;

    /**
     * @param HandlingFee $handlingFee
     */
    public function __construct(
        HandlingFee $handlingFee
    ) {
        $this->handlingFee = $handlingFee;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();
        $this->handlingFee->setRefundedValue($order, (float)$this->handlingFee->getBaseValue($creditmemo));
    }
}
