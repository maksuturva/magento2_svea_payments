define([
    'Svea_SveaPayment/js/view/checkout/summary/handling'
], function (Component) {
    'use strict';

    return Component.extend({
        isDisplayed: function () {
            return this.getPureValue() > 0;
        },
    });
});
