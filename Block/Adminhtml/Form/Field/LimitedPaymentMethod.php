<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Svea\SveaPayment\Model\System\Config\Source\PaymentMethods;

class LimitedPaymentMethod extends Select
{
    /**
     * Payment methods that are allowed to be used for fees
     */
    const LIMITED_PAYMENT_METHODS = ['FI70', 'FIIN', 'FI72', 'FIBI'];

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
    public function setInputName(string $value): LimitedPaymentMethod
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
    public function setInputId(string $value): LimitedPaymentMethod
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
            $options = array_filter($this->paymentMethods->toOptionArray(), function ($option) {
                return in_array($option['value'], self::LIMITED_PAYMENT_METHODS, true);
            });

            $this->setOptions($options);
        }

        return parent::_toHtml();
    }
}
