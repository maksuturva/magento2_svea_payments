<?php

namespace Svea\SveaPayment\Cron;

use Exception;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Store\Model\StoreManagerInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Order\Status\Query;

class OrderStatusCheck
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Logger $logger,
        Query  $query,
        Config $config,
        StoreManagerInterface $storeManager
    ) {
        $this->logger = $logger;
        $this->query = $query;
        $this->config = $config;
        $this->storeManager = $storeManager;
    }

    public function execute(): void
    {
        if ($this->config->isCronActive()) {
            foreach ($this->storeManager->getStores(true) as $store) {
                $this->storeManager->setCurrentStore($store->getId());
                try {
                    $this->executeQuery();
                } catch (Exception $e) {
                    $this->logger->error('Scheduled payment status query failed for store ID ' . $store->getId() . ', reason: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    private function executeQuery(): void
    {
        // Do status query only orders that are maximum 7 days old
        $statuses = $this->query->querySince('-7 days', '-1 minutes');
        if (empty($statuses)) {
            $this->logger->info(\__('Scheduled payment status query succeeded; no orders to check'));
        } else {
            $ids = \implode(', ', \array_keys($statuses));
            $this->logger->info(\__('Scheduled payment status query succeeded; checked orders: %1', $ids));
        }
    }
}
