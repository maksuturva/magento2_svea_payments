<?php

namespace Svea\SveaPayment\Gateway\Request;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class ResponseUrlBuilder implements BuilderInterface
{
    /**
     * Max length 200
     */
    const OK_RETURN = 'pmt_okreturn';

    /**
     * Max length 200
     */
    const ERROR_RETURN = 'pmt_errorreturn';

    /**
     * Max length 200
     */
    const CANCEL_RETURN = 'pmt_cancelreturn';

    /**
     * Max length 200
     */
    const DELAYED_PAY_RETURN = 'pmt_delayedpayreturn';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        UrlInterface $urlBuilder
    ) {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function build(array $buildSubject): array
    {
        $result = [
            self::OK_RETURN => $this->urlBuilder->getUrl('svea_payment/index/success'),
            self::ERROR_RETURN => $this->urlBuilder->getUrl('svea_payment/index/error'),
            self::CANCEL_RETURN => $this->urlBuilder->getUrl('svea_payment/index/cancel'),
            self::DELAYED_PAY_RETURN => $this->urlBuilder->getUrl('svea_payment/index/delayed'),
        ];

        return $result;
    }
}
