define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/get-totals',
], function (quote, fullScreenLoader, getTotalsAction) {
    'use strict';

    return function (paymentMethod) {
        quote.paymentMethod(paymentMethod);

        fullScreenLoader.startLoader();

        const extension_attributes = paymentMethod.extension_attributes || null;
        const methodCode = extension_attributes && extension_attributes.svea_preselected_payment_method
            ? extension_attributes.svea_preselected_payment_method
            : null;
        const methodGroup = extension_attributes && extension_attributes.svea_method_group
            ? extension_attributes.svea_method_group
            : null;

        if (methodCode == null) {
            getTotalsAction([]);
            fullScreenLoader.stopLoader();
            return;
        }

        let data = {
            store: quote.getStoreCode(),
            payment_method: paymentMethod.method,
            method_code: methodCode,
            method_group: methodGroup
        };

        fetch('/svea_payment/checkout/applyPaymentMethod?' + new URLSearchParams(data).toString())
            .catch((error) => {
                console.log(error);
                return true;
            })
            .then(() => {
                getTotalsAction([]);
                fullScreenLoader.stopLoader();
            });
    }
});
