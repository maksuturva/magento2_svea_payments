<?php

namespace Svea\SveaPayment\Gateway\Request;

use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Model\InfoInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use Svea\SveaPayment\Model\Order\ReferenceNumberProvider;
use Svea\SveaPayment\Model\Payment\AdditionalData;

class OrderDataBuilder implements BuilderInterface
{
    /**
     * Payment transaction identifier. 20 characters maximum
     */
    const ID = 'pmt_id';

    /**
     * Order identifier. Max length 50
     */
    const ORDER_ID = 'pmt_orderid';

    /*
     * Reference number. Max length 20
     */
    const REFERENCE = 'pmt_reference';

    /**
     * Technical user ID. Max length 15
     */
    const SELLER_ID = 'pmt_sellerid';

    /**
     * Order due date, currently should be today (dd.MM.yyyy)
     */
    const DUEDATE = 'pmt_duedate';

    /**
     * Seller IBAN
     */
    const SELLER_IBAN = 'pmt_selleriban';

    /**
     * Information on the country and language choices of the buyer
     */
    const USER_LOCALE = 'pmt_userlocale';

    /**
     * Parameter can be used, if the chosen payment method is invoice or part payment.
     */
    const SELLER_INVOICE = 'pmt_invoicefromseller';

    /**
     * Payment method’s code FInn
     */
    const PAYMENT_METHOD = 'pmt_paymentmethod';

    /**
     * Buyer’s social security number or company’s business identification code
     */
    const BUYER_CODE = 'pmt_buyeridentificationcode';

    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var ReferenceNumberProvider
     */
    private $refProvider;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var AdditionalData
     */
    private $paymentData;

    /**
     * @var Random
     */
    private $random;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        ReferenceNumberProvider $refProvider,
        Config $config,
        TimezoneInterface $timezone,
        AdditionalData $paymentData,
        Random $random
    ) {
        $this->subjectReader  = $subjectReader;
        $this->refProvider = $refProvider;
        $this->config = $config;
        $this->timezone = $timezone;
        $this->paymentData = $paymentData;
        $this->random = $random;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $orderIncrement = (int)$order->getOrderIncrementId();

        $result = [
            self::ID => $this->getId($payment),
            self::ORDER_ID => $orderIncrement,
            self::REFERENCE => $this->refProvider->getPmtReferenceNumber($orderIncrement + 100),
            self::SELLER_ID => $this->getSellerId(),
            self::DUEDATE => $this->getDueDate(),
        ];

        if ($payment->hasAdditionalInformation('svea_preselected_payment_method')) {
            $result[self::PAYMENT_METHOD] = $payment->getAdditionalInformation('svea_preselected_payment_method');
        }

        return $result;
    }

    /**
     * @param InfoInterface $payment
     * @return string
     * @throws \Exception
     */
    private function getId($payment): string
    {
        $additionalData = $this->paymentData->get($payment);

        if (isset($additionalData[AdditionalData::SVEA_TRANSACTION_ID])) {
            return $additionalData[AdditionalData::SVEA_TRANSACTION_ID];
        }

        $pmtId = $this->random->getRandomString(20);
        $additionalData[AdditionalData::SVEA_TRANSACTION_ID] = $pmtId;
        $this->paymentData->setData($payment, $additionalData);
        $payment->setData(Config::SVEA_PAYMENT_ID, $pmtId);
        $payment->setData(Config::SVEA_SELLER_IR, $this->config->getSellerId());

        return $pmtId;
    }

    /**
     * @return string
     */
    private function getDueDate(): string
    {
        return $this->timezone->date()->format('d.m.Y');
    }

    private function getSellerId() : string
    {
        return $this->config->getSellerId();
    }
}
