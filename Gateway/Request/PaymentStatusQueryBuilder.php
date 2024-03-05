<?php
namespace Svea\SveaPayment\Gateway\Request;

use Magento\Sales\Model\Order\Payment;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Payment\AdditionalData;

class PaymentStatusQueryBuilder
{
    const ACTION = 'pmtq_action';
    const VERSION = 'pmtq_version';
    const SELLER_ID = 'pmtq_sellerid';
    const ID = 'pmtq_id';
    const RESP_TYPE = 'pmtq_resptype';
    const KEY_GENERATION = 'pmtq_keygeneration';

    /** @var Config $config */
    private $config;

    /** @var AdditionalData $paymentData */
    private $paymentData;

    public function __construct(
        Config $config,
        AdditionalData $paymentAdditionalData
    ) {
        $this->config = $config;
        $this->paymentData = $paymentAdditionalData;
    }

    /**
     * @param Payment $payment
     *
     * @return array|string[]
     */
    public function build(Payment $payment) : array
    {
        $result = [
            self::ACTION => 'PAYMENT_STATUS_QUERY',
            self::VERSION => '0005',
            self::SELLER_ID => $this->config->getSellerId(),
            self::ID => $this->paymentData->getSveaTransactionId($payment),
            self::RESP_TYPE => 'XML',
            self::KEY_GENERATION => $this->config->getKeyVersion(),
        ];

        return $result;
    }
}
