<?php
namespace Svea\SveaPayment\Observer\Shipment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class UpdateDeliveryInformation extends DeliveryInformationHandler implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    protected function handle(Observer $observer)
    {
        $track = $this->getTrack($observer);
        if ($track) {
            if ($track->isObjectNew()) {
                $this->deliveryManagement->add($this->getShipment($observer), [$track]);
            } else {
                $this->deliveryManagement->update($this->getShipment($observer), $track);
            }
        }
    }
}
