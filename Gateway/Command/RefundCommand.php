<?php

namespace Svea\SveaPayment\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Svea\SveaPayment\Gateway\Config\Config;
use Svea\SveaPayment\Gateway\Http\ApiClient;
use Svea\SveaPayment\Gateway\SubjectReaderInterface;
use Svea\SveaPayment\Gateway\Validator\OrderValidatorInterface;
use function abs;
use function array_merge;
use function implode;
use function is_array;
use function sprintf;

class RefundCommand implements CommandInterface
{
    const ACTION_CODE_CANCEL = 'CANCEL';
    const ACTION_CODE_REFUND_AFTER_SETTLEMENT = 'REFUND_AFTER_SETTLEMENT';
    const CANCEL_TYPE_FULL_REFUND = 'FULL_REFUND';
    const CANCEL_TYPE_PARTIAL_REFUND = 'PARTIAL_REFUND';
    const CANCEL_TYPE_PARTIAL_RETURN = 'PARTIAL_REFUND_AND_RETURN_OF_DELIVERIES';
    const CANCEL_TYPE_REFUND_AFTER_SETTLEMENT = 'REFUND_AFTER_SETTLEMENT';
    const PAYMENT_CANCEL_OK = '00';
    const PAYMENT_CANCEL_NOT_FOUND = '20';
    const PAYMENT_CANCEL_ALREADY_SETTLED = '30';
    const PAYMENT_CANCEL_MISMATCH = '31';
    const PAYMENT_CANCEL_ERROR = '90';
    const PAYMENT_CANCEL_FAILED = '99';

    /**
     * @var BuilderInterface
     */
    private BuilderInterface $requestBuilder;

    /**
     * @var ApiClient
     */
    private ApiClient $client;

    /**
     * @var HandlerInterface
     */
    private HandlerInterface $handler;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var SubjectReaderInterface
     */
    private SubjectReaderInterface $subjectReader;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var OrderValidatorInterface
     */
    private OrderValidatorInterface $orderValidator;

    public function __construct(
        BuilderInterface               $requestBuilder,
        ApiClient                      $client,
        HandlerInterface               $handler,
        LoggerInterface                $logger,
        SubjectReaderInterface         $subjectReader,
        Config                         $config,
        OrderValidatorInterface $orderValidator
    ) {
        $this->requestBuilder = $requestBuilder;
        $this->client = $client;
        $this->handler = $handler;
        $this->logger = $logger;
        $this->subjectReader = $subjectReader;
        $this->config = $config;
        $this->orderValidator = $orderValidator;
    }

    /**
     * @param array $commandSubject
     *
     * @return void
     * @throws Exception
     */
    public function execute(array $commandSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($commandSubject);
        $this->orderValidator->validate($paymentDO->getOrder()->getCurrentOrder());
        $amount = $this->subjectReader->read($commandSubject, 'amount');
        $this->refund($commandSubject, $paymentDO->getPayment(), $amount);
    }

    /**
     * @param array $commandSubject
     * @param InfoInterface $payment
     * @param float $amount
     *
     * @return void
     * @throws Exception
     */
    private function refund(array $commandSubject, InfoInterface $payment, float $amount): void
    {
        /** @var OrderInterface $order */
        $order = $payment->getOrder();
        $refundableLeft = abs($order->getBaseTotalInvoiced() - $order->getBaseTotalRefunded());
        if ($refundableLeft < .0001) {
            $cancelType = self::CANCEL_TYPE_FULL_REFUND;
        } else {
            $cancelType = self::CANCEL_TYPE_PARTIAL_REFUND;
        }
        try {
            $this->tryRefunds($commandSubject, $cancelType);
        } catch (Exception $exception) {
            $this->logger->error(sprintf('Svea Refund error: %s', $exception->getMessage()));
            throw $exception;
        }
    }

