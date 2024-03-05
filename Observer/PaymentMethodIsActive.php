<?php

namespace Svea\SveaPayment\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Payment\Model\MethodInterface;
use Svea\SveaPayment\Api\Checkout\PaymentMethodCollectorInterface;
use Svea\SveaPayment\Model\Payment\Method;

class PaymentMethodIsActive implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Method
     */
    private $methodResolver;

    /**
     * @var PaymentMethodCollectorInterface
     */
    private $methodCollector;

    /**
     * @param Method $methodResolver
     * @param PaymentMethodCollectorInterface $methodCollector
     */
    public function __construct(
        Method                          $methodResolver,
        PaymentMethodCollectorInterface $methodCollector
    ) {
        $this->methodResolver = $methodResolver;
        $this->methodCollector = $methodCollector;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        /** @var MethodInterface $method */
        $method = $event->getMethodInstance();

        if ($this->methodResolver->isSvea($method)) {
            $this->process($method, $event->getResult());
        }
    }

    /**
     * @param MethodInterface $method
     * @param DataObject $result
     */
    private function process(MethodInterface $method, DataObject $result)
    {
        $hasMethods = !empty($this->methodCollector->getAvailableQuoteMethods($method));
        $result->setData('is_available', $hasMethods);
    }
}
