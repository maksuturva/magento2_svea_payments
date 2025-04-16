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
        /** If not full refund, add cancel amount and row specifications */
        if ($cancelType !== 'FULL_REFUND') {
            $data['pmtc_cancelamount'] = $this->amountHandler->formatFloat($refundAmount);
            $creditmemo = $payment->getCreditmemo();
            $offset = $this->addDiscountRow($data, $creditmemo);
            $offset = $this->addShippingRow($data, $creditmemo, $offset);
            $offset = $this->addAdjustmentRow($data, $creditmemo, $offset);
            $this->addHandlingFeeRow($data, $creditmemo, $offset);
            $this->addItemRows($data, $creditmemo);
        }
        /** Add IBAN number to REFUND_AFTER_SETTLEMENT cases */
        if ($cancelType === RefundCommand::CANCEL_TYPE_REFUND_AFTER_SETTLEMENT) {
            $data['pmtc_payeribanrefund'] = $this->getIBANNumber($payment);
        }

        return $data;
    }

    /**
     * Discount refund is not 100% of the originial row so we add an additional row,
     * discount amount is negative so we negate it when sending it in.
     * Return the offset for more additional rows.
     */
    private function addDiscountRow(array &$data, Creditmemo $creditmemo): int
    {
        if ($creditmemo->getBaseDiscountAmount() < 0) {
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
     * If shipping is refunded, we add a additional row for it, we negate the refund amount to send it to.
     * Offset is to adjust the additional row number if we added row 1 for discount already.
     */
    private function addShippingRow(array &$data, Creditmemo $creditmemo, int $offset): int
    {
        if ($creditmemo->getBaseShippingAmount() > 0) {
            $shippingNet = $creditmemo->getBaseShippingAmount();
            $vat = (\round(($creditmemo->getBaseShippingTaxAmount() / $shippingNet) * 100 * 2) / 2);
            $data["pmtc_additional_row_name{$offset}"] = 'Shipping';
            $data["pmtc_additional_row_desc{$offset}"] = 'Shipping refund';
            $data["pmtc_additional_row_quantity{$offset}"] = 1;
            $data["pmtc_additional_row_price_gross{$offset}"] = $this->amountHandler->formatFloat(-$creditmemo->getBaseShippingInclTax());
            $data["pmtc_additional_row_vat{$offset}"] = $this->amountHandler->formatFloat($vat);
            return $offset + 1;
        }
        return $offset;
    }

    /**
     * If there is an adjustment amount, we add an additional row for it.
     * Offset is to adjust the additional row number if we added rows for discount or shipping already.
     */
    private function addAdjustmentRow(array &$data, Creditmemo $creditmemo, int $offset): int
    {
        if ($creditmemo->getAdjustmentPositive() > 0 || $creditmemo->getAdjustmentNegative() > 0) {
            $data["pmtc_additional_row_name{$offset}"] = 'Adjustment';
            $data["pmtc_additional_row_desc{$offset}"] = 'Adjustment refund';
            $data["pmtc_additional_row_quantity{$offset}"] = 1;
            $data["pmtc_additional_row_price_gross{$offset}"] = $this->amountHandler->formatFloat(-$creditmemo->getAdjustment());
            $data["pmtc_additional_row_vat{$offset}"] = $this->amountHandler->formatFloat(0);
            return $offset + 1;
        }
        return $offset;
    }

    /**
     * If there is an handling fee refund, we add an additional row for it.
     * Offset is to adjust the additional row number if we added rows for discount or shipping already.
     */
    private function addHandlingFeeRow(array &$data, Creditmemo $creditmemo, int $offset): void
    {
        if ($creditmemo->getSveaBaseHandlingFee() > 0) {
            $feeIncVat = $creditmemo->getSveaBaseHandlingFee() + $creditmemo->getSveaBaseHandlingFeeTax();
            $vat = (\round(($creditmemo->getSveaBaseHandlingFeeTax() / $creditmemo->getSveaBaseHandlingFee()) * 100 * 2) / 2);
            $data["pmtc_additional_row_name{$offset}"] = 'Handling fee';
            $data["pmtc_additional_row_desc{$offset}"] = 'Handling fee refund';
            $data["pmtc_additional_row_quantity{$offset}"] = 1;
            $data["pmtc_additional_row_price_gross{$offset}"] = $this->amountHandler->formatFloat(-$feeIncVat);
            $data["pmtc_additional_row_vat{$offset}"] = $this->amountHandler->formatFloat($vat);
        }
    }


    /**
     * Refund the items with quantity greater than 0 from the credit memo, rows are numbered from 1 and same sort as original order.
     * If original order had discount we must offset the row number additionally by 1.
     * Configurable products are accounted for in that we skip the related child items.
     */
    private function addItemRows(array &$data, Creditmemo $creditmemo): void
    {
        $N = 1;
        if ($creditmemo->getOrder()->getDiscountAmount() < 0) {
            $N = 2;
        }

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
