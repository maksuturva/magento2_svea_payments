<?php

namespace Svea\SveaPayment\Model\Config\HandlingFee;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Svea\SveaPayment\Api\HandlingFee\ConfigProviderInterface;
use function array_merge;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $configReader;

    /**
     * @var ConfigParser
     */
    private ConfigParser $configParser;

    /**
     * @var ConfigProviderInterface[]
     */
    private array $configProviders;

    /**
     * @var string[]
     */
    private array $feesConfigPaths;

    /**
     * @var array
     */
    private array $cached = [];

    /**
     * @var array
     */
    private array $defaultFeesConfigPaths;

    /**
     * @param ScopeConfigInterface $configReader
     * @param ConfigParser $configParser
     * @param ConfigProviderInterface[] $configProviders
     * @param string[] $feesConfigPaths
     * @param array $defaultFeesConfigPaths
     */
    public function __construct(
        ScopeConfigInterface $configReader,
        ConfigParser         $configParser,
        array                $configProviders = [],
        array                $feesConfigPaths = [],
        array                $defaultFeesConfigPaths = []
    ) {
        $this->configReader = $configReader;
        $this->configParser = $configParser;
        $this->configProviders = $configProviders;
        $this->feesConfigPaths = $feesConfigPaths;
        $this->defaultFeesConfigPaths = $defaultFeesConfigPaths;
    }

    /**
     * @inheritDoc
     */
    public function get(string $method, int $storeId): array
    {
        $all = $this->collect($storeId);

        return $all[$method] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function collect(int $storeId): array
    {
        if (empty($this->cached) || !isset($this->cached[$storeId])) {
            $configs = [];
            $configs = $this->collectHandlingFeesConfig($configs, $this->feesConfigPaths, $storeId);
            $configs = $this->collectDefaultHandlingFeesConfig($configs, $this->defaultFeesConfigPaths, $storeId);
            $configs = $this->collectConfigProviderConfigs($configs, $this->configProviders, $storeId);
            $this->cached[$storeId] = $configs;
        }

        return $this->cached[$storeId];
    }

    /**
     * @param array $configs
     * @param array $configPaths
     * @param int $storeId
     *
     * @return array
     */
    private function collectHandlingFeesConfig(array $configs, array $configPaths, int $storeId): array
    {
        foreach ($configPaths as $key => $path) {
            $value = $this->configReader->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
            if (!empty($value)) {
                $config = $this->configParser->parseDynamicRowsConfig([$key => $value]);
                $configs = array_merge($configs, $config);
            }
        }

        return $configs;
    }

    /**
     * @param array $configs
     * @param array $configPaths
     * @param int $storeId
     *
     * @return array
     */
    private function collectDefaultHandlingFeesConfig(array $configs, array $configPaths, int $storeId): array
    {
        foreach ($configPaths as $key => $path) {
            $value = $this->configReader->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
            if (!empty($value)) {
                $configs[$key][0] = $value;
            }
        }

        return $configs;
    }

    /**
     * @param array $configs
     * @param array $configProviders
     * @param int $storeId
     *
     * @return array
     */
    private function collectConfigProviderConfigs(array $configs, array $configProviders, int $storeId): array
    {
        foreach ($configProviders as $provider) {
            $configs = array_merge($configs, $provider->collect($storeId));
        }

        return $configs;
    }
}
