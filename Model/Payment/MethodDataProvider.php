<?php

namespace Svea\SveaPayment\Model\Payment;

use Magento\Framework\Config\CacheInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\Data\AmountHandler;
use Svea\SveaPayment\Gateway\Http\ApiClient;

class MethodDataProvider
{
    private const CACHE_LIFETIME = 60 * 10;
    private const CACHE_TAGS = ['SVEA'];

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ApiClient $client
     */
    private $client;

    /**
     * @var AmountHandler
     */
    private $amountFormatter;

    /**
     * @var array
     */
    private $methods = [];

    public function __construct(
        CacheInterface $cache,
        Config $config,
        ApiClient $client,
        AmountHandler $amountFormatter
    ) {
        $this->cache = $cache;
        $this->config = $config;
        $this->client = $client;
        $this->amountFormatter = $amountFormatter;
    }

    /**
     * @param string|null $locale
     * @param float|null $total
     *
     * @return array
     */
    public function request(?string $locale = null, ?float $total = null)
    {
        $cacheKey = $this->getCacheKey($locale, $total);
        $methods = $this->getCached($cacheKey);
        if ($methods === false) {
            $methods = $this->requestFromApi($locale, $total);
            if ($methods) {
                $this->setCached($cacheKey, $methods);
            }
        }

        return $methods;
    }

    /**
     * Clear the cached values from Svea
     */
    public function clearCached()
    {
        $this->unsetCached($this->getCacheKey(null, null));
    }

    /**
     * @param string|null $locale
     * @param float|null $total
     *
     * @return array
     */
    private function requestFromApi(?string $locale, ?float $total): array
    {
        $fields = [
            'sellerid' => $this->config->getSellerId(),
        ];
        if ($locale !== null) {
            $fields['request_locale'] = $locale;
        }
        if ($total !== null) {
            $fields['totalamount'] = $this->amountFormatter->formatFloat($total);
        }

        try {
            return $this->client->getPaymentMethods($fields);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @param string|null $locale
     * @param float|null $total
     *
     * @return string
     */
    private function getCacheKey(?string $locale, ?float $total)
    {
        $key = ['SVEA_PAYMENT_METHODS'];
        if ($locale !== null) {
            $key[] = $locale;
        }
        if ($total !== null) {
            $key[] = \number_format($total, 4, '_', '');
        }

        return \implode('_', $key);
    }

    /**
     * @return mixed|false
     */
    private function getCached(string $key)
    {
        if (isset($this->methods[$key])) {
            return $this->methods[$key];
        }
        if (!$this->cache->test($key)) {
            return false;
        }
        $data = $this->cache->load($key);
        if ($data) {
            $data = \unserialize($data);
        }

        return $data;
    }

    /**
     * @param mixed $data
     */
    private function setCached(string $key, $data)
    {
        $this->methods[$key] = $data;
        $this->cache->save(\serialize($data), $key, self::CACHE_TAGS, self::CACHE_LIFETIME);
    }

    private function unsetCached(string $key)
    {
        unset($this->methods[$key]);
        $this->cache->remove($key);
    }
}
