<?php

namespace Svea\SveaPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Logger\Monolog as Logger;
use Svea\SveaPayment\Exception\OrderNotInvoiceableException;
use Svea\SveaPayment\Exception\PaymentHandlingException;
use Svea\SveaPayment\Gateway\Response\Payment\CallbackState;
use Svea\SveaPayment\Gateway\Response\Payment\SuccessHandler;

class Success extends Action
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SuccessHandler
     */
    private $successHandler;

    /**
     * @var CallbackState
     */
    private $callbackResolver;

    public function __construct(
        Context        $context,
        Logger         $logger,
        SuccessHandler $successHandler,
        CallbackState  $callbackResolver
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->successHandler = $successHandler;
        $this->callbackResolver = $callbackResolver;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $isCallback = $this->callbackResolver->resolveIsCallback($params);
        if ($isCallback) {
            $this->logger->info('Callback webhook requested.');
        }

        try {
            $this->successHandler->handle($params, $isCallback);
        } catch (OrderNotInvoiceableException $e) {
            if ($isCallback) {
                return $this->handleCallbackException($e);
            } else {
                $this->messageManager->addErrorMessage(\__('Your order is not valid or is already paid.'));
                return $this->_redirect('checkout/cart');
            }
        } catch (PaymentHandlingException $e) {
            if ($isCallback) {
                return $this->handleCallbackException($e);
            } else {
                return $this->_redirect('svea_payment/index/error', [$e->getErrorParameters()]);
            }
        } catch (\Exception $e) {
            if ($isCallback) {
                return $this->handleCallbackException($e);
            } else {
                $this->logger->error($e->getMessage());
                return $this->_redirect('svea_payment/index/error',[$e->getMessage()]);
            }
        }

        return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
    }

    /**
     * @param \Exception $e
     *
     * @return mixed
     */
    private function handleCallbackException(\Exception $e)
    {
        $httpCode = 500;
        $response = $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'text/html')
            ->setBody($e->getMessage());

        if ($e instanceof PaymentHandlingException) {
            $httpCode = $e->getCode();
        }

        if ($e instanceof OrderNotInvoiceableException) {
            $message = \sprintf(
                'Order cannot be updated or invoiced anymore. Current status %s and state %s',
                $e->getOrder()->getState(),
                $e->getOrder()->getState()
            );
            $this->logger->info($message);
            $response->setBody($message);
        }

        $response->setHeader('HTTP/1.0', $httpCode, true);

        return $response;
    }
}
