<?php

namespace Svea\SveaPayment\Observer;

use Magento\Framework\Event\ObserverInterface;

class AssignPaymentAdditionalInfo implements ObserverInterface
{
    const ADDITIONAL_DATA = 'additional_data';
    const EXTENSION_ATTRIBUTES = 'extension_attributes';

    public function execute(\Magento\Framework\Event\Observer $observer) : void
    {
        $payment = $observer->getEvent()->getData('payment_model');
        $data = $observer->getEvent()->getData('data');

        if (isset($data[self::ADDITIONAL_DATA][self::EXTENSION_ATTRIBUTES]) &&
            $data[self::ADDITIONAL_DATA][self::EXTENSION_ATTRIBUTES]->getSveaPreselectedPaymentMethod()
        ) {
            $payment->setAdditionalInformation(
                'svea_preselected_payment_method',
                $data[self::ADDITIONAL_DATA][self::EXTENSION_ATTRIBUTES]->getSveaPreselectedPaymentMethod()
            );
        }
    }
}
