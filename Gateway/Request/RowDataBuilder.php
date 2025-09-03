<?php

namespace Svea\SveaPayment\Gateway\Request;

use Magento\Framework\Logger\Monolog as Logger;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
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
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var AmountHandler
     */
    private $amountHandler;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var RowBuilderInterface
     */
    private $rowBuilders;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        AmountHandler $amountHandler,
        Logger $logger,
        array $rowBuilders = []
    ) {
        $this->subjectReader = $subjectReader;
        $this->amountHandler = $amountHandler;
        $this->logger = $logger;
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

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $orderTotal = $order->getGrandTotalAmount() - $sellerCosts;

        if (abs($totalAmount - $orderTotal) > 0.005) {
             $this->logger->debug('Svea Payments: Collected total amount does not match Magento order total, using Magento value.', [
                'collected_total' => $totalAmount,
                'order_total' => $orderTotal,
            ]);
        }

        $result = [
            self::ROWS => count($productRows),
            self::ROWS_DATA => $productRows,
            self::AMOUNT => $this->amountHandler->formatFloat($orderTotal),
            self::SELLER_COSTS => $this->amountHandler->formatFloat($sellerCosts),
        ];

        return $result;
    }
}
