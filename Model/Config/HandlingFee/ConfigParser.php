<?php

namespace Svea\SveaPayment\Model\Config\HandlingFee;

use Magento\Framework\Serialize\SerializerInterface;
use function count;
use function explode;
use function trim;

class ConfigParser
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Formats semicolon-separated config values as an array
     *
     * Example value:
     *  10;FI01=5;FI06=7.5
     * Results in:
     *  0 = 10       (default)
     *  FI01 = 5
     *  FI06 = 7.5
     *
     * @param string $value
     *
     * @return array
     */
    public function parseSemiColonConfig(string $value): array
    {
        $parsed = [];
        foreach (explode(';', $value) as $entry) {
            $feeInfo = explode('=', $entry);
            if (count($feeInfo) == 1) {
                $code = 0;
                $amount = $feeInfo[0];
            } else {
                $code = trim($feeInfo[0]);
                $amount = $feeInfo[1];
            }
            if (!empty($amount)) {
                $parsed[$code] = $amount;
            }
        }
        return $parsed;
    }

    /**
     * @param array $values
     *
     * @return array
     */
    public function parseDynamicRowsConfig(array $values): array
    {
        $parsed = [];
        foreach ($values as $method => $config) {
            $feesByCode = [];
            $dataArray = $this->serializer->unserialize($config);
            foreach ($dataArray as $data) {
                $code = $data['payment_method'] ?? null;
                $fee = $data['fee'] ?? null;
                if ($code && $fee) {
                    $feesByCode[$code] = $fee;
                }
            }
            $parsed[$method] = $feesByCode;
        }

        return $parsed;
    }
}
