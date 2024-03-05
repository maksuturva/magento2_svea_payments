<?php

namespace Svea\SveaPayment\Model\Order;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Svea\SveaPayment\Exception\OrderAlreadyPaidException;
use Svea\SveaPayment\Model\Payment\AdditionalData;

class Cancellation
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var AdditionalData
     */
    private AdditionalData $paymentData;

    /**
     * @var OrderResource
     */
    private OrderResource $orderResource;

    /**
     * @var string[]
     */
    private array $cancellableStatuses = [
        Order::STATE_NEW,
        Order::STATE_PENDING_PAYMENT,
    ];

    /**
     * @param Logger $logger
     * @param AdditionalData $paymentData
     * @param OrderResource $orderResource
     */
    public function __construct(
        Logger         $logger,
        AdditionalData $paymentData,
        OrderResource  $orderResource
    ) {
        $this->logger = $logger;
        $this->paymentData = $paymentData;
        $this->orderResource = $orderResource;
    }

    /**
     * @param Order $order
     *
     * @throws AlreadyExistsException
     * @throws OrderAlreadyPaidException
     */
    public function cancelOrder(Order $order): void
    {
        if (!\in_array($order->getState(), $this->cancellableStatuses)) {
            throw new OrderAlreadyPaidException();
        }
        $order->setActionFlag(Order::ACTION_FLAG_CANCEL, true);
        $order->cancel();
        $order->addCommentToStatusHistory(\__('Payment canceled in Svea Payments.'), 'pay_aborted');
        $this->orderResource->save($order);
        $transactionId = $this->paymentData->getSveaTransactionId($order->getPayment());
        $this->logger->info(
            \__(
                "Cancel action controller, order %1 cancelled for payment %2",
                $order->getIncrementId(),
                $transactionId
            )
        );
    }
}
