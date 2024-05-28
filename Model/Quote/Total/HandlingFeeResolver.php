<?php

namespace Svea\SveaPayment\Model\Quote\Total;

use Magento\Quote\Api\Data\CartInterface;
use Svea\SveaPayment\Api\HandlingFee\ConfigProviderInterface;

class HandlingFeeResolver
{
    /**
     * @var ConfigProviderInterface
     */
    private ConfigProviderInterface $configProvider;

    /**
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(
        ConfigProviderInterface $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param CartInterface $quote
     *
     * @return float
     */
    public function calculateHandlingFee(CartInterface $quote): float
    {
        $method = $quote->getPayment()->getMethod();
        if (!$method) {
            return 0;
        }
        $methodCode = $quote->getPayment()->getAdditionalInformation('svea_method_code');
        $methodGroup = $quote->getPayment()->getAdditionalInformation('svea_method_group');
        $storeId = $quote->getStoreId();

        return $this->resolveConfiguredHandlingFee($storeId, $method, $methodCode, $methodGroup);
    }

    public function calculateHandlingFeeTax(CartInterface $quote, float $feeAmount): float
    {
        $methodCode = $quote->getPayment()->getAdditionalInformation('svea_method_code');
        $taxableMethods = [
            'FI01',
            'FI72'
        ];

        if (!$methodCode || !in_array($methodCode, $taxableMethods, true)) {
            return 0;
        }

        $maxTaxRate = $this->getMaxTaxRateFromItems($quote);

        if ($maxTaxRate === 0 || $feeAmount === 0) {
            return 0;
        }

        return ($feeAmount / 100) * $maxTaxRate;
    }

    public function getMaxTaxRateFromItems(CartInterface $quote): float
    {
        $taxRates = [];
        foreach ($quote->getAllItems() as $item) {
            $taxPercent = $item->getTaxPercent();
            if ($taxPercent === null) {
                continue;
            }

            $totalValue = $item->getPrice() * $item->getQty();

            if (!isset($taxRates[$taxPercent])) {
                $taxRates[$taxPercent] = 0;
            }

            $taxRates[$taxPercent] += $totalValue;
        }

        $maxTaxRate = 0;
        $maxTotalValue = 0;

        if (empty($taxRates)) {
            return $maxTaxRate;
        }

        foreach ($taxRates as $taxRate => $totalValue) {
            if ($totalValue > $maxTotalValue) {
                $maxTotalValue = $totalValue;
                $maxTaxRate = $taxRate;
            }
        }

        return (float)$maxTaxRate;
    }

    /**
     * @param int $storeId
     * @param string $method
     * @param string|null $methodCode
     * @param string|null $methodGroup
     *
     * @return float
     */
    private function resolveConfiguredHandlingFee(
        int     $storeId,
        string  $method,
        ?string $methodCode,
        ?string $methodGroup = null
    ): float {
        $configs = $this->configProvider->collect($storeId);
        if (!isset($configs[$method]) && !isset($configs[$methodGroup])) {

            return 0;
        }
        $config = $configs[$methodGroup] ?? $configs[$method];
        if ($methodCode && isset($config[$methodCode])) {
            return (float)$config[$methodCode];
        } elseif (isset($config[0])) {
            return (float)$config[0];
        }

        return 0;
    }
}
