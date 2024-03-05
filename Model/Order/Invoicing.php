<?php

namespace Svea\SveaPayment\Model\Order;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Invoice;
use Svea\SveaPayment\Exception\OrderNotInvoiceableException;
use Svea\SveaPayment\Model\Payment\AdditionalData;

class Invoicing
{
    /**
     * @var AdditionalData
     */
    private AdditionalData $paymentData;

    /**
     * @param AdditionalData $paymentData
     */
    public function __construct(
        AdditionalData $paymentData
    ) {
        $this->paymentData = $paymentData;
    }

    /**
     * @param OrderInterface $order
     *
     * @return Invoice
     * @throws OrderNotInvoiceableException
     * @throws LocalizedException
     */
    public function createInvoice(OrderInterface $order): Invoice
    {
        if (!$order->canInvoice()) {
            throw new OrderNotInvoiceableException(\__('Order cannot be invoiced'), $order);
        }
        $payment = $order->getPayment();
        $transactionId = $this->paymentData->getSveaTransactionId($payment);
        $payment->setTransactionId($transactionId);
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
        $invoice->register();
        /**
         * Create transaction
         */
        $payment->addTransaction(TransactionInterface::TYPE_CAPTURE, $invoice, true);
        if ($invoice->canCapture()) {
            $invoice->capture();
        }
        /** deprecated ?? */
        $invoice->save();
        $order->addRelatedObject($invoice);

        return $invoice;
    }
}
