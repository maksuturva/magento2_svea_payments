define([
    'jquery',
    'Magento_Checkout/js/model/quote',
], function ($, quote) {
    'use strict';

    return function (onFetch) {
        let data = {
            store: quote.getStoreCode(),
        };

        $.ajax('/svea_payment/checkout/fetchPaymentMethods', {
            data: data,
            dataType: 'json',
            done: onFetch,
        });
    }
});
