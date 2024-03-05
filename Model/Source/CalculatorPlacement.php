<?php

namespace Svea\SveaPayment\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CalculatorPlacement implements OptionSourceInterface
{
    public const CART = 'checkout_cart_index';
    public const CHECKOUT = 'checkout_index_index';
    public const PRODUCT_PAGES = 'catalog_product_view';

    public function toOptionArray()
    {
        return [
            [
                'value' => self::CART,
                'label' => 'Cart'
            ],
            [
                'value' => self::CHECKOUT,
                'label' => 'Checkout'
            ],
            [
                'value' => self::PRODUCT_PAGES,
                'label' => 'Product Pages'
            ],
        ];
    }
}
