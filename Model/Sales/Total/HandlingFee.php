<?php

namespace Svea\SveaPayment\Model\Sales\Total;

use Magento\Framework\DataObject;

class HandlingFee
{
    const CODE = 'svea_handling_fee';
    const BASE_CODE = 'svea_base_handling_fee';
    const REFUNDED_CODE = 'svea_refunded_handling_fee';
    const REFUNDED_TAX_CODE = 'svea_refunded_handling_fee_tax';
    const TAX_AMOUNT_CODE = 'svea_handling_fee_tax';
    const TAX_AMOUNT_BASE_CODE = 'svea_base_handling_fee_tax';
    const TAX_AMOUNT__PERCENTAGE_CODE = 'svea_handling_fee_tax_percentage';

    /**
     * @param DataObject $model
     *
     * @return float|null
     */
    public function getValue(DataObject $model)
    {
        return $model->getData(self::CODE);
    }

    /**
     * @param DataObject $model
     * @param float|null $value
     *
     * @return DataObject
     */
    public function setValue(DataObject $model, $value)
    {
        return $model->setData(self::CODE, $value);
    }

    /**
     * @param DataObject $model
     *
     * @return float|null
     */
    public function getRefundedValue(DataObject $model)
    {
        return $model->getData(self::REFUNDED_CODE);
    }

    public function getRefundedTaxValue(DataObject $model)
    {
        return $model->getData(self::REFUNDED_TAX_CODE);
    }

    /**
     * @param DataObject $model
     * @param float|null $value
     *
     * @return DataObject
     */
    public function setRefundedValue(DataObject $model, $value)
    {
        return $model->setData(self::REFUNDED_CODE, $value);
    }

    public function setRefundedTaxValue(DataObject $model, $value)
    {
        return $model->setData(self::REFUNDED_TAX_CODE, $value);
    }

    /**
     * @param DataObject $model
     *
     * @return float
     */
    public function getBaseValue(DataObject $model): float
    {
        return $model->getData(self::BASE_CODE) ?? 0;
    }

    /**
     * @param DataObject $model
     * @param float $value
     *
     * @return DataObject
     */
    public function setBaseValue(DataObject $model, float $value)
    {
        return $model->setData(self::BASE_CODE, $value);
    }

    public function setTaxBaseValue(DataObject $model, float $value)
    {
        return $model->setData(self::TAX_AMOUNT_BASE_CODE, $value);
    }

    /**
     * @param DataObject $model
     *
     * @return float
     */
    public function getTaxAmount(DataObject $model): float
    {
        return $model->getData(self::TAX_AMOUNT_CODE) ?? 0;
    }

    /**
     * @param DataObject $model
     * @param float $value
     *
     * @return DataObject
     */
    public function setTaxAmount(DataObject $model, float $value): DataObject
    {
        return $model->setData(self::TAX_AMOUNT_CODE, round($value, 2));
    }

    public function setBaseTaxAmount(DataObject $model, float $value): DataObject
    {
        return $model->setData(self::TAX_AMOUNT_BASE_CODE, round($value, 2));
    }

    public function getBaseTaxAmount(DataObject $model): float
    {
        return $model->getData(self::TAX_AMOUNT_BASE_CODE) ?? 0;
    }

    public function getTaxAmountPercentage(DataObject $model): float
    {
        return $model->getData(self::TAX_AMOUNT__PERCENTAGE_CODE) ?? 0;
    }

    public function setTaxAmountPercentage(DataObject $model, float $value): DataObject
    {
        return $model->setData(self::TAX_AMOUNT__PERCENTAGE_CODE, $value);
    }
}
