<?php

namespace Svea\SveaPayment\Gateway\Response\Payment;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Svea\SveaPayment\Model\OrderManagement;
use Svea\SveaPayment\Model\Payment\AdditionalData;
use Svea\SveaPayment\Model\Quote\QuoteCancellation;
use Svea\SveaPayment\Model\Source\RestoreShoppingCart;
use function __;
use function in_array;

class ErrorHandler
{
    const ERROR_SELLERCOSTS_VALUES_MISMATCH = 'sellercosts_values_mismatch_error';

    /**
     * @var OrderManagement
     */
    private OrderManagement $orderManagement;

    /**
     * @var AdditionalData
     */
    private AdditionalData $paymentData;

    /**
     * @var OrderResource
     */
    private OrderResource $orderResource;

    /**
     * @var QuoteCancellation
     */
    private QuoteCancellation $quoteCancellation;

    /**
     * @param OrderManagement $orderManagement
     * @param AdditionalData $paymentData
     * @param OrderResource $orderResource
     * @param QuoteCancellation $quoteCancellation
     */
    public function __construct(
        OrderManagement $orderManagement,
        AdditionalData  $paymentData,
        OrderResource   $orderResource,
        QuoteCancellation $quoteCancellation
    ) {
        $this->orderManagement = $orderManagement;
        $this->paymentData = $paymentData;
        $this->orderResource = $orderResource;
        $this->quoteCancellation = $quoteCancellation;
    }

    /**
     * @param string $pmtId
     * @param array $requestParams
     *
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(string $pmtId, array $requestParams): void
    {
        $order = $this->orderManagement->getLastOrder();
        $transactionId = $this->paymentData->getSveaTransactionId($order->getPayment());
        if ($transactionId !== $pmtId) {
            throw new Exception('Transaction id mismatch');
        }
        if (in_array($order->getState(), [
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_NEW
        ])) {
            if (isset($requestParams['type']) && $requestParams['type'] === self::ERROR_SELLERCOSTS_VALUES_MISMATCH) {
                $order->addCommentToStatusHistory(
                    __(
                        'Mismatch in seller costs returned from Svea Payments. New sellercosts: %1',
                        $this->getNewSellerCost($requestParams)
                    )
                );
            } else {
                $order->cancel();
                $this->quoteCancellation->cancelQuote(RestoreShoppingCart::ERROR);
                $order->addCommentToStatusHistory(__('Error on Svea Payments return'));
            }
            $this->orderResource->save($order);
        }
    }

    /**
     * @param array $requestParams
     *
     * @return string
     * @throws Exception
     */
    private function getNewSellerCost(array $requestParams): string
    {
        if (!isset($requestParams['new_sellercosts']) || !isset($requestParams['old_sellercosts'])) {
            throw new Exception('One of seller costs was not specified');
        }
        $newCost = $requestParams['new_sellercosts'];
        $oldCost = $requestParams['old_sellercosts'];

        return __('%1 EUR, was %2 EUR', $newCost, $oldCost);
    }
}
