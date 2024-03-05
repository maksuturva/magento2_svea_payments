<?php

namespace Svea\SveaPayment\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Svea\SveaPayment\Model\Order\ReferenceNumberProvider;
use Svea\SveaPayment\Gateway\Config\Config;

class OrderManagement
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var ReferenceNumberProvider
     */
    private $refNumberProvider;

    public function __construct(
        OrderRepository                 $orderRepository,
        Session                         $checkoutSession,
        SortOrderBuilder                $sortOrderBuilder,
        SearchCriteriaBuilder           $searchCriteriaBuilder,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        ReferenceNumberProvider $refNumberProvider
    ) {
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->refNumberProvider = $refNumberProvider;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        if ($this->order == null) {
            $this->order = $this->checkoutSession->getLastRealOrder();
        }
        return $this->order;
    }

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order): OrderManagement
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @param string $paymentId
     * @return OrderInterface
     * @throws NoSuchEntityException
     * @throws InputException
     *
     * @see \Svea\SveaPayment\Gateway\Request\OrderDataBuilder::getId
     */
    public function getOrderByPaymentId($paymentId): OrderInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(Config::SVEA_PAYMENT_ID, $paymentId)
            ->create();

        $payments = $this->orderPaymentRepository->getList($searchCriteria)->getItems();

        if (empty($payments)) {
            throw new NoSuchEntityException(\__('Payment with id %1 not found', $paymentId));
        }

        $payment = \reset($payments);

        $order = $this->orderRepository->get($payment->getParentId());
        $this->checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId());
        return $order;
    }

    /**
     * @return Order
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getLastOrder(): Order
    {
        $quoteId = $this->checkoutSession->getQuote()->getId();

        $sortOrder = $this->sortOrderBuilder
            ->setField('entity_id')
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('quote_id', $quoteId, 'eq')
            ->addSortOrder($sortOrder)->create();

        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
        if (is_array($orderList) && !empty($orderList)) {
            $order = reset($orderList);
            if ($order->getId()) {
                $this->setOrder($order);
                $this->checkoutSession->setLastOrderId($order->getId())
                    ->setLastRealOrderId($order->getIncrementId());
            }
        }

        return $this->getOrder();
    }

    /**
     * @param Order $order
     * @param array $params
     *
     * @return bool
     */
    public function validateReferenceNumbers(Order $order, array $params): bool
    {
        $pmtReference = $params['pmt_reference'] ?? '';

        return $this->refNumberProvider->getPmtReferenceNumber($order->getIncrementId() + 100) == $pmtReference;
    }
}

