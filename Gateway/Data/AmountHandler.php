<?php
namespace Svea\SveaPayment\Gateway\Data;

class AmountHandler
{
    /**
     * @param string|float $amount
     *
     * @return float
     */
    public function parseFloat($amount): float
    {
        return \floatval(\str_replace(',', '.', $amount));
    }

    /**
     * Format floats based on usual API requirements.
     * (must be presented with two decimals and the decimal delimiter is a comma, e.g. 94,80)
     *
     * @param float $amount
     *
     * @return string
     */
    public function formatFloat($amount): string
    {
        return \str_replace('.', ',', \sprintf('%.2f', $amount));
    }
}
