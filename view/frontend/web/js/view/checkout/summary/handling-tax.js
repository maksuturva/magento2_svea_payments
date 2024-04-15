define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
], function (ko, Component, quote, totals) {
    'use strict';

    return Component.extend({
        defaults: {
            isFullTaxSummaryDisplayed: window.checkoutConfig.isFullTaxSummaryDisplayed || false,
            template: 'Svea_SveaPayment/checkout/summary/handling-tax',
        },
        totals: quote.getTotals(),
        isTaxDisplayedInGrandTotal: window.checkoutConfig.includeTaxInGrandTotal || false,

        isDisplayed: function () {
            return this.getPureValue() > 0;
        },

        getPureValue: function () {
            let price = 0,
                handlingSegment = totals.getSegment('svea_handling_fee_tax_amount'),
                handlingValue = handlingSegment ? handlingSegment.value : null;

            if (this.totals() && handlingValue) {
                price = parseFloat(handlingValue);
            }

            return price;
        },

        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        }
    });
});
