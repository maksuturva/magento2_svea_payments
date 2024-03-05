<?php

namespace Svea\SveaPayment\Cron;

use Exception;
use Magento\Framework\Logger\Monolog as Logger;
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

    public function __construct(
        Logger $logger,
        Query  $query,
        Config $config
    ) {
        $this->logger = $logger;
        $this->query = $query;
        $this->config = $config;
    }

    public function execute(): void
    {
        if ($this->config->isCronActive()) {
            try {
                $this->executeQuery();
            } catch (Exception $e) {
                $this->logger->error('Scheduled payment status query failed, reason: ' . $e->getMessage());
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
