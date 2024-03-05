<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Model\ResourceModel\Migrate;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Svea\SveaPayment\Model\Config\HandlingFee\ConfigParser;

class MigrateHandlingFees
{
    /**
     * @var WriterInterface
     */
    private WriterInterface $configWriterInterface;

    /**
     * @var ConfigParser
     */
    private ConfigParser $configParser;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @param WriterInterface $configWriterInterface
     * @param ConfigParser $configParser
     * @param SerializerInterface $serializer
     */
    public function __construct(
        WriterInterface     $configWriterInterface,
        ConfigParser        $configParser,
        SerializerInterface $serializer
    ) {
        $this->configWriterInterface = $configWriterInterface;
        $this->configParser = $configParser;
        $this->serializer = $serializer;
    }

    /**
     * @param string $configPath
     * @param string|null $value
     * @param string $scope
     * @param int $scopeId
     *
     * @return string
     */
    public function resolveHandlingFees(string $configPath, ?string $value, string $scope, int $scopeId): string
    {
        if ($value !== null) {
            $valueArray = $this->configParser->parseSemiColonConfig($value);
            $defaultFee = $this->getDefaultFee($valueArray);
            if ($defaultFee) {
                $this->saveDefaultFee($configPath, $defaultFee, $scope, $scopeId);
            }
            if (!empty($valueArray)) {
                return $this->convertToDynamicRowsValue($valueArray);
            }
        }

        return '';
    }

    /**
     * @param array $valueArray
     *
     * @return string|null
     */
    private function getDefaultFee(array &$valueArray): ?string
    {
        $firstValue = $valueArray[0] ?? null;
        if ($firstValue && is_numeric($firstValue)) {
            unset($valueArray[0]);

            return $firstValue;
        }

        return null;
    }

    /**
     * @param string $configPath
     * @param string $value
     * @param string $scope
     * @param int $scopeId
     *
     * @return void
     */
    private function saveDefaultFee(string $configPath, string $value, string $scope, int $scopeId): void
    {
        $configPath = str_replace('handling_fee', 'default_handling_fee', $configPath);
        $this->configWriterInterface->save($configPath, $value, $scope, $scopeId);
    }

    /**
     * @param array $valueArray
     *
     * @return string
     */
    private function convertToDynamicRowsValue(array $valueArray): string
    {
        $data = [];
        foreach ($valueArray as $code => $fee) {
            $data[] = [
                'payment_method' => $code,
                'fee' => $fee,
            ];
        }

        return $this->serializer->serialize($data);
    }
}
