<?php
namespace Svea\SveaPayment\Model\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Svea\SveaPayment\Api\HandlingFee\HandlingFeeApplierInterface;

class HandlingFeeApplier implements HandlingFeeApplierInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @param QuoteRepository $quoteRepository
     */
    public function __construct(
        QuoteRepository $quoteRepository
    ) {
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @inheritDoc
     */
    public function updateHandlingFee(
        Quote  $quote,
        string $paymentMethod = null,
        string $methodCode = null,
        string $methodGroup = null
    ) {
        $quote->getPayment()->setMethod($paymentMethod);
        $quote->getPayment()->setAdditionalInformation('svea_method_code', $methodCode);
        $quote->getPayment()->setAdditionalInformation('svea_method_group', $methodGroup);
        $quote->unsTotalsCollectedFlag();
        $quote->collectTotals();
        $quote->setTriggerRecollect(true);
        $this->quoteRepository->save($quote);
    }
}
