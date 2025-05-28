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

        $baseDiscountAmount = $orderAdapter->getBaseDiscountAmount();
        if ($baseDiscountAmount && $baseDiscountAmount != 0) {
            $discounts = $this->buildRows($orderAdapter->getItems());

            $description = 'Discount';
            if (!empty($orderAdapter->getDiscountDescription())) {
                $description = 'Discount: ' . $orderAdapter->getDiscountDescription();
            }
            /* In magento there is an option for "Apply Customer Tax" "Before Discount"(0) / "After Discount"(1).
             * If it is set to before discount then the discounts are VAT 0% as they do not change the VAT of the order.
             * This also affcts our running total discount amount.
             */
            $apply_after_discount = $this->scopeConfig->getValue('tax/calculation/apply_after_discount');

            $rows = [];
            foreach ($discounts as $vat => $discount_data) {
                if ($apply_after_discount == '0') {
                    $vat = '0,0';
                    $runningTotal = $discount_data['net'];
                } else {
                    //$vat = $vat;
                    $runningTotal = $discount_data['gross'];
                }
                $totalAmount += $runningTotal;
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
            self::ROW => $rows ?: [],
        ];
    }

    /** Build up discount row per VAT class that appears in the order */
    private function buildRows($items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $discount_vat = $item->getTaxPercent();
            /* This took a while to figure out, we can solve many diffs by using the DiscountTaxCompensationAmount,
             * Tested it that it means the amount subtracted from the product net price to get the VAT to match
             */
            $discount_net = -($item->getBaseDiscountAmount() - $item->getDiscountTaxCompensationAmount());
            $discount_gross = round($discount_net * (1 + ($discount_vat / 100.0)), 2);

            $key = $this->amountHandler->formatFloat($discount_vat);
            if (isset($rows[$key])) {
                $rows[$key]['net'] += $discount_net;
                $rows[$key]['gross'] += $discount_gross;
            } else {
                $rows[$key] = [
                    'net' => $discount_net,
                    'gross' => $discount_gross,
                ];
            }
        }
        return $rows;
    }

}
