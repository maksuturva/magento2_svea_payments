<?php

namespace Svea\SveaPayment\Model\Creditmemo\Total;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee as HandlingFeeManager;
use function __;
use function round;

class HandlingFeeTax extends AbstractTotal
{
    /**
     * @var HandlingFeeManager
     */
    private HandlingFeeManager $handlingFee;

    /**
     * @param HandlingFeeManager $handlingFee
     * @param array $data
     */
    public function __construct(
        HandlingFeeManager $handlingFee,
        array              $data = []
    ) {
        parent::__construct($data);
        $this->handlingFee = $handlingFee;
    }

    /**
     * @inheritDoc
     */
    public function collect(Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $fee = $this->handlingFee->getTaxAmount($order);
        $allowedAmount = $fee - $this->handlingFee->getRefundedTaxValue($order);
        $desiredAmount = round((float)$this->handlingFee->getBaseTaxAmount($creditmemo), 2);
        if ($desiredAmount > round($allowedAmount) + 0.0001) {
            $allowedAmount = $order->getBaseCurrency()->format($allowedAmount, null, false);
            throw new LocalizedException(
                __('Maximum invoicing fee tax amount allowed to refund is: %1', $allowedAmount)
            );
        }
        $this->handlingFee->setTaxAmount($creditmemo, $allowedAmount);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $allowedAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $desiredAmount);

        return $this;
    }
}
