<?php

namespace Svea\SveaPayment\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Svea\SveaPayment\Exception\PaymentHandlingException;
use Svea\SveaPayment\Model\OrderManagement;
use Svea\SveaPayment\Model\QuoteManagement;

class Delayed extends Action
{
    /**
     * @var OrderManagement
     */
    private $orderManagement;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @param Context $context
     * @param OrderManagement $orderManagement
     * @param QuoteManagement $quoteManagement
     * @param OrderResource $orderResource
     */
    public function __construct(
        Context         $context,
        OrderManagement $orderManagement,
        QuoteManagement $quoteManagement,
        OrderResource   $orderResource
    ) {
        parent::__construct($context);
        $this->orderManagement = $orderManagement;
        $this->quoteManagement = $quoteManagement;
        $this->orderResource = $orderResource;
    }

    /**
     * @return ResponseInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws AlreadyExistsException
     */
    public function execute(): ResponseInterface
    {
        $params = $this->getRequest()->getParams();
        $order = $this->orderManagement->getLastOrder();

        if (!$this->orderManagement->validateReferenceNumbers($order, $params)) {
            return $this->_redirect(
                'svea_payment/index/error',
                [
                    'type' => PaymentHandlingException::ERROR_TYPE_VALUES_MISMATCH,
                    'message' => \__(
                        'Order id did not match (%1 != %2)',
                        $order->getIncrementId(),
                        $params['pmt_reference'] ?? ''
                    ),
                ]
            );
        }

        if ($order->getId()) {
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $this->orderResource->save($order);
            $this->quoteManagement->setQuoteMode($order, false);
        }

        return $this->_redirect('checkout/onepage/success', ['_secure' => true]);
    }
}
