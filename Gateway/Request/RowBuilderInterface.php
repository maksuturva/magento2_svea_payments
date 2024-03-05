<?php

namespace Svea\SveaPayment\Gateway\Request;

interface RowBuilderInterface
{
    /**
     * Name of the row. Max length 40
     */
    const NAME = 'pmt_row_name';

    /**
     * Detailed description of the row. Max length 1000
     */
    const DESC = 'pmt_row_desc';

    /**
     * Row quantity. Max length 8
     */
    const QUANTITY = 'pmt_row_quantity';

    /**
     * Date in form dd.mm.yyyy. Max length 10
     */
    const DELIVERY_DATE = 'pmt_row_deliverydate';

    /**
     * Net unit price of product (VAT excluded). Max length 17
     */
    const PRICE_NET = 'pmt_row_price_net';

    /**
     * Gross unit price of product (VAT included). Max length 17
     */
    const PRICE_GROSS = 'pmt_row_price_gross';

    /**
     * Max length 5
     */
    const VAT = 'pmt_row_vat';

    /**
     * Max length 5
     */
    const DISCOUNT_PERCENTAGE = 'pmt_row_discountpercentage';

    /**
     * Type of the row. Max length 5
     */
    const TYPE = 'pmt_row_type';

    /**
     * Article or product number of the row
     */
    const ARTICLE_NUMBER = 'pmt_row_articlenr';

    /**
     * Unit of quantity of this order row, e.g. kg, l, m, kpl
     */
    const ROW_UNIT = 'pmt_row_unit';

    const TOTAL_AMOUNT = 'totalAmount';

    const SELLER_COSTS = 'sellerCosts';

    const ROW = 'row';

    /**
     * @param array $buildSubject
     * @param float $totalAmount
     * @param float $sellerCosts
     * @return array
     */
    public function build(array $buildSubject, float $totalAmount, float $sellerCosts) : array;
}
