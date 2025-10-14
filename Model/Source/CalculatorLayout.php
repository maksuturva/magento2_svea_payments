<?php

namespace Svea\SveaPayment\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CalculatorLayout implements OptionSourceInterface
{
    public const FULL = 'full';
    public const MINI = 'mini';
    public const BUTTON = 'button';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::FULL,
                'label' => 'Full'
            ],
            [
                'value' => self::MINI,
                'label' => 'Mini'
            ],
            [
                'value' => self::BUTTON,
                'label' => 'Button'
            ],
        ];
    }
}
