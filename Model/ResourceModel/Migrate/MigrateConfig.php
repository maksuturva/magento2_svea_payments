<?php

namespace Svea\SveaPayment\Model\ResourceModel\Migrate;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use function __;
use function array_diff;
use function explode;
use function str_contains;
use function str_replace;

class MigrateConfig implements MigrateConfigInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfigInterface;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManagerInterface;

    /**
     * @var WriterInterface
     */
    private WriterInterface $configWriterInterface;

    /**
     * @var TypeListInterface
     */
    private TypeListInterface $typeListInterface;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encrypted;

    /**
     * @var MigrateHandlingFees
     */
    private MigrateHandlingFees $migrateHandlingFees;

    /**
     * @var string[]
     */
    private array $maksuturvaConfigPaths = [
        "maksuturva_config/maksuturva_payment" => "svea_config/svea_payment",
        "payment/maksuturva_collated_payment" => "payment/svea_collated_payment",
        "payment/maksuturva_part_payment_payment" => "payment/svea_part_payment",
        "payment/maksuturva_invoice_payment" => "payment/svea_invoice_payment",
        "payment/maksuturva_card_payment" => "payment/svea_card_payment",
        "payment/maksuturva_generic_payment" => "payment/svea_generic_payment",
    ];

    private array $sveaCollatedSubMethods =
        [
            'pay_later' => 'payment_method_subgroup_1_title',
            'pay_later_method_filter' => 'payment_method_subgroup_1_method_filter',
            'pay_later_handling_fee' => 'payment_method_subgroup_1_handling_fee',
            'pay_now_other' => 'payment_method_subgroup_2_title',
            'pay_now_other_method_filter' => 'payment_method_subgroup_2_method_filter',
            'pay_now_other_handling_fee' => 'payment_method_subgroup_2_handling_fee',
            'pay_now_bank' => 'payment_method_subgroup_3_title',
            'pay_now_bank_method_filter' => 'payment_method_subgroup_3_method_filter',
            'pay_now_bank_handling_fee' => 'payment_method_subgroup_3_handling_fee',
        ];

    private $maksuturvaConfig;

    /**
     * @var array
     */
    private array $storeIds;

    /**
     * @var array
     */
    private array $websiteIds;

    /**
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param StoreManagerInterface $storeManagerInterface
     * @param WriterInterface $configWriterInterface
     * @param TypeListInterface $typeListInterface
     * @param EncryptorInterface $encrypted
     * @param MigrateHandlingFees $migrateHandlingFees
     * @param array $storeIds
     * @param array $websiteIds
     */
    public function __construct(
        ScopeConfigInterface  $scopeConfigInterface,
        StoreManagerInterface $storeManagerInterface,
        WriterInterface       $configWriterInterface,
        TypeListInterface     $typeListInterface,
        EncryptorInterface    $encrypted,
        MigrateHandlingFees   $migrateHandlingFees,
        array                 $storeIds = [],
        array                 $websiteIds = []
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->configWriterInterface = $configWriterInterface;
        $this->typeListInterface = $typeListInterface;
        $this->encrypted = $encrypted;
        $this->storeIds = $storeIds;
        $this->websiteIds = $websiteIds;
        $this->migrateHandlingFees = $migrateHandlingFees;
    }

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        if (!$this->maksuturvaConfigValueExists()) {
            throw new LocalizedException(__('No existing config value found for Maksuturva.'));
        }
        foreach ($this->maksuturvaConfigPaths as $mPath => $sPath) {
            $this->removeSveaConfigs($sPath);
            $this->migrateMaksuturvaConfigs($mPath);
        }
        $this->typeListInterface->cleanType("config");
    }

    /**
     * @return bool
     */
    private function maksuturvaConfigValueExists(): bool
    {
        return !empty($this->scopeConfigInterface->getValue(
            Config::MAKSUTURVA_SELLERID,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0)
        );
    }

    /**
     * This function removes current Svea configs
     *
     * @param string $path
     *
     * @return void
     */
    private function removeSveaConfigs(string $path)
    {
        $configPath = explode('/', $path);
        $sveaConfig = [];
        $sveaConfig["default"][0] =
            $this->getDefaultConfigs(0)[$configPath[0]][$configPath[1]] ?? [];
        foreach ($this->getWebSiteIds() as $websiteId) {
            $sveaConfig["websites"][$websiteId] = $this->getWebsiteConfigs($websiteId)[$configPath[0]][$configPath[1]] ?? [];
        }
        foreach ($this->getStoresIds() as $storeId => $storeWebsiteId) {
            $sveaConfig["stores"][$storeId] = $this->getStoreConfigs($storeId)[$configPath[0]][$configPath[1]] ?? [];
        }
        foreach ($sveaConfig as $scope => $scopeData) {
            foreach ($scopeData as $scopeId => $scopeValues) {
                foreach ($scopeValues as $sveapath => $value) {
                    $this->configWriterInterface->delete($path . "/" . $sveapath, $scope, $scopeId);
                }
            }
        }
    }

    /**
     * @param $scopeCode
     *
     * @return mixed
     */
    private function getDefaultConfigs(
        $scopeCode = null
    ) {
        return $this->scopeConfigInterface->getValue('', $this->scopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeCode);
    }

    /**
     * @return array
     */
    private function getWebSiteIds(): array
    {
        if (empty($this->websiteIds)) {
            $websiteIds = [];
            $websites = $this->storeManagerInterface->getWebsites();
            foreach ($websites as $website) {
                $websiteIds[] = $website->getId();
            }
            $this->websiteIds = $websiteIds;
        }

        return $this->websiteIds;
    }

    /**
     * @param $scopeCode
     *
     * @return mixed
     */
    private function getWebsiteConfigs(
        $scopeCode = null
    ) {
        return $this->scopeConfigInterface->getValue('', ScopeInterface::SCOPE_WEBSITES, $scopeCode);
    }

    /**
     * @return array
     */
    private function getStoresIds(): array
    {
        if (empty($this->storeIds)) {
            $storeIds = [];
            $stores = $this->storeManagerInterface->getStores();
            foreach ($stores as $store) {
                $storeIds[$store->getId()] = $store->getWebsiteId();
            }
            $this->storeIds = $storeIds;
        }

        return $this->storeIds;
    }

    /**
     * @param $scopeCode
     *
     * @return mixed
     */
    private function getStoreConfigs(
        $scopeCode = null
    ) {
        return $this->scopeConfigInterface->getValue('', ScopeInterface::SCOPE_STORES, $scopeCode);
    }

    /**
     * Fetching maksuturva general configuration
     * Default > website > store scope fetch and cleanup
     * Unset if the values are the same
     *
     * @param string $path
     *
     * @return void
     */
    private function migrateMaksuturvaConfigs(string $path)
    {
        $configPath = explode('/', $path);
        $maksuturvaConfig = [];
        $maksuturvaConfig["default"][0] =
            $this->getDefaultConfigs(0)[$configPath[0]][$configPath[1]] ?? [];
        foreach ($this->getWebSiteIds() as $websiteId) {
            $websiteData = $this->getWebsiteConfigs($websiteId)[$configPath[0]][$configPath[1]] ?? [];
            $maksuturvaConfig["websites"][$websiteId] = array_diff($websiteData, $maksuturvaConfig["default"][0]);
        }
        foreach ($this->getStoresIds() as $storeId => $storeWebsiteId) {
            $storedata = $this->getStoreConfigs($storeId)[$configPath[0]][$configPath[1]] ?? [];
            $maksuturvaConfig["stores"][$storeId] = array_diff(
                $storedata, $maksuturvaConfig["websites"][$storeWebsiteId],
                $maksuturvaConfig["default"][0]
            );
        }
        foreach ($maksuturvaConfig as $scope => $scopeData) {
            foreach ($scopeData as $scopeId => $scopeValues) {
                foreach ($scopeValues as $maksuturvapath => $value) {
                    $this->maksuturvaConfig[$scope][$scopeId][$path][$maksuturvapath] = $value;
                    $spath = $this->getSveaPath($path, $maksuturvapath);
                    $value = $this->resolveValue($spath, $value, $scope, $scopeId);
                    $this->configWriterInterface->save($spath, $value, $scope, $scopeId);
                }
            }
        }
    }

    /**
     * @param string $path
     * @param string $maksuturvapath
     *
     * @return string
     */
    private function getSveaPath(string $path, string $maksuturvapath): string
    {
        $sveaPath = $this->getCollatedPaymentPath($path, $maksuturvapath);
        if ($sveaPath) {
            return $sveaPath;
        }

        return $this->maksuturvaConfigPaths[$path] . "/" . $maksuturvapath;
    }

    /**
     * @param string $path
     * @param string $maksuturvapath
     *
     * @return string|null
     */
    private function getCollatedPaymentPath(string $path, string $maksuturvapath): ?string
    {
        if ($path === 'payment/maksuturva_collated_payment') {
            $sveaConfigPath = $this->sveaCollatedSubMethods[$maksuturvapath] ?? null;
            if ($sveaConfigPath) {
                return $this->maksuturvaConfigPaths[$path] . "/" . $sveaConfigPath;
            }
        }

        return null;
    }

    /**
     * @param string $configPath
     * @param mixed $value
     * @param string $scope
     * @param int $scopeId
     *
     * @return mixed
     */
    private function resolveValue(string $configPath, $value, string $scope, int $scopeId)
    {
        switch ($configPath) {
            case ($configPath === Config::SECRET_KEY):
                return $this->encrypted->encrypt($value);
            case (str_contains($configPath, 'method_filter')):
                return str_replace(';', ',', $value);
            case (str_contains($configPath, 'handling_fee')):
                return $this->migrateHandlingFees->resolveHandlingFees($configPath, $value, $scope, $scopeId);
            default:
                return $value;
        }
    }
}
