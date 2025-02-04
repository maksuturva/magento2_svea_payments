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
                formHolder: 'product_addtocart_form',
                swatchHolders: 'swatch-input'
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

        productConfigured: function () {
            return $('#'+this.options.formHolder).validation('isValid');
        },

        productFormSubscriberSimple: function () {
            // On the first page load check the availability of the Simple Product
            this.checkAvailabilityOfProduct();

            // On qty changes calling methods
            document.getElementById(this.options.formHolder).onchange = function () {

                // Reset trigger event in case option for a product was unselected
                this.state.triggerEvent('');

                if (this.productConfigured()) {
                    this.replacePriceAmount();
                    this.checkAvailabilityOfProduct();
                }
            }.bind(this);
        },

        productFormSubscriberOther: function () {
            // On option and qty changes calling methods
            document.getElementById(this.options.formHolder).onchange = function () {
                const inputs = Array.from(document.getElementById(this.options.formHolder).getElementsByClassName(this.options.swatchHolders));
                let inputsSelected = inputs.map(input => { return input.value });

                // Reset trigger event in case option for a product was unselected
                this.state.triggerEvent('');

                if (!inputsSelected.includes('') && this.productConfigured()) {
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
            }.bind(this);
        },

        // Check if product is available to use/show SVEA widget
        checkAvailabilityOfProduct: async function () {
            const dataHandler = this.state.totalPriceOfProduct() > 0 ? this.state.totalPriceOfProduct() : this.productPrice;
            const URL = `${window.BASE_URL}${this.options.path}${dataHandler}/catalog_product_view`;

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

            const calcButtonSelector = 'svea-calc-btn';
            const calcMessageSelector = 'svea-calc-msg-info';

            document.getElementById(calcButtonSelector).onclick = function () {
                let isFormValid = self.productConfigured();

                if (isFormValid) {
                    if (self.state.isPriceAboveThreshold()) {
                        // Show notification message
                        document.getElementById(calcMessageSelector).classList.remove('show-notification');
                    } else {
                        // Hide notification message
                        document.getElementById(calcMessageSelector).classList.add('show-notification');
                        // Auto-Remove info notification message after N milliseconds (timeout)
                        setTimeout(function () {
                            document.getElementById(calcMessageSelector).classList.remove('show-notification');
                        }, 3200);
                    }
                }
            };
        },

        showPartPaymentButton: function () {
            const elements = document.getElementsByClassName('calculator-holder');
            Array.from(elements).forEach(function (element) {
                element.style.display = 'inline-block';
            });
        },

        hidePartPaymentButton: function () {
            const elements = document.getElementsByClassName('calculator-holder');
            Array.from(elements).forEach(function (element) {
                element.style.display = 'none';
            });
        },

        /**
         * Replacement/Update price in SVEA widget
         */
        replacePriceAmount: function () {
            const dataHandler = this.state.totalPriceOfProduct() > 0 ? this.state.totalPriceOfProduct() : this.productPrice;
            if (dataHandler !== undefined) {
                const elements = document.getElementsByClassName('svea-pp-widget-part-payment');
                Array.from(elements).forEach(function (element) {
                    element.setAttribute('data-price', dataHandler);
                });
                this.updateCalc();
            }
        },

        updateCalc: function () {
            // if SveaPartPayment exist add posibility to use update or re-initilize widget
            return window.SveaPartPayment ? window.SveaPartPayment.initializeWidgets() : false;
        }

    });
});
