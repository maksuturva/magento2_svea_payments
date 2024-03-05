<?php

namespace Svea\SveaPayment\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Svea\SveaPayment\Model\Payment\MethodDataProvider;
use function sprintf;

class PaymentMethods implements OptionSourceInterface
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
        $options = [];
        $methodData = $this->methodProvider->request();
        foreach ($methodData['paymentmethod'] ?? [] as $method) {
            $code = $method['code'] ?? null;
            if ($code === null) {
                continue;
            }
            $displayName = $method['displayname'] ?? '';
            $options[] = [
                'value' => $code,
                'label' => sprintf('%s %s', $code, $displayName),
            ];
        }

        return $options;
    }
}
