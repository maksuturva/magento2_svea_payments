<?php

namespace Svea\SveaPayment\Model\System\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FormType implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 0,
                'label' => 'Dropdown'
            ],
            [
                'value' => 1,
                'label' => 'Icons'
            ],
        ];
    }
}
