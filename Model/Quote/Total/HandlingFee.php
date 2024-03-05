<?php
namespace Svea\SveaPayment\Model\Quote\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee as HandlingFeeManager;

class HandlingFee extends AbstractTotal
{
    /**
     * @var HandlingFeeManager
     */
    private $feeManager;

    /**
     * @var HandlingFeeResolver
     */
    private $feeResolver;

    /**
     * @param HandlingFeeManager $feeManager
     * @param HandlingFeeResolver $feeResolver
     */
    public function __construct(
        HandlingFeeManager $feeManager,
        HandlingFeeResolver $feeResolver
    ) {
        $this->feeManager = $feeManager;
        $this->feeResolver = $feeResolver;
        $this->setCode(HandlingFeeManager::CODE);
    }

    /**
     * @inheritDoc
     */
    public function getLabel()
    {
        return \__('Invoicing Fee');
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' =>  $this->feeResolver->calculateHandlingFee($quote),
        ];
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $items = $shippingAssignment->getItems();
        if (empty($items)) {
            return $this;
        }

        $amount = $this->feeResolver->calculateHandlingFee($quote);

        $this->feeManager->setValue($quote, $amount);
        $this->feeManager->setBaseValue($quote, (float)$amount);
        $this->feeManager->setValue($total, $amount);
        $this->feeManager->setBaseValue($total, (float)$amount);

        $total->setTotalAmount($this->getCode(), $amount);
        $total->setBaseTotalAmount($this->getCode(), $amount);

        return $this;
    }
}
