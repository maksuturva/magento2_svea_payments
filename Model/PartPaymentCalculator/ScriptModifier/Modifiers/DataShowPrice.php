<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifiers;

use Svea\SveaPayment\Api\PartPaymentCalculator\ModifierInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifier;

class DataShowPrice implements ModifierInterface
{
    private const DATA_SHOW_PRICE_ATTRIBUTE = 'data-showprice';

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
        $showPurchasePrice = $this->config->isCalculatorPurchasePriceInfoVisible() ? 'true' : 'false';

        return $this->modifier->setAttribute(
            $script,
            self::DATA_SHOW_PRICE_ATTRIBUTE,
            $showPurchasePrice
        );
    }
}
