<?php
namespace Svea\SveaPayment\Block\Adminhtml\Sales\Order\Invoice;

use Magento\Sales\Model\Order\Invoice;

class HandlingFeeTax extends \Svea\SveaPayment\Block\Adminhtml\Sales\Order\HandlingFeeTax
{
    /**
     * @return Invoice
     */
    public function getInvoice()
    {
        return $this->getParentBlock()->getInvoice();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        parent::initTotals();
        $this->getInvoice()->setGrandTotal($this->feeManager->getTaxAmount($this->getOrder()));

        return $this;
    }
}
