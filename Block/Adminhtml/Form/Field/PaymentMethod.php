<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Svea\SveaPayment\Model\System\Config\Source\PaymentMethods;

class PaymentMethod extends Select
{
    /**
     * @var PaymentMethods
     */
    private PaymentMethods $paymentMethods;

    /**
     * @param Context $context
     * @param PaymentMethods $paymentMethods
     * @param array $data
     */
    public function __construct(
        Context $context,
        PaymentMethods $paymentMethods,
        array $data = []
    ) {
        $this->paymentMethods = $paymentMethods;
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputName(string $value): PaymentMethod
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputId(string $value): PaymentMethod
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->paymentMethods->toOptionArray());
        }

        return parent::_toHtml();
    }
}
