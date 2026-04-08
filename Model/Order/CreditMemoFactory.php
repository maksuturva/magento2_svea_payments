<?php
namespace Svea\SveaPayment\Model\Order;

use Svea\SveaPayment\Model\Sales\Total\HandlingFee;

class CreditMemoFactory extends \Magento\Sales\Model\Order\CreditmemoFactory
{
    /**
     * @var HandlingFee
     */
    private $handlingFee;

    public function __construct(
        \Magento\Sales\Model\Convert\OrderFactory    $convertOrderFactory,
        \Magento\Tax\Model\Config                    $taxConfig,
        HandlingFee                                  $handlingFee,
        ?\Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($convertOrderFactory, $taxConfig, $serializer);
        $this->handlingFee = $handlingFee;
    }

    /**
     * @inheritDoc
     */
    protected function initData($creditmemo, $data)
    {
        parent::initData($creditmemo, $data);
        if (isset($data['svea_handling_fee'])) {
            $amount = (float)$data['svea_handling_fee'];
            $this->handlingFee->setValue($creditmemo, $amount);
            $this->handlingFee->setBaseValue($creditmemo, $amount);
        }
        if (isset($data['svea_base_handling_fee'])) {
            $baseAmount = (float)$data['svea_base_handling_fee'];
            $this->handlingFee->setBaseValue($creditmemo, $baseAmount);
            if (!isset($data['svea_handling_fee'])) {
                $this->handlingFee->setValue($creditmemo, $baseAmount);
            }
        }
        if (isset($data['svea_handling_fee_tax'])) {
            $taxAmount = (float)$data['svea_handling_fee_tax'];
            $this->handlingFee->setTaxAmount($creditmemo, $taxAmount);
            $this->handlingFee->setTaxBaseValue($creditmemo, $taxAmount);
        }
        if (isset($data['svea_base_handling_fee_tax'])) {
            $baseTaxAmount = (float)$data['svea_base_handling_fee_tax'];
            $this->handlingFee->setTaxBaseValue($creditmemo, $baseTaxAmount);
            if (!isset($data['svea_handling_fee_tax'])) {
                $this->handlingFee->setTaxAmount($creditmemo, $baseTaxAmount);
            }
        }
        if (isset($data['svea_iban'])) {
            $creditmemo->setData('svea_iban', $data['svea_iban']);
        }
    }
}
