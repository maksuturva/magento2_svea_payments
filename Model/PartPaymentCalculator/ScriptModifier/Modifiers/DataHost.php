<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifiers;

use Svea\SveaPayment\Api\PartPaymentCalculator\ModifierInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier\Modifier;
use Svea\SveaPayment\Model\Source\CommunicationEndpoint;

class DataHost implements ModifierInterface
{
    private const DATA_HOST_ATTRIBUTE = 'data-host';

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
    public function __construct(Config $config, Modifier $modifier)
    {
        $this->config = $config;
        $this->modifier = $modifier;
    }

    /**
     * @inheritDoc
     */
    public function modify(string $script): string
    {
        $endpoint = $this->config->getCommunicationUrl();
        if ($endpoint === CommunicationEndpoint::TEST_ENVIRONMENT_URL) {
            return $this->modifier->setAttribute(
                $script,
                self::DATA_HOST_ATTRIBUTE,
                $endpoint
            );
        }

        return $script;
    }
}
