<?php

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Svea\SveaPayment\Gateway\Data\AmountHandler;
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
     * @var AmountHandler
     */
    private $amountHandler;

    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        AmountHandler $amountHandler,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader  = $subjectReader;
        $this->amountHandler = $amountHandler;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts) : array
    {
        /* Generate one discount row per VAT class that appears in the order so the discounts are visible
         * including VAT. Magento has a lot of settings for tax caclulation, not all of them make sense
         * but this implementation gets same result for all the ones Magento does not explicitly warn against.
         */
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $orderAdapter = $paymentDO->getOrder();
        $rows = [];

        $baseDiscountAmount = $orderAdapter->getBaseDiscountAmount();
        if ($baseDiscountAmount && $baseDiscountAmount != 0) {
            /* In magento there is an option for "Apply Customer Tax" "Before Discount"(0) / "After Discount"(1).
             * If it is set to before discount then the discounts are VAT 0% as they do not change the VAT of the order.
             * This also affcts our running total discount amount.
             */
            $apply_after_discount = $this->scopeConfig->getValue('tax/calculation/apply_after_discount');
            $discounts = $this->buildRows($orderAdapter->getItems(), $apply_after_discount == '1');

            $description = 'Discount';
            if (!empty($orderAdapter->getDiscountDescription())) {
                $description = 'Discount: ' . $orderAdapter->getDiscountDescription();
            }

            foreach ($discounts as $vat => $discount_data) {
                $rounded_gross = round($discount_data['gross']-0.0009, 2, PHP_ROUND_HALF_UP);
                $totalAmount += $rounded_gross;
                $rows[] = [
                    self::NAME => 'Discount',
                    self::DESC => $description,
                    self::QUANTITY => 1,
                    self::DELIVERY_DATE => date('d.m.Y'),
                    self::PRICE_GROSS => $this->amountHandler->formatFloat($rounded_gross),
                    self::VAT => $vat,
                    self::DISCOUNT_PERCENTAGE => '0,00',
                    self::TYPE => 6,
                ];
            }
        }

        return [
            self::TOTAL_AMOUNT => $totalAmount,
            self::SELLER_COSTS => $sellerCosts,
            self::ROW => $rows,
        ];
    }

    /** Build up discount row per VAT class that appears in the order */
    private function buildRows($items, $taxesAfterDiscount): array
    {
        $rows = [];
        foreach ($items as $item) {
            if ($item->getBaseDiscountAmount() == 0) {
                continue; // No discount on this item
            }
            $discount = -$item->getBaseDiscountAmount();
            $discount_vat_percent = $item->getTaxPercent();
            if ($taxesAfterDiscount) {
                if ($item->getDiscountTaxCompensationAmount() > 0) {
                    $discount_gross = $discount;
                } else {
                    $total_net = $item->getBaseRowTotal();
                    $discounted_total = ($total_net + $discount + $item->getBaseTaxAmount()); ;
                    $discount_gross = $discounted_total - $item->getBaseRowTotalInclTax();
                }
            } else {
                $discount_gross = $discount;
                $discount_vat_percent = 0.00;
            }

            $key = $this->amountHandler->formatFloat($discount_vat_percent);
            $this->addOrMergeRow($rows, $key, $discount_gross);
        }
        return $rows;
    }


    private function addOrMergeRow(array &$rows, string $vat, float $gross): void
    {
        if (isset($rows[$vat])) {
            $rows[$vat]['gross'] += $gross;
        } else {
            $rows[$vat] = [
                'gross' => $gross
            ];
        }
    }

}
