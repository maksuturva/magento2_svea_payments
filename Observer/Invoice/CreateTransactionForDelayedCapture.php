<?php

namespace Svea\SveaPayment\Observer\Invoice;

use Magento\Framework\Logger\Monolog as Logger;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Svea\SveaPayment\Api\Checkout\PaymentMethodCollectorInterface;
use Svea\SveaPayment\Model\Payment\Method;
use Magento\Sales\Model\Order\Payment\Transaction;

class CreateTransactionForDelayedCapture implements ObserverInterface
{
    /**
     * @var Method
     */
    private $methodResolver;

    /**
     * @var PaymentMethodCollectorInterface
     */
    private $methodCollector;

    /**
     * @var BuilderInterface
     */
    private $transactionBuilder;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Method $methodResolver
     * @param PaymentMethodCollectorInterface $methodCollector
     * @param BuilderInterface $transactionBuilder
     * @param Logger $logger
     */
    public function __construct(
        Method                          $methodResolver,
        PaymentMethodCollectorInterface $methodCollector,
        BuilderInterface                $transactionBuilder,
        Logger                          $logger
    )
    {
        $this->methodResolver = $methodResolver;
        $this->methodCollector = $methodCollector;
        $this->transactionBuilder = $transactionBuilder;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        try {

            $invoice = $observer->getEvent()->getInvoice();
            $order = $invoice->getOrder();
            $payment = $invoice->getOrder()->getPayment();
            $methodCode = $payment->getAdditionalInformation('svea_method_code');
            $orderMethod = $order->getPayment()->getMethodInstance();

            $additionalData = json_decode($payment->getAdditionalData('svea_transaction_id'));
            $sveaTransactionId = $additionalData->svea_transaction_id;

            if (!$this->methodResolver->isSvea($orderMethod) || !$this->methodResolver->isDelayedCapture($methodCode)) {
                return;
            }

            $transaction = $this->transactionBuilder->setPayment($payment)
                ->setOrder($order)
                ->setTransactionId($sveaTransactionId)
                ->setAdditionalInformation([Transaction::RAW_DETAILS, ['invoice_id' => $invoice->getIncrementId()]])
                ->setFailSafe(true)
                ->build(TransactionInterface::TYPE_CAPTURE);

            $payment->setLastTransId($sveaTransactionId);
            $transaction->setIsClosed(true);
            $transaction->save();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
