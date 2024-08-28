<?php
declare(strict_types=1);

namespace Svea\SveaPayment\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class HandlingFee extends AbstractFieldArray
{
    /**
     * @var PaymentMethod
     */
    private $paymentMethodRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'class' => 'required-entry',
            'renderer' => $this->getPaymentMethodRenderer()
        ]);
        $this->addColumn('fee', ['label' => __('Invoicing Fee'), 'class' => 'required-entry']);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $paymentMethod = $row->getPaymentMethod();
        if ($paymentMethod !== null) {
            $options['option_' . $this->getPaymentMethodRenderer()->calcOptionHash($paymentMethod)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return LimitedPaymentMethod
     * @throws LocalizedException
     */
    private function getPaymentMethodRenderer(): LimitedPaymentMethod
    {
        if (!$this->paymentMethodRenderer) {
            $this->paymentMethodRenderer = $this->getLayout()->createBlock(
                LimitedPaymentMethod::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->paymentMethodRenderer;
    }

}
