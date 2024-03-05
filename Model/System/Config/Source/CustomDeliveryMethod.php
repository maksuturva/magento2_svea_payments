<?php

namespace Svea\SveaPayment\Model\System\Config\Source;

class CustomDeliveryMethod implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'ODLVR', 'label' => 'Own delivery'],
            ['value' => 'SERVI', 'label' => 'Service'],
            ['value' => 'ELECT', 'label' => 'Electronic delivery'],
            ['value' => 'UNREG', 'label' => 'Untraceable letter'],
        ];
    }
}
