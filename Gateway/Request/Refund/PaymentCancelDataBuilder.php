<?php

namespace Svea\SveaPayment\Gateway\Request\Refund;

use Exception;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Svea\SveaPayment\Gateway\Command\RefundCommand;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;

class PaymentCancelDataBuilder implements BuilderInterface
{
    const SUBJECT_AMOUNT = 'amount';
    const SUBJECT_ACTION_CODE = 'action_code';
    const SUBJECT_CANCEL_TYPE = 'cancel_type';

    /**
     * @var SubjectReaderInterface
     */
    private SubjectReaderInterface $subjectReader;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var AmountHandler
     */
    private AmountHandler $amountHandler;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        Config                 $config,
        AmountHandler          $amountHandler
    ) {
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->amountHandler = $amountHandler;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     * @throws Exception
     */
    public function build(array $buildSubject)
    {
        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();
        $refundAmount = $this->subjectReader->read($buildSubject, self::SUBJECT_AMOUNT);
        $actionCode = $this->subjectReader->read($buildSubject, self::SUBJECT_ACTION_CODE);
        $cancelType = $this->subjectReader->read($buildSubject, self::SUBJECT_CANCEL_TYPE);
        $order = $payment->getOrder();
        $data = [
            'pmtc_action' => $actionCode,
            'pmtc_version' => '0005',
            'pmtc_sellerid' => $this->config->getSellerId(),
            'pmtc_id' => $this->getTransactionId($payment),
            'pmtc_amount' => $this->amountHandler->formatFloat($order->getGrandTotal()),
            'pmtc_currency' => $order->getBaseCurrencyCode(),
            'pmtc_canceltype' => $cancelType,
            'pmtc_resptype' => 'XML',
            'pmtc_keygeneration' => $this->config->getKeyVersion(),
        ];
        /** If not full refund, add cancel amount */
        if ($cancelType !== 'FULL_REFUND') {
            $data['pmtc_cancelamount'] = $this->amountHandler->formatFloat($refundAmount);
        }
        /** Add IBAN number to REFUND_AFTER_SETTLEMENT cases */
        if ($cancelType === RefundCommand::CANCEL_TYPE_REFUND_AFTER_SETTLEMENT) {
            $data['pmtc_payeribanrefund'] = $this->getIBANNumber($payment);
        }

        return $data;
    }

    /**
     * @param InfoInterface $payment
     *
     * @return mixed
     * @throws Exception
     */
    private function getTransactionId(InfoInterface $payment)
    {
        $id = $payment->getParentTransactionId() ? $payment->getParentTransactionId() : $payment->getLastTransId();
        if (!$id) {
            throw new Exception('Can\'t refund online because transaction id is missing');
        }

        return $id;
    }

    /**
     * @param Payment $payment
     *
     * @return string|null
     */
    private function getIBANNumber(Payment $payment)
    {
        $creditmemo = $payment->getCreditmemo();

        return $creditmemo->getData('svea_iban');
    }
}
