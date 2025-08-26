<?php

namespace Svea\SveaPayment\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CommunicationEndpoint implements OptionSourceInterface
{
    public const TEST_ENVIRONMENT_URL = 'https://test1.maksuturva.fi/';
    public const PRODUCTION_ENVIRONMENT_URL = 'https://www.maksuturva.fi/';
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::TEST_ENVIRONMENT_URL,
                'label' => 'Test'
            ],
            [
                'value' => self::PRODUCTION_ENVIRONMENT_URL,
                'label' => 'Production'
            ],
        ];
    }
}
