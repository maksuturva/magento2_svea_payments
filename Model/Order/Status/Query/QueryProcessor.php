<?php

namespace Svea\SveaPayment\Model\Order\Status\Query;

use Exception;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Framework\Phrase;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\DateTime as Date;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Api\Order\StatusCheckerInterface;
use Svea\SveaPayment\Gateway\Http\ApiClient;
use Svea\SveaPayment\Gateway\Request\PaymentStatusQueryBuilder;
use function sprintf;

class QueryProcessor implements StatusCheckerInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ApiClient
     */
    private $client;

    /**
     * @var PaymentStatusQueryBuilder
     */
    private $dataBuilder;

    /**
     * @var ResponseHandler
     */
    private $responseHandler;

    /**
     * @var ResponseValidator
     */
    private $responseValidator;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Date
     */
    private $date;

    public function __construct(
        Logger                    $logger,
        ApiClient                 $client,
        PaymentStatusQueryBuilder $dataBuilder,
        ResponseHandler           $responseHandler,
        ResponseValidator         $responseValidator,
        DateTime                  $dateTime,
        Date                      $date
    ) {
        $this->logger = $logger;
        $this->client = $client;
        $this->dataBuilder = $dataBuilder;
        $this->responseHandler = $responseHandler;
        $this->responseValidator = $responseValidator;
        $this->dateTime = $dateTime;
        $this->date = $date;
    }

    /**
     * @param Order $order
     *
     * @return Status
     */
    public function execute(Order $order): Status
    {
        try {
            $order->setData(
                'svea_last_status_query',
                $this->dateTime->formatDate($this->date->gmtTimestamp())
            );
            $payment = $order->getPayment();
            $data = $this->dataBuilder->build($payment);
            $response = $this->client->paymentStatusQuery($data);
            $this->validateAuthenticity($response);

            return $this->handleResponse($order, $response);
        } catch (Exception $e) {
            return $this->responseHandler->createError($e->getMessage());
        }
    }

    /**
     * @param array $response
     *
     * @return void
     * @throws Exception
     */
    private function validateAuthenticity(array $response): void
    {
        if (!$this->responseValidator->validateFields($response)) {
            throw new Exception('The authenticity of the answer could\'t be verified.');
        }
    }

    /**
     * @param Order $order
     * @param array $response
     *
     * @return Status
     * @throws Exception
     */
    private function handleResponse(Order $order, array $response): Status
    {
        $orderIdValidation = $this->responseValidator->validateOrderId($order, $response);
        if ($orderIdValidation !== true) {
            return $this->handleValidationError($order, $orderIdValidation);
        }
        $paymentIdValidation = $this->responseValidator->validatePaymentId($order, $response);
        if ($paymentIdValidation !== true) {
            return $this->handleValidationError($order, $paymentIdValidation);
        }
        $amountsValidation = $this->responseValidator->validateAmounts($order, $response);
        if ($amountsValidation !== true) {
            return $this->handleValidationError($order, $amountsValidation);
        }

        return $this->executeHandler($order, $response);
    }

    /**
     * @param Order $order
     * @param string|Phrase $message
     *
     * @return Status
     */
    private function handleValidationError(Order $order, $message): Status
    {
        $this->logger->error($message, $this->getLoggerContext($order));

        return $this->responseHandler->createError($message);
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    private function getLoggerContext(Order $order): array
    {
        return [
            'order_id' => $order->getId(),
            'order_increment_id' => $order->getIncrementId(),
        ];
    }

    /**
     * @param Order $order
     * @param array $response
     *
     * @return Status
     * @throws Exception
     */
    private function executeHandler(Order $order, array $response): Status
    {
        $result = $this->responseHandler->execute($order, $response);
        $message = sprintf('Status Query [#%s]: %s', $order->getIncrementId(), $result->getMessage());
        switch ($result->getCode()) {
            case Status::CODE_SUCCESS:
                $this->logger->info($message, $this->getLoggerContext($order));
                break;
            case Status::CODE_NOTICE:
                $this->logger->notice($message, $this->getLoggerContext($order));
                break;
            case Status::CODE_ERROR:
                $this->logger->error($message, $this->getLoggerContext($order));
                break;
        }

        return $result;
    }
}
