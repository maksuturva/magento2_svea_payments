<?php

namespace Svea\SveaPayment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Svea\SveaPayment\Api\HandlingFee\HandlingFeeApplierInterface;

class ApplyPaymentMethod extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var HandlingFeeApplierInterface
     */
    private $handlingApplier;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        HandlingFeeApplierInterface $handlingApplier
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->handlingApplier = $handlingApplier;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $paymentMethod = $this->getRequest()->getParam('payment_method');
        $methodCode = $this->getRequest()->getParam('method_code') ?? null;
        $methodGroup = $this->getRequest()->getParam('method_group') ?? null;
        $this->storeManager->setCurrentStore($this->getRequest()->getParam('store'));
        $quote = $this->checkoutSession->getQuote();
        $this->handlingApplier->updateHandlingFee($quote, $paymentMethod, $methodCode, $methodGroup);
    }
}
