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
        // Split up the function into parts
        // Get the discount row per VAT class
        // How do i figure out vat classes in the order?
        // Maybe just iterate items and keep a running sum of the discount
        // per vat class

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $orderAdapter = $paymentDO->getOrder();
        $discount = 0;
        $row = [];

        if ($orderAdapter->getBaseDiscountAmount() && $orderAdapter->getBaseDiscountAmount() != 0) {
            $discount = $orderAdapter->getBaseDiscountAmount();
            if ($discount > ($orderAdapter->getBaseShippingAmount() + $totalAmount)) {
                $discount = ($orderAdapter->getBaseShippingAmount() + $totalAmount);
            }

            $baseGrandTotal = $orderAdapter->getGrandTotalAmount()
                - $orderAdapter->getBaseShippingAmount();
                - $orderAdapter->getBaseShippingTaxAmount();
                - $orderAdapter->getHandlingFee();

            $discount_real = $baseGrandTotal
                - $orderAdapter->getBaseSubtotalInclTax()
                + $orderAdapter->getBaseGiftCardAmount();

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
                self::VAT => $this->amountHandler->formatFloat(25.5),
                self::DISCOUNT_PERCENTAGE => '0,00',
                self::TYPE => 6,
            ];
        }

        $totalAmount += $discount_real;

        return [
            self::TOTAL_AMOUNT => $totalAmount,
            self::SELLER_COSTS => $sellerCosts,
            self::ROW => $row,
        ];
    }


}
