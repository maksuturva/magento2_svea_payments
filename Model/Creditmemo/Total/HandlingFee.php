<?php

namespace Svea\SveaPayment\Model\Creditmemo\Total;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee as HandlingFeeManager;
use function __;
use function round;

class HandlingFee extends AbstractTotal
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
        $fee = $this->handlingFee->getValue($order);
        $allowedAmount = $fee - $this->handlingFee->getRefundedValue($order);
        if ($creditmemo->hasSveaBaseHandlingFee()) {
            $desiredAmount = round((float)$this->handlingFee->getBaseValue($creditmemo), 2);
            if ($desiredAmount > round($allowedAmount, 2) + 0.0001) {
                $allowedAmount = $order->getBaseCurrency()->format($allowedAmount, null, false);
                throw new LocalizedException(
                    __('Maximum invoicing fee amount allowed to refund is: %1', $allowedAmount)
                );
            }
        } else {
            $desiredAmount = $allowedAmount;
        }
        $this->handlingFee->setValue($creditmemo, $desiredAmount);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $desiredAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $desiredAmount);

        return $this;
    }
}
