<?php

namespace Svea\SveaPayment\Observer\Shipment;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Payment\Method;
use Svea\SveaPayment\Model\Shipment\DeliveryManagement;
use Svea\SveaPayment\Model\System\Config\Source\DeliveryMode;

abstract class DeliveryInformationHandler implements ObserverInterface
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

    /**
     * @param Observer $observer
     *
     * @return mixed
     */
    protected abstract function handle(Observer $observer);

    /**
     * @param Observer $observer
     *
     * @return Order\Shipment
     */
    protected function getShipment(Observer $observer): Order\Shipment
    {
        return $observer->getData('shipment') ?? $this->getTrack($observer)->getShipment();
    }

    /**
     * @param Observer $observer
     *
     * @return Order\Shipment\Track|null
     */
    protected function getTrack(Observer $observer): ?Order\Shipment\Track
    {
        return $observer->getData('track');
    }

    /**
     * @param Observer $observer
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function shouldHandle(Observer $observer): bool
    {
        if ($this->config->getDeliveryMode() == DeliveryMode::MODE_DISABLED) {
            return false;
        }

        $order = $this->getShipment($observer)->getOrder();
        if (!$order || !$order->getId()) {
            return false;
        }

        return $this->method->isSvea($order->getPayment()->getMethodInstance());
    }
}
