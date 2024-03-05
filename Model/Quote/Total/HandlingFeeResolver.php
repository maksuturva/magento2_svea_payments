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
