<?php

namespace Svea\SveaPayment\Gateway;

use Magento\Payment\Gateway\Data\PaymentDataObjectFactoryInterface;
use Magento\Payment\Model\InfoInterface;

class SubjectBuilder
{
    /**
     * @var PaymentDataObjectFactoryInterface
     */
    private $paymentFactory;

    /**
     * @param PaymentDataObjectFactoryInterface $paymentFactory
     */
    public function __construct(PaymentDataObjectFactoryInterface $paymentFactory)
    {
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * @param InfoInterface $payment
     *
     * @return array
     */
    public function build(InfoInterface $payment)
    {
        $subject = [
            'payment' => $this->paymentFactory->create($payment),
        ];

        return $subject;
    }
}
