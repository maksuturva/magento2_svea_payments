define([
   'jquery',
   'ko',
   'uiComponent',
   'Magento_Swatches/js/swatch-renderer'
], function ($, ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            options: {
                path: 'rest/V1/partPaymentCalculator/',
                formHolder: '#product_addtocart_form',
                swatchHolders: '.swatch-input'
            },
            state: {
                isDisabled: ko.observable(true),
                triggerEvent: ko.observable(''),
                totalPriceOfProduct: ko.observable(0),
                previousPriceOfProduct: ko.observable(0),
                isPriceEqualFlag: ko.observable(false),
                isPriceAboveThreshold: ko.observable(false)
            },
            template: 'Svea_SveaPayment/calculator/calculator-holder'
        },

        initialize: function () {
            this._super();

            if (this.productType === 'simple') {
                this.observeCalcBtnAction();
                this.observeProductPriceChange();
                this.productFormSubscriberSimple();
            } else {
                this.observeCalcBtnAction();
                this.observeProductPriceChange();
                this.productFormSubscriberOther();
            }
        },

        observeProductPriceChange: function () {
            // Custom event trigger to get correct data-amount price of product
            $('body').on('updatePriceOnProduct', function (e, data) {
                if (data.finalPriceAmount) {
                    this.state.totalPriceOfProduct(data.finalPriceAmount);
                }
            }.bind(this));
        },

        productFormSubscriberSimple: function () {
            // On the first page load check the availability of the Simple Product
            this.checkAvailabilityOfProduct();

            // On qty changes calling methods
            $(this.options.formHolder).on('change', function () {

                // Reset trigger event in case option for a product was unselected
                this.state.triggerEvent('');

                if ($(this.options.formHolder).validation('isValid')) {
                    this.replacePriceAmount();
                    this.checkAvailabilityOfProduct();
                }
            }.bind(this));
        },

        productFormSubscriberOther: function () {
            // On option and qty changes calling methods
            $(this.options.formHolder).on('change', function () {
                const inputs = $(this.options.formHolder).find(this.options.swatchHolders);
                let inputsSelected = inputs.map((index, input) => { return input.value }).get();

                // Reset trigger event in case option for a product was unselected
                this.state.triggerEvent('');

                if (!inputsSelected.includes('') && $(this.options.formHolder).validation('isValid')) {
                    let currProductPrice = this.state.totalPriceOfProduct();
                    let prevProductPrice = this.state.previousPriceOfProduct();

                    // Check if price is actually changed and set the isPriceEqualFlag accordingly
                    if (currProductPrice === prevProductPrice) {
                        this.state.isPriceEqualFlag(true);
                    } else {
                        // If price is changed then update the previousPriceOfProduct observable value
                        this.state.previousPriceOfProduct(currProductPrice);
                        this.state.isPriceEqualFlag(false);
                    }

                    // If option price is not changed avoid unnecessary script re-initialization
                    if (!this.state.isPriceEqualFlag()) {
                        this.replacePriceAmount();
                    }

                    this.checkAvailabilityOfProduct();
                }
            }.bind(this));
        },

        // Check if product is available to use/show SVEA widget
        checkAvailabilityOfProduct: async function () {
            const dataHandler = this.state.totalPriceOfProduct() > 0 ? this.state.totalPriceOfProduct() : this.productPrice;
            const URL = `${window.BASE_URL}${this.options.path}${dataHandler}`;
            const calcButtonSelector = '#svea-calc-btn';
            const calcMessageSelector = '#svea-calc-msg-info';

            try {
                await fetch(URL).then(response => {
                    if (response.ok) {
                        return response.json();
                    } else {
                        this.hidePartPaymentButton();

                        throw error(`${response.statusText}, ${response.status}`);
                    }
                }).then(data => {
                    // Fallback mechanism for enabling or disabling the SVEA Widget calculator button
                    // this.state.isDisabled(!data);

                    if (data === true && data !== undefined) {
                        /**
                         * If product price IS above part payment threshold - SVEA Widget can be used,
                         * ADD Trigger Event and set isPriceAboveThreshold observable to true
                         */
                        this.state.triggerEvent('trigger-open');
                        this.state.isPriceAboveThreshold(true);

                        this.showPartPaymentButton();
                    } else {
                        this.hidePartPaymentButton();

                        /**
                         * If product price IS NOT above part payment threshold - SVEA Widget can not be used,
                         * REMOVE Trigger Event and set isPriceAboveThreshold observable to false
                         */
                        this.state.triggerEvent('');
                        this.state.isPriceAboveThreshold(false);
                    }
                });

            } catch (e) {
                console.error(e);
            }
        },

        // Observe calculator button action and check if the SVEA widget CAN be shown, if NOT, display notification
        observeCalcBtnAction: function () {
            let self = this;

            const calcButtonSelector = '#svea-calc-btn';
            const calcMessageSelector = '#svea-calc-msg-info';
            const prodAddToCartSelector = '#product_addtocart_form';

            $(document).on('click', calcButtonSelector, function () {
                let isFormValid = $(prodAddToCartSelector).validation('isValid');

                if (isFormValid) {
                    if (self.state.isPriceAboveThreshold()) {
                        // Show notification message
                        $('body').find(calcMessageSelector).removeClass('show-notification');
                    } else {
                        // Hide notification message
                        $('body').find(calcMessageSelector).addClass('show-notification');
                        // Auto-Remove info notification message after N milliseconds (timeout)
                        setTimeout(function () {
                            $('body').find(calcMessageSelector).removeClass('show-notification');
                        }, 3200);
                    }
                }

            });
        },

        showPartPaymentButton: function () {
            $('.calculator-holder').show();
        },

        hidePartPaymentButton: function () {
            $('.calculator-holder').hide();
        },

        /**
         * Replacement/Update price in SVEA widget
         */
        replacePriceAmount: function () {
            const dataHandler = this.state.totalPriceOfProduct() > 0 ? this.state.totalPriceOfProduct() : this.productPrice;
            if (dataHandler !== undefined) {
                $('body').find('.svea-pp-widget-part-payment').attr('data-price', dataHandler);
                this.updateCalc();
            }
        },

        updateCalc: function () {
            // if SveaPartPayment exist add posibility to use update or re-initilize widget
            return window.SveaPartPayment ? window.SveaPartPayment.initializeWidgets() : false;
        }

    });
});
