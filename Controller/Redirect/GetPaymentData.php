<?php

namespace Svea\SveaPayment\Controller\Redirect;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Model\Order\Cancellation;

class GetPaymentData extends Action implements
    HttpPostActionInterface,
    HttpGetActionInterface,
    CsrfAwareActionInterface
{
    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var Cancellation
     */
    private Cancellation $cancellation;

    /**
     * GetPaymentData constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Cancellation $cancellation
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        Cancellation $cancellation
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->cancellation = $cancellation;
    }

    public function execute()
    {
        /**
         * @var $resultJson Json
         */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $orderId = $this->checkoutSession->getData('last_order_id');
            if (!\is_numeric($orderId)) {
                throw new InputException(\__('Order Id not found.'));
            }
            $this->checkoutSession->restoreQuote();
            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();
            $paymentInformation = $payment->getAdditionalInformation();
            if (!$paymentInformation['gateway_redirect_url']) {
                throw new InputException(\__('Invalid Payment Information.'));
            }
            $order->setState(Order::STATE_PENDING_PAYMENT);
            $order->setStatus(Order::STATE_PENDING_PAYMENT);
            $this->orderRepository->save($order);
            $resultJson->setData([
                'redirectUrl' => $paymentInformation['gateway_redirect_url'],
            ]);

            return $resultJson;
        } catch (\Throwable $e) {
            $this->cancellation->cancelOrder($order);
            $resultJson->setHttpResponseCode(Exception::HTTP_BAD_REQUEST);

            return $resultJson->setData(['message' => \__(
                'Failed to process payment with error: %1',
                [$e->getMessage()]
            )]);
        }
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
