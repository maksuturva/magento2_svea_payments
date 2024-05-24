<?php

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\Request\RowBuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee;

class HandlingFeeRowBuilder implements RowBuilderInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var AmountHandler
     */
    private $amountHandler;

    /**
     * @var HandlingFee
     */
    private $handlingFee;

    /**
     * @param SubjectReaderInterface $subjectReader
     * @param AmountHandler $amountHandler
     * @param HandlingFee $handlingFee
     */
    public function __construct(
        SubjectReaderInterface $subjectReader,
        AmountHandler          $amountHandler,
        HandlingFee            $handlingFee
    ) {
        $this->subjectReader = $subjectReader;
        $this->amountHandler = $amountHandler;
        $this->handlingFee = $handlingFee;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        $amount = $this->handlingFee->getValue($payment->getOrder());
        $taxAmount = $this->handlingFee->getTaxAmount($payment->getOrder());
        $taxPercent = $this->handlingFee->getTaxAmountPercentage($payment->getOrder());
        $row = [];

        if ($amount > 0) {
            $row[] = [
                self::NAME => \__('Invoicing Fee'),
                self::DESC => \__('Invoicing Fee'),
                self::QUANTITY => 1,
                self::DELIVERY_DATE => \date('d.m.Y'),
                self::PRICE_NET => $this->amountHandler->formatFloat($amount),
                self::VAT => $this->amountHandler->formatFloat($taxPercent),
                self::DISCOUNT_PERCENTAGE => $this->amountHandler->formatFloat(0),
                self::TYPE => 3,
            ];
        }

        $sellerCosts += $amount;
        $sellerCosts += $taxAmount;

        return [
            self::TOTAL_AMOUNT => $totalAmount,
            self::SELLER_COSTS => $sellerCosts,
            self::ROW => $row,
        ];
    }
}
