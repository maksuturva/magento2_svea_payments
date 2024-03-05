<?php

namespace Svea\SveaPayment\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Svea\SveaPayment\Gateway\Config\Config;

class PaymentConfigBuilder implements BuilderInterface
{
    /**
     * This parameter value is ignored, but validated to exist and be Y or N
     */
    const ESCROW = 'pmt_escrow';

    /**
     * Version of interface content
     */
    const VERSION = 'pmt_version';

    /**
     * Currency used for the payment. Always EUR
     */
    const CURRENCY = 'pmt_currency';

    /**
     * This field tells the character set that has been used to calculate the hash value for input data
     */
    const CHARSET = 'pmt_charset';

    /**
     * This field tells the character encoding of the input data
     */
    const CHARSET_HTTP = 'pmt_charsethttp';

    /**
     * This parameter value is currently ignored, but validated it exists.
     * Currently always N
     */
    const ESCROW_CHANGE_ALLOWED = 'pmt_escrowchangeallowed';

    /**
     * Currently always NEW_PAYMENT_EXTENDED
    */
    const ACTION = 'pmt_action';

    /**
     * Webstore's secret key generation or version. Default or initial value is 001
     */
    const KEY_GENERATION = 'pmt_keygeneration';


    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param array $buildSubject
     * @return array|string[]
     */
    public function build(array $buildSubject) : array
    {
        $result = [
            self::ESCROW => 'N',
            self::VERSION => '0004',
            self::CURRENCY => 'EUR',
            self::CHARSET => 'UTF-8',
            self::CHARSET_HTTP => 'UTF-8',
            self::ESCROW_CHANGE_ALLOWED => 'N',
            self::ACTION => 'NEW_PAYMENT_EXTENDED',
            self::KEY_GENERATION => $this->config->getKeyVersion()
        ];

        return $result;
    }
}
