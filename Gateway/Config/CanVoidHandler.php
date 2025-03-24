<?php

namespace Svea\SveaPayment\Gateway\Config;

use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;

class CanVoidHandler implements ValueHandlerInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * CanVoidHandler constructor.
     * @param SubjectReaderInterface $subjectReader
     */
    public function __construct(
        SubjectReaderInterface $subjectReader
    ) {
        $this->subjectReader  = $subjectReader;
    }

    /**
     * Retrieve method configured value
     *
     * @param array $subject
     * @param int|null $storeId
     *
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function handle(array $subject, $storeId = null)
    {
        $paymentDO = $this->subjectReader->readPayment($subject);

        $payment = $paymentDO->getPayment();

        return $payment instanceof Payment && !(bool)$payment->getAmountPaid();
    }
}
