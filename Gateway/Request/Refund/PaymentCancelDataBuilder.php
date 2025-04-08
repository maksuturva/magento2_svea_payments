<?php

namespace Svea\SveaPayment\Gateway\Request\Refund;

use Exception;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item;
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
            $creditmemo = $payment->getCreditmemo();
            $N = $this->addDiscountRow($data, $creditmemo);
            $this->addShippingRow($data, $creditmemo, $N);
            $N = $this->addItemRows($data, $creditmemo, $N);
        }
        /** Add IBAN number to REFUND_AFTER_SETTLEMENT cases */
        if ($cancelType === RefundCommand::CANCEL_TYPE_REFUND_AFTER_SETTLEMENT) {
            $data['pmtc_payeribanrefund'] = $this->getIBANNumber($payment);
        }

        return $data;
    }

    /**
     * Discount must be accounted for in the number of rows as it was sent in the original order.
     * But we will not refund the original row, but rather add a new row with the adjustment, discount is always negative.
     */
    private function addDiscountRow(array &$data, Creditmemo $creditmemo): int
    {
        if ($creditmemo->getOrder()->getDiscountAmount() < 0) {
            $data['pmtc_additional_row_name1'] = 'Discount';
            $data['pmtc_additional_row_desc1'] = 'Discount reduction';
            $data['pmtc_additional_row_quantity1'] = 1;
            $data['pmtc_additional_row_price_gross1'] = $this->amountHandler->formatFloat(-$creditmemo->getBaseDiscountAmount());
            $data['pmtc_additional_row_vat1'] = $this->amountHandler->formatFloat(0);
            return 2;
        }
        return 1;
    }

    /**
     * Refund the items with quantity greater than 0 from the credit memo, order needs to be the same as orignal order.
     * Configurable products are accounted for in that we skip the simple subitems
     */
    private function addItemRows(array &$data, Creditmemo $creditmemo, int $N): int
    {
        /** @var Item $item */
        foreach ($creditmemo->getItems() as $item) {
            $orderItem = $item->getOrderItem();
            /* Skip the simple subitems of configurable products */
            if ($orderItem->getProductType() === 'simple' && $orderItem->getParentItem() !== null) {
                continue;
            }
            if ($item->getQty() > 0) {
                $data["pmtc_refund_quantity_of_original_row{$N}"] = $this->amountHandler->formatFloat($item->getQty());
            }
            $N += 1;
        }

        return $N;
    }

    /**
     * If shipping is refunded, add a additional row for it
     */
    private function addShippingRow(array &$data, Creditmemo $creditmemo, int $N): void
    {
        if ($creditmemo->getBaseShippingAmount() > 0) {
            $shippingNet = $creditmemo->getBaseShippingAmount();
            $vat = (\round(($creditmemo->getBaseShippingTaxAmount() / $shippingNet) * 100 * 2) / 2);
            $data["pmtc_additional_row_name{$N}"] = 'Shipping';
            $data["pmtc_additional_row_desc{$N}"] = 'Shipping refund';
            $data["pmtc_additional_row_quantity{$N}"] = 1;
            $data["pmtc_additional_row_price_gross{$N}"] = $this->amountHandler->formatFloat(-$creditmemo->getBaseShippingInclTax());
            $data["pmtc_additional_row_vat{$N}"] = $this->amountHandler->formatFloat($vat);
        }
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
