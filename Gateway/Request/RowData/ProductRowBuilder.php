<?php

namespace Svea\SveaPayment\Gateway\Request\RowData;

use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\Request\RowBuilderInterface;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use Svea\SveaPayment\Model\Request\RowDataValidator;
use Magento\Sales\Api\Data\OrderItemInterface;
use function date;

class ProductRowBuilder implements RowBuilderInterface
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
     * @var RowDataValidator
     */
    private $validator;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        AmountHandler $amountHandler,
        RowDataValidator $validator
    ) {
        $this->subjectReader = $subjectReader;
        $this->amountHandler = $amountHandler;
        $this->validator = $validator;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $orderAdapter = $paymentDO->getOrder();

        /* @var $items OrderItemInterface[] */
        $items = $orderAdapter->getItems();

        $data = [
            self::SELLER_COSTS => $sellerCosts,
            self::TOTAL_AMOUNT => $totalAmount,
            self::ROW => [],
        ];

        foreach ($items as $itemId => $item) {
            $sku = $item->getSku();

            $row = [
                self::NAME => $item->getName(),
                self::DESC => $sku,
                self::QUANTITY => $this->amountHandler->formatFloat($item->getQtyToInvoice()),
                self::ARTICLE_NUMBER => $sku,
                self::DELIVERY_DATE => date('d.m.Y'),
                self::PRICE_NET => $this->amountHandler->formatFloat($item->getBasePrice()),
                self::VAT => $this->amountHandler->formatFloat($item->getTaxPercent()),
                self::DISCOUNT_PERCENTAGE => '0,00',
                self::TYPE => 1,
            ];

            $rowData = $this->validator->validate($item, $row, $items, $itemId, $totalAmount);

            if (!$rowData) {
                continue;
            }

            $totalAmount = $rowData[self::TOTAL_AMOUNT];
            $data[self::TOTAL_AMOUNT] = $totalAmount;
            $data[self::ROW][] = $rowData[self::ROW];
        }

        return $data;
    }
}
