<?php

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\Request\RowBuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use function __;

class ShippingRowBuilder implements RowBuilderInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var AmountHandler
     */
    private $amountHandler;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        TimezoneInterface $timezone,
        AmountHandler $amountHandler
    ) {
        $this->subjectReader  = $subjectReader;
        $this->timezone = $timezone;
        $this->amountHandler = $amountHandler;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts) : array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $orderAdapter = $paymentDO->getOrder();

        $shippingDescription = $orderAdapter->getShippingDescription() ?: 'Free Shipping';
        $shippingInclTax = $orderAdapter->getBaseShippingAmountInclTax();
        $shippingTaxRate = $orderAdapter->getShippingTaxRate();

        $row = [
            self::NAME => __('Shipping'),
            self::DESC => $shippingDescription,
            self::QUANTITY => 1,
            self::DELIVERY_DATE => $this->timezone->date()->format('d.m.Y'),
            self::PRICE_GROSS => $this->amountHandler->formatFloat($shippingInclTax),
            self::VAT => $this->amountHandler->formatFloat($shippingTaxRate),
            self::DISCOUNT_PERCENTAGE => '0,00',
            self::TYPE => 2,
        ];

        $sellerCosts += $shippingInclTax;

        return [
            self::TOTAL_AMOUNT => $totalAmount,
            self::SELLER_COSTS => $sellerCosts,
            self::ROW => $row,
        ];
    }
}
