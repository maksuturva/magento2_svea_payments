<?php
namespace Svea\SveaPayment\Model\Quote\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Svea\SveaPayment\Model\Quote\Total\HandlingFeeResolver;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee as HandlingFeeManager;

class HandlingFeeTax extends AbstractTotal
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
        return \__('Invoicing Fee Tax');
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $amount = $this->feeResolver->calculateHandlingFee($quote);

        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $this->feeResolver->calculateHandlingFeeTax($quote, $amount),
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
        $handlingFeeTax = $this->feeResolver->calculateHandlingFeeTax($quote, $amount);
        $handlingFeeTaxRate = $this->feeResolver->getMaxTaxRateFromItems($quote);

        $this->feeManager->setTaxAmountPercentage($quote, $handlingFeeTaxRate);
        $this->feeManager->setTaxAmountPercentage($total, $handlingFeeTaxRate);

        $this->feeManager->setTaxAmount($quote, $handlingFeeTax);
        $this->feeManager->setBaseTaxAmount($quote, $handlingFeeTax);

        $this->feeManager->setTaxAmount($total, $handlingFeeTax);
        $this->feeManager->setBaseTaxAmount($total, $handlingFeeTax);

        $total->addTotalAmount('svea_handling_fee_tax', $this->feeManager->getTaxAmount($total));
        $total->addBaseTotalAmount('svea_base_handling_fee_tax', $this->feeManager->getTaxAmount($total));

        return $this;
    }
}
