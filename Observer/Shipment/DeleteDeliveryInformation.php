<?php
namespace Svea\SveaPayment\Observer\Shipment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class DeleteDeliveryInformation extends DeliveryInformationHandler implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    protected function handle(Observer $observer)
    {
        $track = $this->getTrack($observer);
        if ($track && !$track->isObjectNew()) {
            $this->deliveryManagement->delete($this->getShipment($observer), $track);
        }
    }
}
