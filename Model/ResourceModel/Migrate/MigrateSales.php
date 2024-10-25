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
        print("Migrating sales data...\n");
        $this->connection = $this->resourceConnection->getConnection();
        print("Connection established...\n");
        print("Migrating handling fees...\n");
        $this->migrateHandlingFees($fromDate);
        print("Migrating payment ids...\n");
        $migratedIds = $this->migratePaymentIds($fromDate);
        print("Migrating payments...\n");
        $this->migratePayments($migratedIds);
        print("Sales data migration completed.\n");
    }

    /** TODO: 
     * order
     * 
     *       * from:
     *        3 | {"maksuturva_transaction_id":"365d590cf9a3a0a3"} 
     *        4 | {"maksuturva_transaction_id":"09587bc0c7ec0e0e"} 
     * to:
     *        5 | {"svea_transaction_id":"W3QvG4Fk3Q1u0farK9mz"}   
     *        6 | {"svea_transaction_id":"3LZlhy96zzG9YSZvpX1k"} 
    */

    /**
     * Use migrated payment ids list to update payment additional info
     * 
     * @param array $migratedIds
     * 
     * @return void
     * @throws Exception
     */
    private function migratePayments(array $migratedIds): void
    {   
        $updatedRows = 0;
        $table = $this->connection->getTableName("sales_order_payment");

        foreach ($migratedIds as $id) {
            $additional_info = $this->getPaymentInfo($table, $id);
            if (empty($additional_info)) {
                continue;
            }
            try {
                $this->connection->beginTransaction();
                $id = $additional_info[0]['entity_id'];
                $value = $additional_info[0]['additional_information'];
                
                print("Updating payment additional info for order id: {$id} and old additional_info: {$value}\n");
                $value = json_decode($value, true);
                $value['svea_method_code'] = $value['sub_payment_method'];
                $value['svea_method_group'] = $value['collated_method'];
                $value['svea_preselected_payment_method'] = $value['maksuturva_preselected_payment_method'];
                $value = json_encode($value);
                
                print("New additional_info: {$value}\n");
                $where = $this->connection->quoteInto("entity_id = ?", $id);
                $this->connection->update($table, ['additional_information' => $value], [$where]);
                
                $this->connection->commit();
                $updatedRows++;
            } catch (Exception $exception) {
                $this->connection->rollBack();
                throw new Exception($exception);
            }       
        }

        print("Updated {$updatedRows} rows.\n");    
    }

    /**
     * Get payment (additional) info by payment id
     * 
     * @param string $table
     * @param string $paymentId
     *
     * @return array
     */
    private function getPaymentInfo(string $table, string $paymentId): array
    {
        $selectClause = "select entity_id,additional_information from {$table} where additional_information IS NOT NULL AND svea_payment_id LIKE '{$paymentId}'";
        return $this->connection->fetchAll($selectClause);
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
     * @return array    Migrated payment ids
     * @throws Exception
     */
    private function migratePaymentIds(?int $fromDate): array
    {
        $migratedIds = []; // List of migrated ids
        $table = $this->connection->getTableName('sales_order_payment');
        if ($this->columnsExists($table, 'maksuturva_pmt_id', 'svea_payment_id')) {
            $valuesByIds = $this->getPaymentIdValuePairs($table, $fromDate);
            try {
                $this->connection->beginTransaction();
                foreach ($valuesByIds as $id => $value) {
                    $where = $this->connection->quoteInto("entity_id = ? AND svea_payment_id IS NULL", $id);
                    $this->connection->update($table, ['svea_payment_id' => $value], [$where]);
                    $migratedIds[] = $value; // Add migrated id to the list
                }
                $this->connection->commit();
                return $migratedIds; // Return migrated ids
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
