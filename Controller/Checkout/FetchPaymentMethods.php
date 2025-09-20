<?php

namespace Svea\SveaPayment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Svea\SveaPayment\Api\Checkout\PaymentMethodCollectorInterface;

class FetchPaymentMethods extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PaymentMethodCollectorInterface
     */
    private $methodCollector;

    public function __construct(
        Context                         $context,
        JsonFactory                     $jsonFactory,
        Session                         $checkoutSession,
        StoreManagerInterface           $storeManager,
        PaymentMethodCollectorInterface $methodCollector
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->methodCollector = $methodCollector;
    }

    /**
     * @return Json
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $this->storeManager->setCurrentStore($this->getRequest()->getParam('store'));
        $quote = $this->checkoutSession->getQuote();

        if ($quote->getPayment()->getPaymentMethod() === null) {
            $methods = [];
        } else {
            $method = $quote->getPayment()->getMethodInstance();
            $methods = $this->methodCollector->getAvailableQuoteMethods($method, $quote);
        }

        return $this->jsonFactory->create()->setData($methods);
    }
}
