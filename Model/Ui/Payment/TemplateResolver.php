<?php

namespace Svea\SveaPayment\Model\Ui\Payment;

use Magento\Payment\Model\MethodInterface;

class TemplateResolver
{
    /**
     * @param MethodInterface $method
     *
     * @return string
     */
    public function getTemplate(MethodInterface $method): string
    {
        switch ($this->getFormType($method)) {
            case 0:
                $template = 'Svea_SveaPayment/payment/select_form';
                break;
            case 1:
                $template = 'Svea_SveaPayment/payment/icons_form';
                break;
            default:
                $template = 'Svea_SveaPayment/payment/form';
                break;
        }

        return $template;
    }

    /**
     * @param MethodInterface $method
     *
     * @return int
     */
    private function getFormType(MethodInterface $method): int
    {
        return (int)$method->getConfigData('preselect_form_type');
    }
}
