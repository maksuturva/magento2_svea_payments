<?php

namespace Svea\SveaPayment\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Helper\Admin;
use Magento\Shipping\Helper\Data as ShippingHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Svea\SveaPayment\Model\System\Config\Source\PaymentMethods;
use function implode;

class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info
{
    /**
     * @var PaymentMethods
     */
    private PaymentMethods $paymentMethods;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Admin $adminHelper
     * @param PaymentMethods $paymentMethods
     * @param array $data
     * @param ShippingHelper|null $shippingHelper
     * @param TaxHelper|null $taxHelper
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Admin $adminHelper,
        PaymentMethods $paymentMethods,
        array $data = [],
        ?ShippingHelper $shippingHelper = null,
        ?TaxHelper $taxHelper = null
    )
    {
        $this->paymentMethods = $paymentMethods;
        parent::__construct($context, $registry, $adminHelper, $data, $shippingHelper, $taxHelper);
    }

    /**
     * @return string
     */
    public function getAdditionalPaymentInfo(): string
    {
        $additionalInfo = [];
        foreach ($this->getOrder()->getPayment()->getAdditionalInformation() as $key => $information) {
            $additionalInfo[] = $key . ': ' . $information;
        }

        return implode(",\n", $additionalInfo);
    }

    /**
     * @return string
     */
    public function getPaymentMethodCode(): string
    {
        $code = $this->getOrder()->getPayment()->getAdditionalInformation()["svea_method_code"] ?? '';
        if ($code === '') {
            return $code;
        }

        return $this->getPaymentMethodLabelByCode($code);
    }

    /**
     * @param string $code
     *
     * @return string
     */
    private function getPaymentMethodLabelByCode(string $code): string
    {
        $methods = $this->paymentMethods->toOptionArray();
        foreach ($methods as $method) {
            $value = $method['value'] ?? null;
            $label = $method['label'] ?? null;
            if ($value === $code && $label) {
                return (string)$label;
            }
        }

        return $code;
    }

    /**
     * @return string
     */
    public function getPaymentMethodGroup(): string
    {
        return $this->getOrder()->getPayment()->getAdditionalInformation()["svea_method_group"] ?? '';
    }
}
