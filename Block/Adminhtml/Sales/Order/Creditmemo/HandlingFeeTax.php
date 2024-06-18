<?php
namespace Svea\SveaPayment\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;

class HandlingFeeTax extends \Svea\SveaPayment\Block\Adminhtml\Sales\Order\HandlingFeeTax
{
    /**
     * @return Creditmemo
     */
    public function getCreditMemo()
    {
        return $this->getParentBlock()->getCreditMemo();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        parent::initTotals();
        $this->getCreditMemo()->setGrandTotal($this->feeManager->getTaxAmount($this->getOrder()));

        return $this;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    protected function buildTotal()
    {
        $total = parent::buildTotal();
        $total->setData('block_name', 'svea_handling_fee_tax');

        return $total;
    }
}
