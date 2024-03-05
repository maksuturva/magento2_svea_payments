<?php

namespace Svea\SveaPayment\Model\ResourceModel\Migrate;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class MigrateSales implements MigrateSalesInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private AdapterInterface $connection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     */
    public function execute(?int $fromDate = null): void
    {
        $this->connection = $this->resourceConnection->getConnection();
        $this->migrateHandlingFees($fromDate);
        $this->migratePaymentIds($fromDate);
    }

    /**
     * @param int|null $fromDate
     *
     * @return void
     * @throws Exception
     */
    private function migrateHandlingFees(?int $fromDate): void
    {
        $queryData = $this->getQueryData();
        foreach ($queryData as $item) {
            $table = $item['table'];
            $oldColumn = $item['oldColumn'];
            $newColumn = $item['newColumn'];
            $idField = $item['idField'];
            $table = $this->connection->getTableName($table);
            if ($this->columnsExists($table, $oldColumn, $newColumn)
            ) {
                $valuesByIds = $this->getIdValuePairs($table, $oldColumn, $idField, $fromDate);
                try {
                    $this->connection->beginTransaction();
                    foreach ($valuesByIds as $id => $value) {
                        $this->updateColumnValue($table, $newColumn, (float)$value, (int)$id, $idField);
                    }
                    $this->connection->commit();
                } catch (Exception $exception) {
                    $this->connection->rollBack();
                    throw new Exception($exception);
                }
            }
        }
    }

    /**
     * @return array
     */
    private function getQueryData(): array
    {
        return [
            [
                'table' => 'sales_order',
                'oldColumn' => 'handling_fee',
                'newColumn' => 'svea_handling_fee',
                'idField' => 'entity_id'
            ],
            [
                'table' => 'sales_order',
                'oldColumn' => 'base_handling_fee',
                'newColumn' => 'svea_base_handling_fee',
                'idField' => 'entity_id'
            ],
            [
                'table' => 'sales_order',
                'oldColumn' => 'refunded_handling_fee',
                'newColumn' => 'svea_refunded_handling_fee',
                'idField' => 'entity_id'
            ],
            [
                'table' => 'quote',
                'oldColumn' => 'handling_fee',
                'newColumn' => 'svea_handling_fee',
                'idField' => 'entity_id'
            ],
            [
                'table' => 'quote',
                'oldColumn' => 'base_handling_fee',
                'newColumn' => 'svea_base_handling_fee',
                'idField' => 'entity_id'
            ],
            [
                'table' => 'quote_address',
                'oldColumn' => 'handling_fee',
                'newColumn' => 'svea_handling_fee',
                'idField' => 'address_id'
            ],
            [
                'table' => 'quote_address',
                'oldColumn' => 'base_handling_fee',
                'newColumn' => 'svea_base_handling_fee',
                'idField' => 'address_id'
            ],
        ];
    }

    /**
     * @param string $table
     * @param string $oldColumn
     * @param string $newColumn
     *
     * @return bool
     */
    private function columnsExists(string $table, string $oldColumn, string $newColumn): bool
    {
        return $this->connection->tableColumnExists($table, $oldColumn) &&
            $this->connection->tableColumnExists($table, $newColumn);
    }

    /**
     * @param string $table
     * @param string $condColumn
     * @param string $idField
     * @param int|null $fromDate
     *
     * @return array
     */
    private function getIdValuePairs(string $table, string $condColumn, string $idField, ?int $fromDate): array
    {
        $select = $this->connection->select()
            ->from($table,
                [
                    $idField,
                    $condColumn
                ])
            ->where("{$condColumn} > 0 AND {$condColumn} IS NOT NULL AND created_at >= '{$fromDate}'");

        return $this->connection->fetchPairs($select);
    }

    /**
     * @param string $table
     * @param string $columnToUpdate
     * @param float $value
     * @param int $id
     * @param string $idField
     *
     * @return void
     */
    private function updateColumnValue(
        string $table,
        string $columnToUpdate,
        float  $value,
        int    $id,
        string $idField
    ) {
        $where = $this->connection->quoteInto("{$idField} = ? AND ($columnToUpdate IS NULL OR $columnToUpdate <= 0)", $id);
        $this->connection->update($table, [$columnToUpdate => $value], [$where]);
    }

    /**
     * @param int|null $fromDate
     *
     * @return void
     * @throws Exception
     */
    private function migratePaymentIds(?int $fromDate): void
    {
        $table = $this->connection->getTableName('sales_order_payment');
        if ($this->columnsExists($table, 'maksuturva_pmt_id', 'svea_payment_id')) {
            $valuesByIds = $this->getPaymentIdValuePairs($table, $fromDate);
            try {
                $this->connection->beginTransaction();
                foreach ($valuesByIds as $id => $value) {
                    $where = $this->connection->quoteInto("entity_id = ? AND svea_payment_id IS NULL", $id);
                    $this->connection->update($table, ['svea_payment_id' => $value], [$where]);
                }
                $this->connection->commit();
            } catch (Exception $exception) {
                $this->connection->rollBack();
                throw new Exception($exception);
            }
        }
    }

    /**
     * @param string $table
     * @param int|null $fromDate
     *
     * @return array
     */
    private function getPaymentIdValuePairs(string $table, ?int $fromDate): array
    {
        $select = $this->connection->select()
            ->from($table,
                [
                    'entity_id',
                    'maksuturva_pmt_id'
                ])
            ->joinLeft('sales_order', "sales_order.entity_id = {$table}.parent_id", 'created_at')
            ->where("maksuturva_pmt_id IS NOT NULL AND sales_order.created_at >= '{$fromDate}'");

        return $this->connection->fetchPairs($select);
    }
}
