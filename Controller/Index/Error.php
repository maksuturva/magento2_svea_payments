<?php

namespace Svea\SveaPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Exception\PaymentHandlingException;
use Svea\SveaPayment\Gateway\Response\Payment\SuccessHandler;
use Svea\SveaPayment\Model\QuoteManagement;

class Error extends Action
{
    /**
     * @var SuccessHandler
     */
    private $successHandler;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    public function __construct(
        Context      $context,
        SuccessHandler $successHandler,
        QuoteManagement $quoteManagement
    ) {
        parent::__construct($context);
        $this->successHandler = $successHandler;
        $this->quoteManagement = $quoteManagement;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $requestParams = $this->getRequest()->getParams();
            if (!isset($requestParams['pmt_id'])) {
                if (\array_key_exists('type', $requestParams)) {
                    $this->processErrorMessages($requestParams);
                }
                throw new \Exception(\implode(',', $requestParams));
            }
            if ($this->successHandler->handleError($this->getRequest()->getParam('pmt_id'), $requestParams) == Order::STATE_PROCESSING) {
                return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
            }
            $this->messageManager->addErrorMessage(\__('Svea Payments returned an error on your payment.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(\__('Something went wrong:' . $exception->getMessage()));
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * @param array $requestParams
     * @return void
     */
    private function processErrorMessages(array $requestParams): void
    {
        switch ($requestParams['type']) {
            case PaymentHandlingException::ERROR_TYPE_EMPTY_FIELD:
                $this->messageManager->addErrorMessage(
                    \__('Gateway returned an empty field %1', $requestParams['field'])
                );
                break;

            case PaymentHandlingException::ERROR_TYPE_VALUES_MISMATCH:
                $this->messageManager->addErrorMessage(
                    \__('Value returned from Svea does not match: %1', \implode(' ', $requestParams))
                );
                break;

            case PaymentHandlingException::ERROR_TYPE_SELLERCOSTS_VALUES_MISMATCH:
                $this->messageManager->addErrorMessage(
                    \__('Shipping and payment costs returned from Svea do not match. %1', $requestParams['message'])
                );
                break;

            default:
                $this->messageManager->addErrorMessage(
                    \__('Unknown error on Svea Payments module. %1', $requestParams)
                );
                break;
        }
    }
}
