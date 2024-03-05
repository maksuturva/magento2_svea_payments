<?php

namespace Svea\SveaPayment\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\Order;
use Svea\SveaPayment\Api\HandlingFee\HandlingFeeApplierInterface;

class QuoteManagement
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var HandlingFeeApplierInterface
     */
    private $handlingFeeApplier;

    /**
     * @param QuoteRepository $quoteRepository
     * @param Session $checkoutSession
     * @param HandlingFeeApplierInterface $handlingFeeApplier
     */
    public function __construct(
        QuoteRepository             $quoteRepository,
        Session                     $checkoutSession,
        HandlingFeeApplierInterface $handlingFeeApplier
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->handlingFeeApplier = $handlingFeeApplier;
    }

    /**
     * @param Order $order
     * @param bool $active
     * @throws NoSuchEntityException
     */
    public function setQuoteMode(Order $order, bool $active)
    {
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $quote->setIsActive($active?1:0)->setReservedOrderId($order->getIncrementId());
        $this->quoteRepository->save($quote);
        $this->checkoutSession->setQuoteId($quote->getId());
    }

    /**
     * @return Quote
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getQuote(): Quote
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function resetSessionHandlingFee()
    {
        $this->handlingFeeApplier->updateHandlingFee($this->getQuote());
    }
}
