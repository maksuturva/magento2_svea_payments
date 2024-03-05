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
                type: 'svea_payment',
                component: 'Svea_SveaPayment/js/view/payment/method-renderer/svea_payment'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
