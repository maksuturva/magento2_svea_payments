<?php
namespace Svea\SveaPayment\Block\Adminhtml\Sales\Order\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;

class HandlingFee extends \Svea\SveaPayment\Block\Adminhtml\Sales\Order\HandlingFee
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
        $this->getCreditMemo()->setGrandTotal($this->feeManager->getValue($this->getOrder()));

        return $this;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    protected function buildTotal()
    {
        $total = parent::buildTotal();
        $total->setData('block_name', 'svea_handling_fee');

        return $total;
    }
}
