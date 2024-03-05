<?php

namespace Svea\SveaPayment\Model\System\Config\Source;

class DeliveryMode implements \Magento\Framework\Data\OptionSourceInterface
{
    const MODE_DISABLED = 0;
    const MODE_REAL = 1;
    const MODE_CUSTOM = 2;

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::MODE_DISABLED, 'label' => 'Disabled'],
            ['value' => self::MODE_REAL, 'label' => 'Real'],
            ['value' => self::MODE_CUSTOM, 'label' => 'Custom'],
        ];
    }
}
