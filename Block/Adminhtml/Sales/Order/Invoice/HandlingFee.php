<?php
namespace Svea\SveaPayment\Block\Adminhtml\Sales\Order\Invoice;

use Magento\Sales\Model\Order\Invoice;

class HandlingFee extends \Svea\SveaPayment\Block\Adminhtml\Sales\Order\HandlingFee
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
        $this->getInvoice()->setGrandTotal($this->feeManager->getValue($this->getOrder()));

        return $this;
    }
}
