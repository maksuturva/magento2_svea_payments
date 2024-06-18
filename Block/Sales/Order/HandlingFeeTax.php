<?php
namespace Svea\SveaPayment\Block\Sales\Order;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\View\Element\Context;
use Svea\SveaPayment\Model\Sales\Total\HandlingFee as HandlingFeeManager;

class HandlingFeeTax extends \Magento\Framework\View\Element\AbstractBlock
{
    /**
     * @var DataObjectFactory
     */
    private $objectFactory;

    /**
     * @var HandlingFeeManager
     */
    private $feeManager;

    public function __construct(
        Context $context,
        DataObjectFactory $objectFactory,
        HandlingFeeManager $feeManager,
        array   $data = []
    ) {
        parent::__construct($context, $data);
        $this->objectFactory = $objectFactory;
        $this->feeManager = $feeManager;
    }

    /**
     * @return $this
     */
    public function initTotals()
    {
        $order = $this->getOrder();
        $amount = $this->feeManager->getTaxAmount($order);

        if ($amount > 0) {
            $handlingFee = $this->objectFactory->create();
            $handlingFee->setData([
                'code' => HandlingFeeManager::TAX_AMOUNT_CODE,
                'strong' => false,
                'value' => $amount,
                'base_value' => $amount,
                'label' => \__('Invoicing Fee Tax'),
            ]);
            $this->getParentBlock()->addTotalBefore($handlingFee, 'grand_total');
        }

        return $this;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }
}

