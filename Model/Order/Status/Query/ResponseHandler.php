<?php
namespace Svea\SveaPayment\Model\Order\Status\Query;

use Magento\Framework\Phrase;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Model\Payment\Method;

class ResponseHandler
{
    const RESPONSE_STATUS_CODE = 'pmtq_returncode';
    const RESPONSE_PAYMENT_METHOD = 'pmtq_paymentmethod';

    // ToDo: Revise these in handleOrder()
    // Paid: 20,30,40,91,92,93,95,98 (Between 20 .. 98).
    // Yleisen tilan kyselyn kannalta tulisi tulkita finaalitiloiksi ja lopettaa ajastettu kysely kun tapahtumalle saadaan jokin näistä tiloista.
    // Poikkeustilanteisssa, jos vaikkapa palautuksen tai toimitustietojen välityksen onnistuminen jää epäselväksi, voi sen jälkeen kysyä tilaa.
    // Not paid: 00,01,10,11,15,19 (Between 00 ... 19)
    // Näissä tiloissa voi kysellä ja odottaa maksun vahvistumista. Tulee suunnitella fiksusti kuinka usein, kuinka kauan, mille tapahtumille kysellään
    // 99 on kokonaan peruutettu, joten tilausta ei kannata merkitä maksetuksi vaikka se mitä todennäköisimmin on käynytkin maksettu-tilassa ennen tähän tilaan päätymistä.
    const STATUS_QUERY_NOT_PAID = "00";
    const STATUS_QUERY_FAILED = "01";
    const STATUS_QUERY_WAITING = "10";
    const STATUS_QUERY_UNPAID = "11";
    const STATUS_QUERY_UNPAID_DELIVERY = "15";
    const STATUS_QUERY_PAID = "20";
    const STATUS_QUERY_PAID_DELIVERY = "30";
    const STATUS_QUERY_COMPENSATED = "40";
    const STATUS_QUERY_PAYER_CANCELLED = "91";
    const STATUS_QUERY_PAYER_CANCELLED_PARTIAL = "92";
    const STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN = "93";
    const STATUS_QUERY_PAYER_RECLAMATION = "95";
    const STATUS_QUERY_CANCELLED = "99"; // Fully cancelled

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Method
     */
    private $methodData;

    /**
     * @var StatusFactory
     */
    private $statusFactory;

    public function __construct(
        Config $config,
        Method $methodData,
        StatusFactory $statusFactory
    ) {
        $this->config = $config;
        $this->methodData = $methodData;
        $this->statusFactory = $statusFactory;
    }

    /**
     * @param Order $order
     * @param array $response
     *
     * @return Status
     * @throws \Exception
     */
    public function execute(Order $order, array $response): Status
    {
        try {
            return $this->handleOrder($order, $response);
        } catch (\Exception $e) {
            return $this->createError($e->getMessage(), $e);
        }
    }

    /**
     * @param Order $order
     * @param array $response
     *
     * @return Status
     * @throws \Exception
     */
    private function handleOrder(Order $order, array $response): Status
    {
        /** @var Status $result */
        $result = $this->statusFactory->create();

        switch ($response[static::RESPONSE_STATUS_CODE]) {
            // Set as paid if not already set
            case static::STATUS_QUERY_PAID:
            case static::STATUS_QUERY_PAID_DELIVERY:
            case static::STATUS_QUERY_COMPENSATED:
                if ($this->methodData->isDelayedCapture($response[static::RESPONSE_PAYMENT_METHOD])) {
                    // In case of delayed capture, payment module never prepares invoice
                    $this->setOrderAsPaid($order, \__('Payment capture authorized by Svea Payments.'));
                    $result->setCode(Status::CODE_SUCCESS)
                           ->setMessage(\__('Payment capture authorized by Svea Payments.'));
                } else {
                    if ($order->hasInvoices() == false) {
                        if ($order->canInvoice()) {
                            $this->prepareInvoice($order);
                            $this->setOrderAsPaid($order, \__('Payment confirmed by Svea Payments.'));
                            $result->setCode(Status::CODE_SUCCESS)
                                   ->setMessage(\__('Payment confirmed by Svea Payments. Invoice saved.'));
                        } else {
                            $result->setCode(Status::CODE_NOTICE)
                                   ->setMessage(\__(
                                       'Order %1 cannot be invoiced or automatic invoicing is not enabled.',
                                       $order->getIncrementId()
                                   ));
                        }
                    } else {
                        /* resolve case when invoice exists but status in database is still pending payment */
                        if ($order->getState() == Order::STATE_PENDING_PAYMENT ||
                            $order->getStatus() == $this->config->getNewOrderStatus()) {
                            $this->setOrderAsPaid($order, \__('Payment confirmed by Svea Payments.'));
                        }
                        $result->setCode(Status::CODE_SUCCESS)
                               ->setMessage(\__('Payment confirmed by Svea Payments. Invoice already exists.'));
                    }
                }
                break;

            // Set payment cancellation with the notice
            case static::STATUS_QUERY_PAYER_CANCELLED:
            case static::STATUS_QUERY_PAYER_CANCELLED_PARTIAL:
            case static::STATUS_QUERY_PAYER_CANCELLED_PARTIAL_RETURN:
            case static::STATUS_QUERY_PAYER_RECLAMATION:
            case static::STATUS_QUERY_CANCELLED:
                $this->updateOrderStatus($order, Order::STATE_CLOSED, \__('Payment closed in Svea Payments.'));
                $result->setCode(Status::CODE_NOTICE)->setMessage(\__('Payment closed in Svea Payments.'));
                break;

            // No change
            case static::STATUS_QUERY_NOT_PAID:
            case static::STATUS_QUERY_FAILED:
            case static::STATUS_QUERY_WAITING:
            case static::STATUS_QUERY_UNPAID:
            case static::STATUS_QUERY_UNPAID_DELIVERY:
            default:
                $result->setCode(Status::CODE_NOTICE)->setMessage(\__('No change, still awaiting payment.'));
                break;
        }

        return $result;
    }

    /**
     * @param string|Phrase $message
     * @param \Exception|null $exception
     *
     * @return Status
     */
    public function createError($message, ?\Exception $exception = null): Status
    {
        /** @var Status $result */
        $result = $this->statusFactory->create();

        $result->setCode(Status::CODE_ERROR)->setMessage($message)->setException($exception);

        return $result;
    }

    /**
     * @return string
     */
    private function getPaidOrderStatus(): string
    {
        if ($this->config->getPaidOrderStatus()) {
            return $this->config->getPaidOrderStatus();
        } else {
            return Order::STATE_PROCESSING;
        }
    }

    /**
     * @param Order $order
     * @param string|Phrase $status
     * @param string|Phrase $comment
     * @param string|Phrase|null $state
     * @param bool $visibleOnFront
     *
     * @throws \Exception
     */
    private function updateOrderStatus(Order $order, $status, $comment, $state = null, bool $visibleOnFront = true): void
    {
        if (!$state) {
            $state = $status;
        }
        $order->setState($state);
        $order->addCommentToStatusHistory($comment, $status, $visibleOnFront);
        $order->save();
    }

    /**
     * @param Order $order
     * @param string|Phrase $comment
     */
    private function setOrderAsPaid(Order $order, $comment): void
    {
        $status = $this->getPaidOrderStatus();
        $this->updateOrderStatus($order, $status, $comment);
    }

    /**
     * @param Order $order
     */
    private function prepareInvoice(Order $order): void
    {
        $invoice = $order->prepareInvoice();
        $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
        // Do capture in register step
        $invoice->register();
        $order->addRelatedObject($invoice);
    }
}
