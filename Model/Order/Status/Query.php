<?php

namespace Svea\SveaPayment\Model\Order\Status;

use DateTimeInterface;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Model\Order\Status\Query\OrderCollector;
use Svea\SveaPayment\Model\Order\Status\Query\QueryProcessor;
use Svea\SveaPayment\Model\Order\Status\Query\QueryValidator;
use Svea\SveaPayment\Model\Order\Status\Query\Status;
use function __;
use function array_merge;
use function count;
use function date_create;
use function sprintf;

class Query
{
    /**
     * @var OrderCollector
     */
    private $orderCollector;

    /**
     * @var QueryProcessor
     */
    private $processor;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var QueryValidator
     */
    private $queryValidator;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Date
     */
    private $date;

    public function __construct(
        OrderCollector           $orderCollector,
        QueryProcessor           $processor,
        Logger                   $logger,
        QueryValidator           $queryValidator,
        OrderRepositoryInterface $orderRepository,
        DateTime                 $dateTime,
        Date                     $date
    ) {
        $this->orderCollector = $orderCollector;
        $this->processor = $processor;
        $this->logger = $logger;
        $this->queryValidator = $queryValidator;
        $this->orderRepository = $orderRepository;
        $this->dateTime = $dateTime;
        $this->date = $date;
    }

    /**
     * @param string $lookbackTime
     * @param string|null $startOffset
     * @param bool $isManualQuery
     *
     * @return Status[]
     * @throws LocalizedException
     */
    public function querySince(string $lookbackTime, ?string $startOffset = null, bool $isManualQuery = false): array
    {
        $from = $this->getDate($lookbackTime);
        $to = $this->getDate($startOffset);

        return $this->queryBetween($from, $to, $isManualQuery);
    }

    /**
     * @param string|null $modifier
     *
     * @return DateTimeInterface
     * @throws LocalizedException
     */
    private function getDate(?string $modifier): DateTimeInterface
    {
        $date = date_create($this->dateTime->formatDate($this->date->gmtTimestamp()));
        if ($modifier != null) {
            $date = $date->modify($modifier);
            if ($date === false) {
                throw new LocalizedException(__('Failed to resolve date offset from input "%1"', $modifier));
            }
        }

        return $date;
    }

    /**
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @param bool $isManualQuery
     *
     * @return Status[]
     */
    public function queryBetween(DateTimeInterface $from, DateTimeInterface $to,  bool $isManualQuery = false): array
    {
        $sveaOrders = $this->orderCollector->collectSveaOrdersByDate($from, $to);
        $maksuturvaOrders = $this->orderCollector->collectMaksuturvaOrdersByDate($from, $to);
        $orders = array_merge($sveaOrders, $maksuturvaOrders);
        $this->logger->info(sprintf(
            'Status Query: Querying orders created between "%s" and "%s", found %d',
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s'),
            count($orders)
        ));

        return $this->queryOrders($orders, $isManualQuery);
    }

    /**
     * @param Order[] $orders
     * @param bool $isManualQuery
     *
     * @return Status[]
     */
    public function queryOrders(array $orders, bool $isManualQuery = false): array
    {
        $statuses = [];
        foreach ($orders as $order) {
            if ($this->queryValidator->isValidForStatusCheck($order, $isManualQuery)) {
                $statuses[$order->getIncrementId()] = $this->processor->execute($order);
                $this->orderRepository->save($order);
            }
        }

        return $statuses;
    }
}
