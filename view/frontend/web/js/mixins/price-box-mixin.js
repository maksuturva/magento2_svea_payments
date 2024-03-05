/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template'
], function ($, utils, _, mageTemplate) {
    'use strict';

    return function (originalWidget) {
        const prototype = originalWidget.prototype;

        $.widget(prototype.namespace + '.' + prototype.widgetName, originalWidget, {
            qtyInfo: '#qty',

            reloadPrice: function reDrawPrices() {
                var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
                    priceTemplate = mageTemplate(this.options.priceTemplate);

                let finalPriceAmount = 0;

                _.each(this.cache.displayPrices, function (price, priceCode) {
                    price.final = _.reduce(price.adjustments, function (memo, amount) {
                        return memo + amount;
                    }, price.amount);

                    price.formatted = utils.formatPriceLocale(price.final, priceFormat);

                    $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate({
                        data: price
                    }));

                    finalPriceAmount = price.final;

                    let qtyFieldValue = parseInt($(this.qtyInfo).val());

                    if (!isNaN(qtyFieldValue) && qtyFieldValue > 0) {
                        finalPriceAmount *= qtyFieldValue;
                    }
                }, this);

                $('body').trigger(
                    'updatePriceOnProduct',
                    {
                        'finalPriceAmount': finalPriceAmount
                    }
                );
                // return some
            },
        });

        return $[prototype.namespace][prototype.widgetName];
    };
});
