<?php

namespace Svea\SveaPayment\Observer\Shipment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddDeliveryInformation extends DeliveryInformationHandler implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    protected function handle(Observer $observer)
    {
        $shipment = $this->getShipment($observer);
        $this->deliveryManagement->add($shipment, $shipment->getTracks());
    }
}
