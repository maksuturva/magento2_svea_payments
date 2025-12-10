<?php
namespace Svea\SveaPayment\Gateway\Response\Payment;

use Magento\Framework\Logger\Monolog as Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Svea\SveaPayment\Exception\OrderNotInvoiceableException;
use Svea\SveaPayment\Exception\PaymentHandlingException;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\Http\ApiClient;
use Svea\SveaPayment\Gateway\Request\PaymentInitializeRequestBuilder;
use Svea\SveaPayment\Gateway\Request\PaymentStatusQueryBuilder;
use Svea\SveaPayment\Model\Order\Cancellation;
use Svea\SveaPayment\Model\Order\Invoicing;
use Svea\SveaPayment\Model\Order\Status\Query\ResponseHandler;
use Svea\SveaPayment\Model\OrderManagement;
use Svea\SveaPayment\Model\Payment\Method;
use Svea\SveaPayment\Model\Quote\QuoteCancellation;
use Svea\SveaPayment\Model\Source\RestoreShoppingCart;
use Svea\SveaPayment\Model\QuoteManagement;

class SuccessHandler
{
    private Logger $logger;
    private Config $config;
    private Method $methods;
    private OrderManagement $orderManagement;
    private Invoicing $invoicing;
    private PaymentInitializeRequestBuilder $requestBuilder;
    private OrderSender $orderSender;
    private QuoteManagement $quoteManagement;
    private PaymentStatusQueryBuilder $paymentStatusQueryBuilder;
    private ApiClient $apiClient;
    private OrderResource $orderResource;
    private QuoteCancellation $quoteCancellation;
    private Cancellation $cancellation;

    private bool $isCallback = false;
    private array $mandatoryFields = [
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
    private array $successList = [
        ResponseHandler::STATUS_QUERY_PAID,
        ResponseHandler::STATUS_QUERY_PAID_DELIVERY,
        ResponseHandler::STATUS_QUERY_COMPENSATED
    ];

    const ERROR_SELLERCOSTS_VALUES_MISMATCH = 'sellercosts_values_mismatch_error';

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
     * @param OrderResource $orderResource
     * @param QuoteCancellation $quoteCancellation
     * @param Cancellation $cancellation
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
        ApiClient                       $apiClient,
        OrderResource                   $orderResource,
        QuoteCancellation               $quoteCancellation,
        Cancellation                    $cancellation,
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
        $this->orderResource = $orderResource;
        $this->quoteCancellation = $quoteCancellation;
        $this->cancellation = $cancellation;
    }

    /**
     * Handle assumed success response from Svea Payments where the normal outcome is order set to processing.
     * If the payment is not successful the method will throw exception with appropriate message and cancel the order.
     *
     * @param array $params
     * @param bool $isCallback
     *
     * @throws OrderNotInvoiceableException
     * @throws PaymentHandlingException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function handleSuccess(array $params, bool $isCallback): void
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

        if(($status = $this->validatePaymentStatus($order)) === false) {
            if ($this->config->cancelOrderOnFailure()) {
                $order->cancel();
            }
            $this->quoteCancellation->cancelQuote(RestoreShoppingCart::ERROR);
            $order->addCommentToStatusHistory(__('Payment was not validated from Svea Payments'));
            $this->orderResource->save($order);
            throw new PaymentHandlingException(\__('Order payment status failed:'), $order, null,500);
        }
        $order->getPayment()->setAdditionalInformation('svea_method_code', $status[ResponseHandler::RESPONSE_PAYMENT_METHOD]);

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

    /**
     * Handle assumed error response from Svea Payments where the normal outcome is order set to canceled.
     * If the order is in a state where it could be processed we call validation to confirm status with Svea.
     *
     * @param string $pmtId
     * @param array $requestParams
     *
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function handleError(string $pmtId, array $requestParams): string
    {
        $this->isCallback = false;

        $order = $this->orderManagement->getOrderByPaymentId($pmtId);

        $this->quoteManagement->resetSessionHandlingFee();
        if (in_array($order->getState(), [
            Order::STATE_PENDING_PAYMENT,
            Order::STATE_NEW
        ])) {
            if (isset($requestParams['type']) && $requestParams['type'] === self::ERROR_SELLERCOSTS_VALUES_MISMATCH) {
                $order->addCommentToStatusHistory(
                    __(
                        'Mismatch in seller costs returned from Svea Payments. New sellercosts: %1',
                        $this->getNewSellerCost($requestParams)
                    )
                );
            } else {
                if ($order->canInvoice()) {
                    $validatedStatus = $this->validatePaymentStatus($order);
                    if($validatedStatus !== false) {
                        $order->getPayment()->setAdditionalInformation('svea_method_code', $validatedStatus[ResponseHandler::RESPONSE_PAYMENT_METHOD]);
                        $request = $this->requestBuilder->buildFrom($order->getPayment());
                        $response = [
                            'pmt_paymentmethod' => $validatedStatus['pmtq_paymentmethod'],
                            'pmt_sellercosts' => $validatedStatus['pmtq_sellercosts']
                        ];
                        $this->processOrder($order, $request, $response);
                        return $order->getState();
                    }
                }
                if ($this->config->cancelOrderOnFailure()) {
                    $order->cancel();
                }
                $this->quoteCancellation->cancelQuote(RestoreShoppingCart::ERROR);
                $order->addCommentToStatusHistory(__('Error on Svea Payments return'));
            }
            $this->orderResource->save($order);
            return $order->getState();
        }
    }

    /**
     * Handle assumed cancel response from Svea Payments where the normal outcome is order set to canceled.
     * If the order is in a state where it could be processed we call validation to confirm status with Svea.
     *
     * @param string $pmtId
     *
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function handleCancel(string $pmtId): string
    {
        $this->isCallback = false;

        $order = $this->orderManagement->getOrderByPaymentId($pmtId);
        if ($order->canInvoice()) {
            $validatedStatus = $this->validatePaymentStatus($order);
            if($validatedStatus !== false) {
                $order->getPayment()->setAdditionalInformation('svea_method_code', $validatedStatus[ResponseHandler::RESPONSE_PAYMENT_METHOD]);
                $request = $this->requestBuilder->buildFrom($order->getPayment());
                $response = [
                    'pmt_paymentmethod' => $validatedStatus['pmtq_paymentmethod'],
                    'pmt_sellercosts' => $validatedStatus['pmtq_sellercosts']
                ];
                $this->processOrder($order, $request, $response);
                return $order->getState();
            }
        }
        if ($this->config->cancelOrderOnFailure()) {
            $this->cancellation->cancelOrder($order);
        }
        $this->quoteManagement->resetSessionHandlingFee();
        $this->quoteCancellation->cancelQuote(RestoreShoppingCart::CANCEL);
        return $order->getState();
    }

    /**
     * @param array $requestParams
     *
     * @return string
     * @throws Exception
     */
    private function getNewSellerCost(array $requestParams): string
    {
        if (!isset($requestParams['new_sellercosts']) || !isset($requestParams['old_sellercosts'])) {
            throw new \Exception('One of seller costs was not specified');
        }
        $newCost = $requestParams['new_sellercosts'];
        $oldCost = $requestParams['old_sellercosts'];

        return __('%1 EUR, was %2 EUR', $newCost, $oldCost);
    }

