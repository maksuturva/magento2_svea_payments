<?php

namespace Svea\SveaPayment\Gateway\Response;

use Magento\Framework\Logger\Monolog as Logger;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Setup\Exception;
use Svea\SveaPayment\Gateway\SubjectReader;

class RedirectUrlHandler implements HandlerInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        Logger $logger,
        SubjectReader $subjectReader
    ) {
        $this->logger = $logger;
        $this->subjectReader = $subjectReader;
    }

    public function handle(array $handlingSubject, array $response) : void
    {
        $paymentData = $this->subjectReader->readPayment($handlingSubject);

        // http://docs.sveapayments.fi/api/payment-api/payment/s2s-api/#S2SAPI-PaymentprocesswithS2SAPI
        if (!isset($response['pmt_paymenturl'])) {
            $this->logger->error('Response is missing pmt_paymenturl', $response);
            throw new Exception('Something went wrong with the payment. Please try again later.');
        }

        $redirectUrl = $response['pmt_paymenturl'];

        $paymentInfo = $paymentData->getPayment();
        $paymentInfo->setAdditionalInformation(
            'gateway_redirect_url',
            $redirectUrl
        );
    }
}
