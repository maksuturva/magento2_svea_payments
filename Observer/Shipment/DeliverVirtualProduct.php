<?php

namespace Svea\SveaPayment\Observer\Shipment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Payment\Method;
use Svea\SveaPayment\Model\Shipment\DeliveryManagement;
use Svea\SveaPayment\Model\System\Config\Source\DeliveryMode;

class DeliverVirtualProduct implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Method
     */
    protected $method;

    /**
     * @var DeliveryManagement
     */
    protected $deliveryManagement;

    public function __construct(
        Config             $config,
        Method             $method,
        DeliveryManagement $deliveryManagement
    ) {
        $this->config = $config;
        $this->method = $method;
        $this->deliveryManagement = $deliveryManagement;
    }


    /**
     * @param Observer $observer
     *
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->shouldHandle($observer)) {
            $this->handle($observer);
        }
    }

    private function shouldHandle(Observer $observer)
    {
        if ($this->config->getDeliveryMode() == DeliveryMode::MODE_DISABLED) {
            return false;
        }
     
        // Order should be complete, Svea payment method, no shipments and virtual products only
        $order = $observer->getEvent()->getOrder();
        if($order->getState() != 'complete') {
            return false;
        }
        if (!$this->method->isSvea($order->getPayment()->getMethodInstance())) {
            return false;
        }
        if ($order->hasShipments()) {
            return false;
        }

        if ($order->getIsVirtual()) {
            return true;
        }
        return false;
    }

    private function handle(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->deliveryManagement->completeVirtualOrder($order);
    }
}
