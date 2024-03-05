<?php

namespace Svea\SveaPayment\Gateway\Command;

use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use Svea\SveaPayment\Gateway\Validator\OrderValidatorInterface;
use Svea\SveaPayment\Model\Payment\Method;

class CaptureCommand implements CommandInterface
{
    /**
     * @var CommandPoolInterface
     */
    private CommandPoolInterface $commandPool;

    /**
     * @var SubjectReaderInterface
     */
    private SubjectReaderInterface $subjectReader;

    /**
     * @var Method
     */
    private Method $method;

    /**
     * @var OrderValidatorInterface
     */
    private OrderValidatorInterface $orderValidator;

    public function __construct(
        CommandPoolInterface    $commandPool,
        SubjectReaderInterface  $subjectReader,
        Method                  $method,
        OrderValidatorInterface $orderValidator
    ) {
        $this->commandPool = $commandPool;
        $this->subjectReader = $subjectReader;
        $this->method = $method;
        $this->orderValidator = $orderValidator;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject)
    {
        $payment = $this->subjectReader->readPayment($commandSubject);
        $this->orderValidator->validate($payment->getOrder()->getCurrentOrder());
        /** @var Adapter $method */
        $method = $payment->getPayment()->getMethodInstance();
        if ($this->method->isDelayedCapture($method->getCode())) {
            $result = $this->commandPool->get(DeliveryCommand::COMMAND_CODE_UPDATE)->execute($commandSubject);
            $this->updateTransaction($payment->getPayment(), $result->get());
        }
    }

    /**
     * @param Payment $payment
     * @param string[] $details
     */
    private function updateTransaction(Payment $payment, array $details): void
    {
        if (isset($details['pkg_id'])) {
            $payment->setTransactionId($details['pkg_id']);
        }
        $payment->setIsTransactionClosed(1);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $details['pkg_id']);
    }
}
