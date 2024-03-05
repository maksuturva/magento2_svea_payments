<?php

namespace Svea\SveaPayment\Gateway\Response;

use LogicException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Svea\SveaPayment\Gateway\SubjectReader;

class OrderStateObjectFlags implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * OrderStatus constructor.
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response) : void
    {
        $paymentData = $this->subjectReader->readPayment($handlingSubject);
        $payment = $paymentData->getPayment();
        if (!$payment instanceof Payment) {
            throw new LogicException('Order Payment should be provided');
        }
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $stateObject = $this->subjectReader->readStateObject($handlingSubject);
        $stateObject->setData('is_notified', false);
    }
}
