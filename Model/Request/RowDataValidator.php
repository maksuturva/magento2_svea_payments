<?php

namespace Svea\SveaPayment\Model\Request;

use Magento\Bundle\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item;
use Svea\SveaPayment\Gateway\Request\RowBuilderInterface;

use function in_array;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function str_replace;

class RowDataValidator
{
    /**
     * Validates rows
     *
     * @param Item $item
     * @param array $row
     * @param OrderItemInterface[] $items
     * @param int $itemId
     * @param float $totalAmount
     * @return array|false
     */
    public function validate(Item $item, array $row, array $items, int $itemId, float $totalAmount)
    {
        $itemData = $item->getData();

        if ($item->getProductType() === Configurable::TYPE_CODE &&
            $item->getChildrenItems() !== null && !empty($item->getChildrenItems())
        ) {
            return $this->childRows($item, $items, $totalAmount, $itemData, $itemId, $row);
        }

        if ($item->getParentItem() !== null && $item->getParentItem()->getProductType() === Configurable::TYPE_CODE) {
            return false;
        }

        if ($item->getProductType() === Type::TYPE_CODE && $item->getChildrenItems() !== null
            && !empty($item->getChildrenItems())
        ) {
            return $this->bundleChildRows($item, $totalAmount, $itemData, $row);
        }

        if ($item->getParentItem() !== null && $item->getParentItem()->getProductType() === Type::TYPE_CODE) {
            return $this->bundleRows($item, $totalAmount, $itemData, $row);
        }

        $totalAmount += $itemData['base_price_incl_tax'] * $item->getQtyToInvoice();

        return [
            RowBuilderInterface::TOTAL_AMOUNT => $totalAmount,
            RowBuilderInterface::ROW => $row,
        ];
    }

    /**
     * @param Item $item
     * @param OrderItemInterface[] $items
     * @param float $totalAmount
     * @param mixed $itemData
     * @param int $itemId
     * @param array $row
     * @return array|false
     */
    private function childRows(Item $item, array $items, float $totalAmount, $itemData, int $itemId, array $row)
    {
        $children = $item->getChildrenItems();

        if (\count($children) !== 1) {
            return false;
        }

        $child = $children[0];
        $row[RowBuilderInterface::NAME] = $child->getName();
        $childSku = $child->getSku();

        if ($childSku != 0) {
            if (mb_strlen($childSku) > 10) {
                $childSku = mb_substr($childSku, 0, 10);
            }

            $row[RowBuilderInterface::ARTICLE_NUMBER] = $childSku;

        }
        if ($childSku != 0) {
            $row[RowBuilderInterface::DESC] = $childSku;
        }

        $totalAmount += $itemData["base_price_incl_tax"] * $item->getQtyToInvoice();

        return [
            RowBuilderInterface::TOTAL_AMOUNT => $totalAmount,
            RowBuilderInterface::ROW => $row,
        ];
    }

    /**
     * @param Item $item
     * @param float $totalAmount
     * @param mixed $itemData
     * @param array $row
     * @return array
     */
    private function bundleChildRows(Item $item, float $totalAmount, $itemData, array $row): array
    {
        $row[RowBuilderInterface::QUANTITY] = str_replace('.', ',', sprintf("%.2f", $item->getQtyOrdered()));

        // If price is fully dynamic
        if ($item->getProduct()->getPriceType() == 0) {
            $row[RowBuilderInterface::PRICE_GROSS] = str_replace('.', ',', sprintf("%.2f", '0'));
        } else {
            $totalAmount += $itemData["price_incl_tax"] * $item->getQtyOrdered();
        }

        $row['pmt_row_type'] = 4;

        return [
            RowBuilderInterface::TOTAL_AMOUNT => $totalAmount,
            RowBuilderInterface::ROW => $row,
        ];
    }

    /**
     * @param Item $item
     * @param float $totalAmount
     * @param mixed $itemData
     * @param array $row
     * @return array
     */
    private function bundleRows(Item $item, float $totalAmount, $itemData, array $row): array
    {
        $parentQty = $item->getParentItem()->getQtyOrdered();

        $unitQty = $item->getQtyOrdered() / $parentQty;

        $row[RowBuilderInterface::NAME] = $unitQty . " X " . $parentQty . " X " . $item->getName();
        $row[RowBuilderInterface::QUANTITY] = str_replace('.', ',', sprintf("%.2f", $item->getQtyOrdered()));
        $totalAmount += $itemData["base_price_incl_tax"] * $item->getQtyOrdered();
        $row['pmt_row_type'] = 4;

        return [
            RowBuilderInterface::TOTAL_AMOUNT => $totalAmount,
            RowBuilderInterface::ROW => $row,
        ];
    }
}
