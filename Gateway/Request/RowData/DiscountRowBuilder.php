<?php

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Magento\Framework\App\Config\ScopeConfigInterface;
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

    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        OrderAdapterFactory $orderAdapterFactory,
        AmountHandler $amountHandler,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->subjectReader  = $subjectReader;
        $this->orderAdapterFactory = $orderAdapterFactory;
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
            $discounts = $this->buildRows($orderAdapter->getItems(), $apply_after_discount == '0');

            $description = 'Discount';
            if (!empty($orderAdapter->getDiscountDescription())) {
                $description = 'Discount: ' . $orderAdapter->getDiscountDescription();
            }

            foreach ($discounts as $vat => $discount_data) {
                $totalAmount += $discount_data['gross'];
                $rows[] = [
                    self::NAME => 'Discount',
                    self::DESC => $description,
                    self::QUANTITY => 1,
                    self::DELIVERY_DATE => date('d.m.Y'),
                    self::PRICE_NET => $this->amountHandler->formatFloat($discount_data['net']),
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
    private function buildRows($items, $beforeTax): array
    {
        $rows = [];
        foreach ($items as $item) {
            if ($item->getBaseDiscountAmount() == 0) {
                continue; // No discount on this item
            }
            /* This took a while to figure out, we can solve many diffs by using the DiscountTaxCompensationAmount,
             * tested it that it means the amount subtracted from the product net price to get the VAT to match.
             */
            $discount_net = -($item->getBaseDiscountAmount() - $item->getDiscountTaxCompensationAmount());
            if ($beforeTax) {
                $discount_vat = 0.00;
                $discount_gross = $discount_net;
            } else {
                $discount_vat = $item->getTaxPercent();
                $discount_gross = round($discount_net * (1 + ($discount_vat / 100.0)), 2);
            }

            $key = $this->amountHandler->formatFloat($discount_vat);
            $this->addOrMergeRow($rows, $key, $discount_net, $discount_gross);
        }
        return $rows;
    }


    private function addOrMergeRow(array &$rows, string $vat, float $net, float $gross): void
    {
        if (isset($rows[$vat])) {
            $rows[$vat]['net'] += $net;
            $rows[$vat]['gross'] += $gross;
        } else {
            $rows[$vat] = [
                'net' => $net,
                'gross' => $gross,
            ];
        }
    }

}
