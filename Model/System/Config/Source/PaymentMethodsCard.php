<?php

namespace Svea\SveaPayment\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Svea\SveaPayment\Model\Payment\MethodDataProvider;
use function sprintf;

class PaymentMethodsCard implements OptionSourceInterface
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
        $allowedCodes = ['FI50', 'FI51', 'FI52', 'FI53'];

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
