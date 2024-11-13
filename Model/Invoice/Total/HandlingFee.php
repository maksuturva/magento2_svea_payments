<?php
namespace Svea\SveaPayment\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee as HandlingFeeManager;

class HandlingFee extends AbstractTotal
{
    /**
     * @var HandlingFeeManager
     */
    private $handlingFee;

    public function __construct(
        HandlingFeeManager $handlingFee,
        array $data = []
    ) {
        parent::__construct($data);
        $this->handlingFee = $handlingFee;
    }

    /**
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $amount = $this->handlingFee->getValue($invoice->getOrder());
        $this->handlingFee->setValue($invoice, $amount);
        $this->handlingFee->setBaseValue($invoice, $amount);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $amount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $amount);

        return $this;
    }
}
