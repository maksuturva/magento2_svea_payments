<?php
namespace Svea\SveaPayment\Gateway\Response\Payment;

use Magento\Framework\Logger\Monolog as Logger;
use Magento\Sales\Api\Data\OrderInterface as Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Svea\SveaPayment\Exception\OrderNotInvoiceableException;
use Svea\SveaPayment\Exception\PaymentHandlingException;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\Request\PaymentInitializeRequestBuilder;
use Svea\SveaPayment\Gateway\Request\PaymentStatusQueryBuilder;
use Svea\SveaPayment\Model\Order\Status\Query\ResponseHandler;
use Svea\SveaPayment\Model\Order\Invoicing;
use Svea\SveaPayment\Model\OrderManagement;
use Svea\SveaPayment\Model\Payment\Method;
use Svea\SveaPayment\Model\QuoteManagement;
use Svea\SveaPayment\Gateway\Http\ApiClient;

class SuccessHandler
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Method
     */
    private $methods;

    /**
     * @var OrderManagement
     */
    private $orderManagement;

    /**
     * @var Invoicing
     */
    private $invoicing;

    /**
     * @var PaymentInitializeRequestBuilder
     */
    private $requestBuilder;

    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var string[]
     */
    private $mandatoryFields = [
        "pmt_action",
        "pmt_version",
        "pmt_id",
        "pmt_reference",
        "pmt_amount",
        "pmt_currency",
        "pmt_sellercosts",
        "pmt_paymentmethod",
        "pmt_escrow",
    ];

    /**
     * @var bool
     */
    private $isCallback = false;

    /**
     * @var PaymentStatusQueryBuilder
     */
    private $paymentStatusQueryBuilder;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var array
     */
    private $successList = [
        ResponseHandler::STATUS_QUERY_PAID,
        ResponseHandler::STATUS_QUERY_PAID_DELIVERY,
        ResponseHandler::STATUS_QUERY_COMPENSATED
    ];

    /**
     * @param Logger $logger
     * @param Config $config
     * @param Method $methods
     * @param OrderManagement $orderManagement
     * @param Invoicing $invoicing
     * @param PaymentInitializeRequestBuilder $requestBuilder
     * @param OrderSender $orderSender
     * @param QuoteManagement $quoteManagement
     * @param PaymentStatusQueryBuilder $paymentStatusQueryBuilder
     * @param ApiClient $apiClient
     */
    public function __construct(
        Logger                          $logger,
        Config                          $config,
        Method                          $methods,
        OrderManagement                 $orderManagement,
        Invoicing                       $invoicing,
        PaymentInitializeRequestBuilder $requestBuilder,
        OrderSender                     $orderSender,
        QuoteManagement                 $quoteManagement,
        PaymentStatusQueryBuilder       $paymentStatusQueryBuilder,
        ApiClient                       $apiClient
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->methods = $methods;
        $this->orderManagement = $orderManagement;
        $this->invoicing = $invoicing;
        $this->requestBuilder = $requestBuilder;
        $this->orderSender = $orderSender;
        $this->quoteManagement = $quoteManagement;
        $this->paymentStatusQueryBuilder = $paymentStatusQueryBuilder;
        $this->apiClient = $apiClient;
    }

    /**
     * @param array $params
     * @param bool $isCallback
     *
     * @throws OrderNotInvoiceableException
     * @throws PaymentHandlingException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function handle(array $params, bool $isCallback): void
    {
        $this->isCallback = $isCallback;
        $this->validateMandatoryFields($params);
        $order = $this->orderManagement->getOrderByPaymentId($params['pmt_id']);

        $this->validateOrder($order, $params);

        if (!$order->canInvoice()) {
            throw new OrderNotInvoiceableException(\__('Order cannot be invoiced'), $order);
        }

        $request = $this->requestBuilder->buildFrom($order->getPayment());
        $this->validateRequestAndResponse($order, $request, $params);

        if(!$this->validatePaymentStatus($order)) {
            throw new PaymentHandlingException(\__('Order payment status failed:'), $order, null,500);
        }

        if ($order->getId()) {
            try {
                $this->processOrder($order, $request, $params);
            } catch (\Exception $e) {
                $this->logger->error(
                    $this->formatLogMessage('Order status message failed: ' . $e->getMessage(), $order)
                );
                throw new PaymentHandlingException(
                    \__('Order status update failed (%1)'),
                    $order,
                    null,
                    500,
                    [],
                    $e
                );
            }
        }
    }

    /**
     * @param $order
     * @return bool
     */
    private function validatePaymentStatus($order): bool {
        $payment = $order->getPayment();
        $paymentStatusQuery = $this->paymentStatusQueryBuilder->build($payment);
        $paymentStatusResponse = $this->apiClient->paymentStatusQuery($paymentStatusQuery);

        if(!in_array($paymentStatusResponse[ResponseHandler::RESPONSE_STATUS_CODE], $this->successList)) {
            $this->logger->error(
                $this->formatLogMessage('Order payment status query returned order not paid: ', $order)
            );
            return false;
        }

        return true;
    }
    /**
     * @param string $message
     * @param Order|null $order
     *
     * @return string
     */
    private function formatLogMessage(string $message, ?Order $order = null): string
    {
        if ($order) {
            $prefix = \sprintf('[Order "#%s" Payment', $order->getIncrementId());
        } else {
            $prefix = '[Order Payment';
        }
        if ($this->isCallback) {
            $prefix .= ' Callback';
        }

        return \sprintf("%s] %s", $prefix, $message);
    }

    /**
     * @param array $params
     *
     * @throws PaymentHandlingException
     */
    private function validateMandatoryFields(array $params): void
    {
        $missing = \array_diff_key(\array_flip($this->mandatoryFields), $params);
        if (!empty($missing)) {
            $this->logger->error($this->formatLogMessage('Missing required mandatory fields'), [
                'missing' => $missing,
            ]);
            throw new PaymentHandlingException(
                \__('Missing required mandatory fields (%1)'),
                null,
                PaymentHandlingException::ERROR_TYPE_EMPTY_FIELD,
                400
            );
        }
    }

    /**
     * @param Order $order
     * @param array $params
     *
     * @throws PaymentHandlingException
     * @throws \Exception
     */
    private function validateOrder(Order $order, array $params): void
    {
        if (!$this->orderManagement->validateReferenceNumbers($order, $params)) {
            $this->logger->error($this->formatLogMessage('Order reference number did not match received value'));
            throw new PaymentHandlingException(\__('Order validation failed (%1)'), $order, 500);
        }
    }

    /**
     * @param Order $order
     * @param array $request
     * @param array $response
     *
     * @throws PaymentHandlingException
     */
    private function validateRequestAndResponse(Order $order, array $request, array $response): void
    {
        $ignored = ['pmt_escrow', 'pmt_paymentmethod', 'pmt_reference', 'pmt_sellercosts'];
        foreach ($response as $key => $value) {
            if (\in_array($key, $ignored)) {
                continue;
            }
            if ($request[$key] != $value) {
                $this->logger->error($this->formatLogMessage(
                    \sprintf('Values mismatch: request "%s" != response "%s"', $request[$key], $value)
                ));
                throw new PaymentHandlingException(
                    \__('Values mismatch'),
                    $order,
                    PaymentHandlingException::ERROR_TYPE_VALUES_MISMATCH,
                    500
                );
            }
        }

        if ($request['pmt_sellercosts'] > $response['pmt_sellercosts']) {
            $this->logger->error($this->formatLogMessage(
                \sprintf(
                    'Sellercost mismatch: request "%s" != response "%s"',
                    $request['pmt_sellercosts'],
                    $response['pmt_sellercosts']
                )
            ), [
                'new_sellercosts' => $response['pmt_sellercosts'],
                'old_sellercosts' => $request['pmt_sellercosts'],
            ]);
            throw new PaymentHandlingException(
                \__('Sellercost values mismatch'),
                $order,
                PaymentHandlingException::ERROR_TYPE_SELLERCOSTS_VALUES_MISMATCH,
                500,
                [
                    'new_sellercosts' => $response['pmt_sellercosts'],
                    'old_sellercosts' => $request['pmt_sellercosts'],
                ]
            );
        }
    }

    /**
     * @param Order $order
     * @param array $request
     * @param array $response
     *
     * @throws OrderNotInvoiceableException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processOrder(Order $order, array $request, array $response): void
    {
        $isDelayedCapture = $this->methods->isDelayedCapture($response['pmt_paymentmethod']);
        $statusText = $isDelayedCapture ? 'authorized' : 'captured';

        // extrapaymentmethodfee has potentially caused sellercosts changes -> include notice to message
        if ($request['pmt_sellercosts'] != $response['pmt_sellercosts']) {
            $sellercostsChange = $response['pmt_sellercosts'] - $request['pmt_sellercosts'];
            if ($sellercostsChange > 0) {
                $msg = \__(
                    "Payment %1 by Svea Payments. NOTE: Change in the sellercosts + %2 EUR.",
                    [$statusText, $sellercostsChange]
                );
            } else {
                $msg = \__(
                    "Payment %1 by Svea Payments. NOTE: Change in the sellercosts + %2 EUR.",
                    [$statusText, $sellercostsChange]
                );
            }
        } else {
            $msg = \__("Payment %1 by Svea Payments", $statusText);
        }

        if (!$isDelayedCapture) {
            /* create invoice and add transaction */
            if ($this->config->getGenerateInvoice()) {
                $this->invoicing->createInvoice($order);
            }
        }
        if (!$order->getEmailSent()) {
            try {
                $this->orderSender->send($order);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }

        $processStatus = $this->config->getPaidOrderStatus();
        if (empty($processStatus)) {
            $processStatus = \Magento\Sales\Model\Order::STATE_PROCESSING;
        }

        $processState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $order->setState($processState);
        $order->addStatusToHistory($processStatus, $msg);
        /** deprecated ? */
        $order->save();

        $this->logger->info($this->formatLogMessage('Updated order'));

        $this->quoteManagement->setQuoteMode($order, false);
    }
}
