<?php
namespace Svea\SveaPayment\Model\Request;

class PaymentDataSpecification
{
    /**
     * @var int[][]
     */
    private $fieldLengths = [
        // min, max, required
        'pmt_action' => [4, 50],
        'pmt_version' => [4, 4],
        'pmt_sellerid' => [1, 15],
        'pmt_selleriban' => [18, 30],// optional
        'pmt_id' => [1, 20],
        'pmt_orderid' => [1, 50],
        'pmt_reference' => [3, 20],// > 100
        'pmt_duedate' => [10, 10],
        'pmt_userlocale' => [5, 5],// optional
        'pmt_amount' => [4, 17],
        'pmt_currency' => [3, 3],
        'pmt_okreturn' => [1, 200],
        'pmt_errorreturn' => [1, 200],
        'pmt_cancelreturn' => [1, 200],
        'pmt_delayedpayreturn' => [1, 200],
        'pmt_escrow' => [1, 1],
        'pmt_escrowchangeallowed' => [1, 1],
        'pmt_invoicefromseller' => [1, 1],// opt
        'pmt_paymentmethod' => [4, 4],// opt
        'pmt_buyeridentificationcode' => [9, 11],// opt
        'pmt_buyername' => [1, 40],
        'pmt_buyeraddress' => [1, 40],
        'pmt_buyerpostalcode' => [1, 5],
        'pmt_buyercity' => [1, 40],
        'pmt_buyercountry' => [1, 2],
        'pmt_buyerphone' => [0, 40],// opt
        'pmt_buyeremail' => [0, 100],// opt
        'pmt_deliveryname' => [1, 40],
        'pmt_deliveryaddress' => [1, 40],
        'pmt_deliverypostalcode' => [1, 5],
        'pmt_deliverycity' => [1, 40],
        'pmt_deliverycountry' => [1, 2],
        'pmt_sellercosts' => [4, 17],
        'pmt_rows' => [1, 4],
        'pmt_row_name' => [1, 40],
        'pmt_row_desc' => [1, 1000],
        'pmt_row_quantity' => [1, 8],
        'pmt_row_deliverydate' => [10, 10],
        'pmt_row_price_gross' => [4, 17],
        'pmt_row_price_net' => [4, 17],
        'pmt_row_vat' => [4, 5],
        'pmt_row_discountpercentage' => [4, 5],
        'pmt_row_type' => [1, 5],
        'pmt_charset' => [1, 15],
        'pmt_charsethttp' => [1, 15],
        'pmt_keygeneration' => [1, 3],
    ];

    /**
     * @var string[]
     */
    private $compulsoryFields = [
        'pmt_action',                    // alphanumeric        max lenght 50        min lenght 4        NEW_PAYMENT_EXTENDED
        'pmt_version',                   // alphanumeric        max lenght 4         min lenght 4        0004

        'pmt_sellerid',                  // alphanumeric        max lenght 15             -
        'pmt_id',                        // alphanumeric        max lenght 20             -
        'pmt_orderid',                   // alphanumeric        max lenght 50             -
        'pmt_reference',                 // numeric             max lenght 20        min lenght 4        Reference number + check digit
        'pmt_duedate',                   // alphanumeric        max lenght 10        min lenght 10       dd.MM.yyyy
        'pmt_amount',                    // alphanumeric        max lenght 17        min lenght 4
        'pmt_currency',                  // alphanumeric        max lenght 3         min lenght 3        EUR

        'pmt_okreturn',                  // alphanumeric        max lenght 200            -
        'pmt_errorreturn',               // alphanumeric        max lenght 200            -
        'pmt_cancelreturn',              // alphanumeric        max lenght 200            -
        'pmt_delayedpayreturn',          // alphanumeric        max lenght 200            -

        'pmt_escrow',                    // alpha               max lenght 1         min lenght 1         Maksuturva=Y, eMaksut=N
        'pmt_escrowchangeallowed',       // alpha               max lenght 1         min lenght 1         N

        'pmt_buyername',                 // alphanumeric        max lenght 40             -
        'pmt_buyeraddress',              // alphanumeric        max lenght 40             -
        'pmt_buyerpostalcode',           // numeric             max lenght 5              -
        'pmt_buyercity',                 // alphanumeric        max lenght 40             -
        'pmt_buyercountry',              // alpha               max lenght 2              -               Respecting the ISO 3166

        'pmt_deliveryname',              // alphanumeric        max lenght 40             -
        'pmt_deliveryaddress',           // alphanumeric        max lenght 40             -
        'pmt_deliverypostalcode',        // numeric             max lenght 5              -
        'pmt_deliverycountry',           // alpha               max lenght 2              -               Respecting the ISO 3166

        'pmt_sellercosts',               // alphanumeric        max lenght 17        min lenght 4         n,nn

        'pmt_rows',                      // numeric             max lenght 4         min lenght 1

        'pmt_charset',                   // alphanumeric        max lenght 15             -               {ISO-8859-1, ISO-8859-15, UTF-8}
        'pmt_charsethttp',               // alphanumeric        max lenght 15             -               {ISO-8859-1, ISO-8859-15, UTF-8}
        'pmt_keygeneration',             // numeric             max lenght 3              -
    ];

    /**
     * @var string[]
     */
    private $optionalFields = [
        'pmt_selleriban',
        'pmt_userlocale',
        'pmt_invoicefromseller',
        'pmt_paymentmethod',
        'pmt_buyeridentificationcode',
        'pmt_buyerphone',
        'pmt_buyeremail',
    ];

    /**
     * @var string[]
     */
    private $optionalRowFields = [
        'pmt_row_articlenr',
        'pmt_row_unit',
    ];

    /**
     * @var string[]
     */
    private $compulsoryRowFields = [
        'pmt_row_name',                  // alphanumeric        max lenght 40             -
        'pmt_row_desc',                  // alphanumeric        max lenght 1000      min lenght 1
        'pmt_row_quantity',              // numeric             max lenght 8         min lenght 1
        'pmt_row_deliverydate',          // alphanumeric        max lenght 10        min lenght 10        dd.MM.yyyy
        'pmt_row_price_gross',           // alphanumeric        max lenght 17        min lenght 4         n,nn
        'pmt_row_price_net',             // alphanumeric        max lenght 17        min lenght 4         n,nn
        'pmt_row_vat',                   // alphanumeric        max lenght 5         min lenght 4         n,nn
        'pmt_row_discountpercentage',    // alphanumeric        max lenght 5         min lenght 4         n,nn
        'pmt_row_type',                  // numeric             max lenght 5         min lenght 1
    ];

    /**
     * @return int[][]
     */
    public function getFieldLengths(): array
    {
        return $this->fieldLengths;
    }

    /**
     * @return string[]
     */
    public function getCompulsoryFields(): array
    {
        return $this->compulsoryFields;
    }

    /**
     * @return string[]
     */
    public function getCompulsoryRowFields(): array
    {
        return $this->compulsoryRowFields;
    }

    /**
     * @return string[]
     */
    public function getOptionalFields(): array
    {
        return $this->optionalFields;
    }

    /**
     * @return string[]
     */
    public function getOptionalRowFields(): array
    {
        return $this->optionalRowFields;
    }
}
