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
     * Execute migration
     * 
     * @inheritDoc
     */
    public function execute(?int $fromDate = null): void
    {
        print("🚀 Migrating sales data \n");
        $this->connection = $this->resourceConnection->getConnection();
        print("\t 📁 Migrating handling fees...\n");
        $this->migrateHandlingFees($fromDate);
        print("\t 📁 Migrating payment ids...\n");
        $migratedIds = $this->migratePaymentIds($fromDate);
        $cntIds = count($migratedIds);
        if ($cntIds == 0) {
            print("\t ❌ No payment ids found for migration.\n");
        } else {     
            print("\t 📁 Found {$cntIds} payments, migrating...\n");
            $this->migratePayments($migratedIds);
            print("👌 Sales data migration completed.\n");
        }
    }

    /**
     * Use migrated payment ids list to update payment additional info
     * 
     * Debugging: select entity_id,additional_data,svea_payment_id,additional_information from sales_order_payment;
     * @param array $migratedIds
     * 
     * @return void
     * @throws Exception
     */
    private function migratePayments(array $migratedIds): void
    {   
        $updatedRows = 0;
        $skippedRows = 0;
        $table = $this->connection->getTableName("sales_order_payment");

        foreach ($migratedIds as $id) {
            $order_payment = $this->getPaymentInfo($table, $id);

            if (empty($order_payment)) {
                $skippedRows++;
                continue;
            }
            try {
                // Start transaction at the beginning as we want to catch all errors and to be able to rollback
                $this->connection->beginTransaction();

                $id = $order_payment[0]['entity_id'];
                $ai = $order_payment[0]['additional_information'];
                $ad = $order_payment[0]['additional_data'];
                
                $ad = json_decode($ad, true);

                // check if the payment is created with the old module, if not skip
                if (!array_key_exists('maksuturva_transaction_id', $ad) || empty($ad['maksuturva_transaction_id'])) {
                    print("\t❌ The payment {$id} was not migrated because it was not made using the old payment module.\n");
                    $this->connection->rollBack();
                    $skippedRows++;
                    continue;
                }

                $ad['svea_transaction_id'] = $ad['maksuturva_transaction_id'];
                $adresult = json_encode($ad);

                $ai = json_decode($ai, true);
                if (!empty($ai['sub_payment_method'])) {
                    // Sub payment method is the payment method code
                    $ai['svea_method_code'] = $ai['sub_payment_method'];
                    $ai['svea_method_group'] = $ai['method_title'] . " " . $ai['collated_method'];
                    $ai['svea_preselected_payment_method'] = $ai['maksuturva_preselected_payment_method'];
                } else {
                    // Payment method without sub payment method. This can be separate payment method or Svea service payment selection
                    if (!empty($ai['maksuturva_preselected_payment_method'])) {
                        $ai['svea_method_code'] = $ai['maksuturva_preselected_payment_method'];
                        $ai['svea_preselected_payment_method'] = $ai['maksuturva_preselected_payment_method'];
                    }
                    // method title should be always set
                    $ai['svea_method_group'] = $ai['method_title'];
                }
                $airesult = json_encode($ai);
                
                // update with migrated data
                $where = $this->connection->quoteInto("entity_id = ?", $id);
                $this->connection->update($table, ['additional_information' => $airesult, 'additional_data' => $adresult], [$where]);
                $this->connection->commit();
                $updatedRows++;
            } catch (Exception $exception) {
                print("Error updating payment for id: {$id}, reason: {$exception->getMessage()} \n");
                $this->connection->rollBack();
                $skippedRows++;
            }       
        }

        if ($updatedRows == 0) {
            print("\t❌ No rows updated. Maybe the sales migration is done already.\n");
        } else {
            print("\t✅ Updated {$updatedRows} and skipped {$skippedRows} rows in the database.\n");    
        }
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
        $selectClause = "select entity_id,additional_information,additional_data from {$table} where additional_information IS NOT NULL AND svea_payment_id LIKE '{$paymentId}'";
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

            if (empty($valuesByIds)) {
                print("❌ Zero matching rows with Svea payment ids found in the database.\n");
                return $migratedIds;
            }

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
        } else {
            print("\t❌ Columns 'maksuturva_pmt_id' or/and 'svea_payment_id' do not exist in the database.\n");
            return $migratedIds;
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
