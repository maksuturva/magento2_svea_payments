<?php

namespace Svea\SveaPayment\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

use function __;

class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field) : Phrase
    {
        return __($field);
    }
}
