<?php

namespace Svea\SveaPayment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Svea\SveaPayment\Model\Payment\MethodDataProvider;

class ClearCachedPaymentMethods implements ObserverInterface
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(MethodDataProvider $cache)
    {
        $this->cache = $cache;
    }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $this->cache->clearCached();
    }
}
