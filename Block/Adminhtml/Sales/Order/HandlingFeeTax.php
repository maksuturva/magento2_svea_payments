<?php
namespace Svea\SveaPayment\Block\Adminhtml\Sales\Order;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\Template;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee as HandlingFeeManager;

class HandlingFeeTax extends \Magento\Framework\View\Element\Template
{
    /**
     * @var DataObjectFactory
     */
    protected $objectFactory;

    /**
     * @var HandlingFeeManager
     */
    protected $feeManager;

    /**
     * @param Template\Context $context
     * @param DataObjectFactory $objectFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        DataObjectFactory $objectFactory,
        HandlingFeeManager $feeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->objectFactory = $objectFactory;
        $this->feeManager = $feeManager;
    }

    /**
     * @return bool
     */
    public function displayFullSummary()
    {
        return true;
    }

    /**
     * @return mixed
     */
    public function getStore()
    {
        return $this->getOrder()->getStore();
    }

    /**
     * @return mixed
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * @return mixed
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * @return mixed
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $parent = $this->getParentBlock();
        $parent->addTotalBefore($this->buildTotal(), 'grand_total');

        return $this;
    }

    /**
     * @return \Magento\Framework\DataObject
     */
    protected function buildTotal()
    {
        $parent = $this->getParentBlock();
        $order = $parent->getOrder();
        $taxAmount = $this->feeManager->getTaxAmount($order);

        $handlingFeeTax = $this->objectFactory->create();
        $handlingFeeTax->setData([
            'code' => 'svea_handling_fee_tax',
            'strong' => false,
            'value' => $taxAmount,
            'base_value' => $taxAmount,
            'label' => \__('Invoicing Fee VAT'),
        ]);

        return $handlingFeeTax;
    }
}
