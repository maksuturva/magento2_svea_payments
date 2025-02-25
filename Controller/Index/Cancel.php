<?php

namespace Svea\SveaPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Logger\Monolog as Logger;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Exception\OrderAlreadyPaidException;
use Svea\SveaPayment\Exception\PaymentHandlingException;
use Svea\SveaPayment\Gateway\Response\Payment\SuccessHandler;

class Cancel extends Action
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var SuccessHandler
     */
    private SuccessHandler $successHandler;

    /**
     * @param Context $context
     * @param Logger $logger
     * @param SuccessHandler $successHandler,
     */
    public function __construct(
        Context $context,
        Logger $logger,
        SuccessHandler $successHandler,
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->successHandler = $successHandler;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $pmtId = $this->getRequest()->getParam('pmt_id');

        if (empty($pmtId)) {
            $this->messageManager->addErrorMessage(\__('Unknown error on Svea Payments.'));
            return $this->_redirect(
                'svea_payment/index/error',
                ['type' => PaymentHandlingException::ERROR_TYPE_VALUES_MISMATCH]
            );
        }

        try {
            $this->logger->info(\sprintf('Cancel action controller request for payment %s', $pmtId));
            if ($this->successHandler->handleCancel($pmtId) == Order::STATE_PROCESSING) {
                return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
            }
            $this->messageManager->addSuccessMessage(\__('You have cancelled your payment in Svea Payments.'));
        } catch (OrderAlreadyPaidException $exception) {
            $this->messageManager->addErrorMessage(\__('Unable to cancel order that has already been paid.'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->_redirect('checkout/cart');
    }
}
