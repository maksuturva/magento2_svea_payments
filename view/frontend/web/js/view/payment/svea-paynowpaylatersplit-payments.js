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
                type: 'svea_paynowpaylatersplit_payment',
                component: 'Svea_SveaPayment/js/view/payment/method-renderer/svea-paynowpaylatersplit-method'
            }
        );
        return Component.extend({});
    }
);