    /**
     * @param array $commandSubject
     * @param string $cancelType
     *
     * @throws Exception
     */
    private function tryRefunds(array $commandSubject, string $cancelType)
    {
        $commandSubject = $this->updateSubject($commandSubject, self::ACTION_CODE_CANCEL, $cancelType);
        $response = $this->cancel($commandSubject);
        if ($response['pmtc_returncode'] === self::PAYMENT_CANCEL_OK) {
            $this->handler->handle($commandSubject, $response);
        } elseif ($response['pmtc_returncode'] === self::PAYMENT_CANCEL_ALREADY_SETTLED) {
            if (!$this->config->canCancelSettled()) {
                throw new Exception('Can\'t refund settled payments.');
            }
            $commandSubject = $this->updateSubject(
                $commandSubject,
                self::ACTION_CODE_REFUND_AFTER_SETTLEMENT,
                self::CANCEL_TYPE_REFUND_AFTER_SETTLEMENT
            );
            $response = $this->cancel($commandSubject);
            $this->handler->handle($commandSubject, $response);
        }
    }

    /**
     * @param array $commandSubject
     * @param string $actionCode
     * @param string $cancelType
     *
     * @return array
     */
    private function updateSubject(array $commandSubject, string $actionCode, string $cancelType)
    {
        $commandSubject['action_code'] = $actionCode;
        $commandSubject['cancel_type'] = $cancelType;

        return $commandSubject;
    }

    /**
     * @param array $commandSubject
     *
     * @return array
     * @throws Exception
     */
    private function cancel(array $commandSubject): array
    {
        $request = $this->requestBuilder->build($commandSubject);
        $response = $this->client->paymentCancel($request);
        $this->validateResponse($response);

        return $response;
    }

    /**
     * @param array $response
     *
     * @throws Exception
     */
    private function validateResponse(array $response)
    {
        switch ($response['pmtc_returncode']) {
            case self::PAYMENT_CANCEL_OK:
            case self::PAYMENT_CANCEL_ALREADY_SETTLED:
                $error = false;
                break;
            case self::PAYMENT_CANCEL_NOT_FOUND:
                $error = 'Payment not found';
                break;
            case self::PAYMENT_CANCEL_MISMATCH:
                $error = 'Cancel parameters from seller and payer do not match';
                break;
            case self::PAYMENT_CANCEL_ERROR:
                $error = 'Errors in input data';
                if (isset($response['errors'])) {
                    $errorTxt = $this->handleResponseErrors($response);
                    $error = sprintf('%s: %s', $error, $errorTxt);
                }
                break;
            case self::PAYMENT_CANCEL_FAILED:
                // ToDo: Should credit memo be created if "The payment event has already been fully refunded." is returned?
                $error = 'Payment cancellation failed';
                if (isset($response['pmtc_returntext'])) {
                    $error = sprintf('%s: %s', $error, $response['pmtc_returntext']);
                }
                break;
            default:
                $error = 'Refund failed';
                if (isset($response['pmtc_returntext'])) {
                    $error = sprintf('%s: %s', $error, $response['pmtc_returntext']);
                }
                break;
        }
        if ($error !== false) {
            throw new Exception($error, $response['pmtc_returncode']);
        }
    }

    /**
     * @param array $response
     *
     * @return string
     */
    private function handleResponseErrors(array $response)
    {
        $errors = $response['errors'] ?? [];
        $errorsArray = $this->handleErrorsArray($errors);

        return implode(', ', $errorsArray);
    }

    /**
     * Handle errors array recursively if needed
     *
     * @param array $errors
     *
     * @return array
     */
    private function handleErrorsArray(array $errors)
    {
        $errorsArray = [];
        foreach ($errors as $error) {
            if (is_array($error)) {
                $errorsArray = array_merge($errorsArray, $this->handleErrorsArray($error));
            } elseif (!is_array($error)) {
                $errorsArray[] = $error;
            }
        }

        return $errorsArray;
    }
}
