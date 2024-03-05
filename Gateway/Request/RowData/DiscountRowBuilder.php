<?php

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\Data\Order\OrderAdapterFactory;
use Svea\SveaPayment\Gateway\Request\RowBuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;

use function date;

class DiscountRowBuilder implements RowBuilderInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var OrderAdapterFactory
     */
    private $orderAdapterFactory;

    /**
     * @var AmountHandler
     */
    private $amountHandler;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        AmountHandler $amountHandler
    ) {
        $this->subjectReader  = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
        $this->amountHandler = $amountHandler;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts) : array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $orderAdapter = $paymentDO->getOrder();
        $discount = 0;
        $row = [];

        if ($orderAdapter->getBaseDiscountAmount() && $orderAdapter->getBaseDiscountAmount() != 0) {
            $discount = $orderAdapter->getBaseDiscountAmount();
            if ($discount > ($orderAdapter->getBaseShippingAmount() + $totalAmount)) {
                $discount = ($orderAdapter->getBaseShippingAmount() + $totalAmount);
            }
            $description = 'Discount';
            if (!empty($orderAdapter->getDiscountDescription())) {
                $description = 'Discount: ' . $orderAdapter->getDiscountDescription();
            }
            $row[] = [
                self::NAME => 'Discount',
                self::DESC => $description,
                self::QUANTITY => 1,
                self::DELIVERY_DATE => date('d.m.Y'),
                self::PRICE_NET => $this->amountHandler->formatFloat($discount),
                self::VAT => $this->amountHandler->formatFloat(0),
                self::DISCOUNT_PERCENTAGE => '0,00',
                self::TYPE => 6,
            ];
        }

        $totalAmount += $discount;

        return [
            self::TOTAL_AMOUNT => $totalAmount,
            self::SELLER_COSTS => $sellerCosts,
            self::ROW => $row,
        ];
    }
}
