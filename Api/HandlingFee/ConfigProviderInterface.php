<?php
namespace Svea\SveaPayment\Api\HandlingFee;

interface ConfigProviderInterface
{
    /**
     * @param int $storeId
     *
     * @return array
     */
    public function collect(int $storeId): array;

    /**
     * @param string $method
     * @param int $storeId
     *
     * @return array
     */
    public function get(string $method, int $storeId): array;
}
