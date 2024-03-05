<?php

namespace Svea\SveaPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Logger\Monolog as Logger;
use Svea\SveaPayment\Exception\OrderAlreadyPaidException;
use Svea\SveaPayment\Exception\PaymentHandlingException;
use Svea\SveaPayment\Model\Order\Cancellation;
use Svea\SveaPayment\Model\OrderManagement;
use Svea\SveaPayment\Model\Payment\AdditionalData;
use Svea\SveaPayment\Model\Quote\QuoteCancellation;
use Svea\SveaPayment\Model\QuoteManagement;
use Svea\SveaPayment\Model\Source\RestoreShoppingCart;

class Cancel extends Action
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var OrderManagement
     */
    private OrderManagement $orderManagement;

    /**
     * @var Cancellation
     */
    private Cancellation $cancellation;

    /**
     * @var AdditionalData
     */
    private AdditionalData $paymentData;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var QuoteCancellation
     */
    private QuoteCancellation $quoteCancellation;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param OrderManagement $orderManagement
     * @param Cancellation $cancellation
     * @param AdditionalData $paymentData
     * @param QuoteManagement $quoteManagement
     * @param QuoteCancellation $quoteCancellation
     */
    public function __construct(
        Context $context,
        Logger $logger,
        OrderManagement $orderManagement,
        Cancellation $cancellation,
        AdditionalData $paymentData,
        QuoteManagement $quoteManagement,
        QuoteCancellation $quoteCancellation
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->cancellation = $cancellation;
        $this->paymentData = $paymentData;
        $this->quoteManagement = $quoteManagement;
        $this->quoteCancellation = $quoteCancellation;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $pmtId = $this->getRequest()->getParam('pmt_id');

        if (empty($pmtId)) {
            $this->messageManager->addErrorMessage(\__('Unknown error on Svea Payments.'));
            return $this->_redirect(
                'svea_payment/index/error',
                ['type' => PaymentHandlingException::ERROR_TYPE_VALUES_MISMATCH]
            );
        }

        try {
            $this->logger->info(\sprintf('Cancel action controller request for payment %s', $pmtId));
            $order = $this->orderManagement->getLastOrder();
            $transactionId = $this->paymentData->getSveaTransactionId($order->getPayment());

            $this->quoteManagement->resetSessionHandlingFee();

            if ($transactionId !== $pmtId) {
                return $this->_redirect('checkout/cart');
            } else {
                $this->cancellation->cancelOrder($order);
                $this->quoteCancellation->cancelQuote(RestoreShoppingCart::CANCEL);
                $this->messageManager->addSuccessMessage(\__('You have cancelled your payment in Svea Payments.'));
            }
        } catch (OrderAlreadyPaidException $exception) {
            $this->messageManager->addErrorMessage(\__('Unable to cancel order that has already been paid.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->_redirect('checkout/cart');
    }
}
