<?php

namespace Svea\SveaPayment\Api\PartPaymentCalculator;

interface CalculatorProviderInterface
{
    /**
     * Validate if the calculator is available for methods
     *
     * @param array $methods
     *
     * @return bool
     */
    public function isAvailableForMethods(array $methods): bool;

    /**
     * Validate if the calculator is available for a price
     *
     * @param float $price
     *
     * @return bool
     */
    public function validatePrice(float $price, string $location): bool;

    /**
     * Validate if the calculator can be displayed. See Model/Source/CalculatorPlacement.php
     *
     * @param string $layoutName
     *
     * @return bool
     */
    public function isCalculatorPlacementAvailable(string $layoutName): bool;

    /**
     * Get a modified calculator script
     *
     * @return string|null
     */
    public function getCalculatorScript(): ?string;

    /**
     * Check if the calculator is enabled for location
     *
     * @return bool
     */
    public function isEnabledForLocation(): bool;
}
