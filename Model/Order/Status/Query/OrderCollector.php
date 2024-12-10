<?php

namespace Svea\SveaPayment\Model\Order\Status\Query;

use DateTimeInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Payment\Method;

class OrderCollector
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $ordersFactory;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Method
     */
    private Method $paymentMethod;

    /**
     * @param CollectionFactory $ordersFactory
     * @param Config $config
     * @param Method $paymentMethod
     */
    public function __construct(
        CollectionFactory $ordersFactory,
        Config            $config,
        Method            $paymentMethod
    ) {
        $this->ordersFactory = $ordersFactory;
        $this->config = $config;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @param DateTimeInterface $createdStart
     * @param DateTimeInterface $createdEnd
     *
     * @return array
     */
    public function collectSveaOrdersByDate(DateTimeInterface $createdStart, DateTimeInterface $createdEnd): array
    {
        $collection = $this->ordersFactory->create();
        $collection->join(['payment' => 'sales_order_payment'], 'main_table.entity_id=parent_id', 'method')
            ->addFieldToFilter('status', ['in' => [$this->config->getNewOrderStatus(), Order::STATE_PENDING_PAYMENT]])
            ->addFieldToFilter('payment.method', $this->paymentMethod->getSveaCollectionFilter())
            ->addFieldToFilter('payment.' . Config::SVEA_SELLER_IR, $this->config->getSellerId())
            ->addAttributeToFilter('created_at', ['gteq' => $this->formatDate($createdStart)])
            ->addAttributeToFilter('created_at', ['lt' => $this->formatDate($createdEnd)]);

        return $collection->getItems();
    }

    /**
     * @param DateTimeInterface $createdStart
     * @param DateTimeInterface $createdEnd
     *
     * @return array
     */
    public function collectMaksuturvaOrdersByDate(DateTimeInterface $createdStart, DateTimeInterface $createdEnd): array
    {
        $collection = $this->ordersFactory->create();
        $collection->join(['payment' => 'sales_order_payment'], 'main_table.entity_id=parent_id', 'method')
            ->addFieldToFilter('status', ['in' => [$this->config->getNewOrderStatus(), Order::STATE_PENDING_PAYMENT]])
            ->addFieldToFilter('payment.method', $this->paymentMethod->getMaksuturvaCollectionFilter())
            ->addAttributeToFilter('created_at', ['gteq' => $this->formatDate($createdStart)])
            ->addAttributeToFilter('created_at', ['lt' => $this->formatDate($createdEnd)]);

        return $collection->getItems();
    }

    /**
     * @param DateTimeInterface $date
     *
     * @return string
     */
    private function formatDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