    /**
     * @param $order
     * @return bool
     */
    private function validatePaymentStatus($order): bool|array {
        $payment = $order->getPayment();
        $paymentStatusQuery = $this->paymentStatusQueryBuilder->build($payment);
        $paymentStatusResponse = $this->apiClient->paymentStatusQuery($paymentStatusQuery);

        if(!in_array($paymentStatusResponse[ResponseHandler::RESPONSE_STATUS_CODE], $this->successList)) {
            $this->logger->error(
                $this->formatLogMessage('Order payment status query returned order not paid: ', $order)
            );
            return false;
        }
        return $paymentStatusResponse;
    }
    /**
     * @param string $message
     * @param OrderInterface|null $order
     *
     * @return string
     */
    private function formatLogMessage(string $message, ?OrderInterface $order = null): string
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
     * Convert string with space as thousands separator and comma as decimal (1 234,56) to float
     */
    private function toFloat(string $value): float
    {
        return floatval(str_replace(',', '.', str_replace(' ', '', $value)));
    }

    /**
     * @param OrderInterface $order
     * @param array $params
     *
     * @throws PaymentHandlingException
     * @throws \Exception
     */
    private function validateOrder(OrderInterface $order, array $params): void
    {
        if (!$this->orderManagement->validateReferenceNumbers($order, $params)) {
            $this->logger->error($this->formatLogMessage('Order reference number did not match received value'));
            throw new PaymentHandlingException(\__('Order validation failed (%1)'), $order, 500);
        }
    }

    /**
     * @param OrderInterface $order
     * @param array $request
     * @param array $response
     *
     * @throws PaymentHandlingException
     */
    private function validateRequestAndResponse(OrderInterface $order, array $request, array $response): void
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

        $requestSellercosts =  $this->toFloat($request['pmt_sellercosts']);
        $responseSellercosts = $this->toFloat($response['pmt_sellercosts']);

        if ($requestSellercosts > $responseSellercosts) {
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
     * @param OrderInterface $order
     * @param array $request
     * @param array $response
     *
     * @throws OrderNotInvoiceableException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function processOrder(OrderInterface $order, array $request, array $response): void
    {
        $isDelayedCapture = $this->methods->isDelayedCapture($response['pmt_paymentmethod']);
        $statusText = $isDelayedCapture ? 'authorized' : 'captured';

        $requestSellercosts = $this->toFloat($request['pmt_sellercosts']);
        $responseSellercosts = $this->toFloat($response['pmt_sellercosts']);

        // extrapaymentmethodfee has potentially caused sellercosts changes -> include notice to message
        if ($requestSellercosts != $responseSellercosts) {
            $sellercostsChange = $responseSellercosts - $requestSellercosts;
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
            $this->invoicing->createInvoice($order);
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
            $processStatus = Order::STATE_PROCESSING;
        }

        $processState = Order::STATE_PROCESSING;
        $order->setState($processState);
        $order->addStatusToHistory($processStatus, $msg);
        $order->getPayment()->setData(Config::SVEA_MERCHANT_REFERENCE, $response['pmt_reference']);
        $this->orderResource->save($order);

        $this->logger->info($this->formatLogMessage('Updated order'));

        $this->quoteManagement->setQuoteMode($order, false);
    }
}
