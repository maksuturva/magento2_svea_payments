<?php

namespace Svea\SveaPayment\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Svea\SveaPayment\Api\PartPaymentCalculator\CalculatorProviderInterface;

class PartPaymentCalculator implements ArgumentInterface
{
    /**
     * @var CalculatorProviderInterface
     */
    private CalculatorProviderInterface $calculatorProvider;

    /**
     * @param CalculatorProviderInterface $calculatorProvider
     */
    public function __construct(
        CalculatorProviderInterface $calculatorProvider
    ) {
        $this->calculatorProvider = $calculatorProvider;
    }

    /**
     * @return string
     */
    public function getCalculatorScript(): string
    {
        $calculatorScript = $this->calculatorProvider->getCalculatorScript();

        return $calculatorScript !== null ? $calculatorScript : '';
    }

    public function isEnabledForLocation(): bool
    {
        return $this->calculatorProvider->isEnabledForLocation();
    }
}
