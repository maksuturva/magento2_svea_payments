<?php

namespace Svea\SveaPayment\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Svea\SveaPayment\Model\Payment\MethodDataProvider;
use function sprintf;

class PaymentMethodsOnlineBanking implements OptionSourceInterface
{
    /**
     * @var MethodDataProvider
     */
    private MethodDataProvider $methodProvider;

    /**
     * @param MethodDataProvider $methodProvider
     */
    public function __construct(
        MethodDataProvider $methodProvider
    ) {
        $this->methodProvider = $methodProvider;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        $allowedCodes = ['FI01', 'FI02', 'FI03', 'FI04', 'FI05', 'FI06', 'FI07', 'FI08', 'FI09', 'FI10', 'FI11', 'FI12', 'FI13', 'FI14', 'FI15'];

        $options = [];
        $methodData = $this->methodProvider->request();
        
        // Index methods by code for quick lookup
        $methodsByCode = [];
        foreach ($methodData['paymentmethod'] ?? [] as $method) {
            $code = $method['code'] ?? null;
            if ($code !== null) {
                $methodsByCode[$code] = $method;
            }
        }

        // Build options in the exact order of $allowedCodes
        foreach ($allowedCodes as $code) {
            if (!isset($methodsByCode[$code])) {
                continue;
            }

            $displayName = $methodsByCode[$code]['displayname'] ?? '';

            $options[] = [
                'value' => $code,
                'label' => sprintf('%s %s', $code, $displayName),
            ];
        }

        return $options;
    }
}
