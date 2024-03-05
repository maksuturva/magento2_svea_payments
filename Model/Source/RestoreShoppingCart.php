<?php

namespace Svea\SveaPayment\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RestoreShoppingCart implements OptionSourceInterface
{
    const NEVER = 'never';
    const CANCEL = 'cancel';
    const ERROR = 'error';
    const BOTH = 'both';

    /**
     * @var string[]
     */
    private array $restoreModes =
        [
            self::NEVER => 'Never',
            self::CANCEL => 'On Cancel',
            self::ERROR => 'On Error',
            self::BOTH => 'On Both',
        ];

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->restoreModes as $value => $label) {
            $options[] =
                [
                    'value' => $value,
                    'label' => $label,
                ];
        }

        return $options;
    }
}
