<?php

namespace Svea\SveaPayment\Model\PartPaymentCalculator\ScriptModifier;

use Svea\SveaPayment\Api\PartPaymentCalculator\ModifierInterface;

class ModifierPool implements ModifierInterface
{
    /**
     * @var array
     */
    private array $modifiers;

    /**
     * @param array $modifiers
     */
    public function __construct(
        array $modifiers = []
    ) {
        $this->modifiers = $modifiers;
    }

    /**
     * @inheritDoc
     */
    public function modify(string $script): string
    {
        foreach ($this->modifiers as $modifier) {
            if (!$modifier instanceof ModifierInterface) {
                continue;
            }
            $script = $modifier->modify($script);
        }
        return $script;
    }
}
