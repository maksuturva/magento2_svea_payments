define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'svea_card_payment',
                component: 'Svea_SveaPayment/js/view/payment/method-renderer/card-svea-payments'
            }
        );
        return Component.extend({});
    }
);
