<?php

namespace Svea\SveaPayment\Gateway\Response\Refund;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Svea\SveaPayment\Gateway\SubjectReader;

class PaymentCancelHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * @param array $handlingSubject
     * @param array $response
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $payment = $this->subjectReader->readPayment($handlingSubject)->getPayment();
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        /** @var InvoiceInterface $invoice */
        $invoice = $payment->getCreditmemo()->getInvoice();
        $canRefundMore = $invoice->canRefund();
        if ($handlingSubject['action_code'] === 'REFUND_AFTER_SETTLEMENT') {
            $this->handleAfterSettlement($order, $payment, $response);
        }
        $transactionId = $this->resolveRefundTransactionId($handlingSubject['cancel_type'], $response);
        $payment->setTransactionId($transactionId)
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(!$canRefundMore);
    }

    /**
     * @param OrderInterface $order
     * @param InfoInterface $payment
     * @param array $response
     */
    private function handleAfterSettlement(OrderInterface $order, InfoInterface $payment, array $response): void
    {
        if ($response['pmtc_returncode'] === '00') {
            /** Add information to order & creditmemo for paying refund money to Svea */
            $message = $this->buildRefundInformationNote($response);
            $order->addCommentToStatusHistory($message);
            $payment->getCreditmemo()->addComment($message);
        }
    }

    /**
     * @param array $response
     *
     * @return string
     */
    private function buildRefundInformationNote(array $response): string
    {
        $message = [
            \__('Svea Payments:'),
            \__('Send refund money to'),
            \__('Name: %1', $response['pmtc_pay_with_recipientname']),
            \__('Iban: %1', $response['pmtc_pay_with_iban']),
            \__('Reference: %1', $response['pmtc_pay_with_reference']),
            \__('Amount: %1', $response['pmtc_pay_with_amount']),
            \__('Svea will refund the payer after receiving money.'),
        ];

        return \implode(\PHP_EOL, $message);
    }

    /**
     * @param string $cancelType
     * @param array $response
     *
     * @return string
     */
    private function resolveRefundTransactionId(string $cancelType, array $response): string
    {
        $pmtId = $response['pmtc_id'];
        if ($cancelType === 'FULL_REFUND') {
            return \sprintf('%s-refund', $pmtId);
        } else {
            return \sprintf('%s%s-refund', $pmtId, \time());
        }
    }
}
