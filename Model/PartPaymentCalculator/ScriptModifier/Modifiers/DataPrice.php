<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifiers;

use Svea\SveaPayment\Api\PartPaymentCalculator\ModifierInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifier;
use function str_contains;

class DataPrice implements ModifierInterface
{
    private const DATA_PRICE_ATTRIBUTE = 'data-price';

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var Modifier
     */
    private Modifier $modifier;

    /**
     * @param Config $config
     * @param Modifier $modifier
     */
    public function __construct(
        Config   $config,
        Modifier $modifier
    ) {
        $this->config = $config;
        $this->modifier = $modifier;
    }

    /**
     * @inheritDoc
     */
    public function modify(string $script): string
    {
        if ($this->config->isCalculatorDynamicPriceChangesEnabled() &&
            str_contains($script, self::DATA_PRICE_ATTRIBUTE)
        ) {
            return $this->modifier->setAttribute($script, self::DATA_PRICE_ATTRIBUTE, '');
        }

        return $script;
    }
}
