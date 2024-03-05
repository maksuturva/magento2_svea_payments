<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\Request\RowBuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use function __;
use function date;

class GiftCardRowBuilder implements RowBuilderInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private SubjectReaderInterface $subjectReader;

    /**
     * @var AmountHandler
     */
    private AmountHandler $amountHandler;

    /**
     * @param SubjectReaderInterface $subjectReader
     * @param AmountHandler $amountHandler
     */
    public function __construct(
        SubjectReaderInterface $subjectReader,
        AmountHandler          $amountHandler
    ) {
        $this->subjectReader = $subjectReader;
        $this->amountHandler = $amountHandler;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $orderAdapter = $paymentDO->getOrder();
        $row = [];
        $amount = $orderAdapter->getBaseGiftCardAmount();
        if ($amount > 0) {
            $row[] = $this->getGiftCardRow($amount);
            $totalAmount -= $amount;
        }

        return [
            self::TOTAL_AMOUNT => $totalAmount,
            self::SELLER_COSTS => $sellerCosts,
            self::ROW => $row,
        ];
    }

    /**
     * Discount type row pmt_row_price_net is always negative
     *
     * @param float $amount
     *
     * @return array
     */
    private function getGiftCardRow(float $amount): array
    {
        return [
            self::NAME => __('Gift Card'),
            self::DESC => __('Gift Card'),
            self::QUANTITY => 1,
            self::DELIVERY_DATE => date('d.m.Y'),
            self::PRICE_NET => $this->amountHandler->formatFloat(-$amount),
            self::VAT => $this->amountHandler->formatFloat(0),
            self::DISCOUNT_PERCENTAGE => $this->amountHandler->formatFloat(0),
            self::TYPE => 6,
        ];
    }
}
