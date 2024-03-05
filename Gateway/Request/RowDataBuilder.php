<?php

namespace Svea\SveaPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Svea\SveaPayment\Gateway\Data\AmountHandler;
use function count;

class RowDataBuilder implements BuilderInterface
{
    /**
     * The count or number of order rows as an integer. Max length 4
     */
    const ROWS = 'pmt_rows';

    /**
     * Rows data
     */
    const ROWS_DATA = 'pmt_rows_data';

    /**
     * The total gross sum of row types 1, 4, 5 and 6
     */
    const AMOUNT = 'pmt_amount';

    /**
     * The total gross sum of row types 2 and 3
     */
    const SELLER_COSTS = 'pmt_sellercosts';

    /**
     * @var AmountHandler
     */
    private $amountHandler;

    /**
     * @var RowBuilderInterface
     */
    private $rowBuilders;

    public function __construct(
        AmountHandler $amountHandler,
        array $rowBuilders = []
    ) {
        $this->amountHandler = $amountHandler;
        $this->rowBuilders = $rowBuilders;
    }

    public function build(array $buildSubject): array
    {
        $totalAmount = 0;
        $sellerCosts = 0;
        $productRows = [];
        foreach ($this->rowBuilders as $rowBuilder) {
            if ($rowBuilder instanceof RowBuilderInterface) {
                $result = $rowBuilder->build($buildSubject, $totalAmount, $sellerCosts);
                $totalAmount = $result[RowBuilderInterface::TOTAL_AMOUNT];
                $sellerCosts = $result[RowBuilderInterface::SELLER_COSTS];
                if (!empty($result[RowBuilderInterface::ROW])) {
                    if (isset($result[RowBuilderInterface::ROW][RowBuilderInterface::TYPE])) {
                        $productRows[] = $result[RowBuilderInterface::ROW];
                    } else {
                        foreach ($result[RowBuilderInterface::ROW] as $rowEntry) {
                            $productRows[] = $rowEntry;
                        }
                    }
                }
            }
        }

        $result = [
            self::ROWS => count($productRows),
            self::ROWS_DATA => $productRows,
            self::AMOUNT => $this->amountHandler->formatFloat($totalAmount),
            self::SELLER_COSTS => $this->amountHandler->formatFloat($sellerCosts),
        ];

        return $result;
    }
}
