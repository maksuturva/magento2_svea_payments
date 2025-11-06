define([
   'jquery',
   'ko',
   'uiComponent',
   'Magento_Checkout/js/model/quote'
], function ($, ko, Component, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            options: {
                path: 'rest/V1/partPaymentCalculator/',
                holderCalcOrigin: '.svea-script-holder'
            },
            state: {
                isDisabled: ko.observable(true),
                triggerEvent: ko.observable(''),
                isShowed: ko.observable(false),
                isPriceAboveThreshold: ko.observable(false),
                totals: quote.getTotals(),
                paymentMethod: quote.getPaymentMethod(),
                paymentMethods: window.checkoutConfig.payment
            },
            template: 'Svea_SveaPayment/calculator/calculator-holder',
        },

        initialize: function () {
            this._super();
            this.observeCalcBtnAction();
            this.subscribeAmountHandler();
            this.repositionPartPaymentCalculatorInDOM();
        },

        waitForElm: function (selector) {
        return new Promise(resolve => {
                if (document.querySelector(selector)) {
                    return resolve(document.querySelector(selector));
                }

                const observer = new MutationObserver(mutations => {
                    if (document.querySelector(selector)) {
                        observer.disconnect();
                        resolve(document.querySelector(selector));
                    }
                });

                // If you get "parameter 1 is not of type 'Node'" error, see https://stackoverflow.com/a/77855838/492336
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        },

        repositionPartPaymentCalculatorInDOM: function () {
            const calcSelector = '#svea-pp-calculator-container';
            const checkoutPosSelector = '.opc-block-summary';


            this.waitForElm(calcSelector).then((elm) => {
                this.waitForElm(checkoutPosSelector).then((elm2) => {
                    //if (this.state.isShowed()) {
                        // Reposition calculator to the div defined in calculator-layout.html
                        var calculatorElement = document.querySelector(calcSelector);
                        document.querySelector(checkoutPosSelector).appendChild(calculatorElement);
                    //}
                });
            });
        },

        subscribeAmountHandler: function () {
            // on every totals change we will update widget
            this.state.totals.subscribe(function (data) {
                if (data !== undefined) {
                    this.replacePriceAmount(data);
                    this.isCartTotalAboveThreshold(data);
                }
            }.bind(this));

            // on select payment method
            this.state.paymentMethod.subscribe(function (data) {
                this.isPaymentMethodSveaAvailable(data);
                this.isSveaBtnShow(data);
            }.bind(this));
        },

        /**
         * isPaymentMethodSveaAvailable => check if payment and payment_group have
         * isPartPaymentCalculatorAvailable attribute and check it boolean val
         *
         * @param {object} data value
         */
        isPaymentMethodSveaAvailable: function (data) {
            if (data.method.includes('svea') && data["extension_attributes"] !== undefined && data["extension_attributes"].svea_method_group !== undefined) {
                const selectedSubMethodKey = data.extension_attributes.svea_method_group.replace(`${data.method}_`,''),
                    isPartialPaymentAvailable = this.state.paymentMethods[data.method].methods[selectedSubMethodKey].isPartPaymentCalculatorAvailable;
                if (isPartialPaymentAvailable !== undefined && isPartialPaymentAvailable) {
                    this.state.isDisabled(false);
                }
                if (isPartialPaymentAvailable !== undefined && isPartialPaymentAvailable === false) {
                    this.state.isDisabled(true);
                }
            } else {
                this.state.isDisabled(true);
            }
        },

        /**
         * Check params to show if SVEA payment pack is selected
         * data - payment methods information
         *
         * @param {object} data value
         */
        isSveaBtnShow: function (data) {
            if (data.method.includes('svea')) {
                this.state.isShowed(!!data.method.includes('svea'));
            } else {
                this.state.isShowed(false);
            }
        },

        /**
         * Check if cart total is above SVEA Widget payment threshold, if is, then enable calculator button
         * data - payment methods information
         *
         * @param {object} cartData value
         */
        isCartTotalAboveThreshold: async function (cartData) {
            if (cartData !== undefined) {
                const dataHandler = cartData.grand_total;
                const URL = `${window.BASE_URL}${this.options.path}${dataHandler}/checkout_index_index`;
                const calcButtonSelector = '#svea-calc-btn';
                const calcMessageSelector = '#svea-calc-msg-info';

                try {
                    await fetch(URL).then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw error(`${response.statusText}, ${response.status}`);
                        }
                    }).then(data => {
                        // Fallback mechanism for enabling or disabling the SVEA Widget calculator button
                        // this.state.isDisabled(!data);

                        if (data === true && data !== undefined) {
                            /**
                             * If cart total IS above part payment threshold - SVEA Widget can be used,
                             * ADD Trigger Event and set isPriceAboveThreshold observable to true
                             */
                            this.state.triggerEvent('trigger-open');
                            this.state.isPriceAboveThreshold(true);
                        } else {
                            /**
                             * If cart total IS NOT above part payment threshold - SVEA Widget can not be used,
                             * REMOVE Trigger Event and set isPriceAboveThreshold observable to false
                             */
                            this.state.triggerEvent('');
                            this.state.isPriceAboveThreshold(false);
                        }
                    });

                } catch (e) {
                    console.error(e);
                }
            }
        },

        // Observe calculator button action and check if the SVEA widget CAN be shown, if NOT, display notification
        observeCalcBtnAction: function () {
            let self = this;

            const calcButtonSelector = '#svea-calc-btn';
            const calcMessageSelector = '#svea-calc-msg-info';

            $(document).on('click', calcButtonSelector, function () {
                console.log("### calculator-handle.js - observeCalcBtnAction - click")
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
            });

        },

        /**
         * Display SVEA widget
         *
         * @param {string} display the value of the display css style ("none" or "block")
         */
        displayCalculator: function (display) {
            const calcSelector = '#svea-pp-calculator-container';
            //document.querySelector(calcSelector).style["display"] = display;

            if (display) {
                document.querySelector(calcSelector).classList.remove("displayNone");
                document.querySelector(calcSelector).classList.add("displayBlock");
            } else {
                document.querySelector(calcSelector).classList.remove("displayBlock");
                document.querySelector(calcSelector).classList.add("displayNone");
            }
        },

        /**
         * Replacement/Update price in SVEA widget
         * data contain - totals data
         *
         * @param {object} data value
         */
        replacePriceAmount: function (data) {
            if (data !== undefined) {
                $('body').find('.svea-pp-widget-part-payment').attr('data-price', data.grand_total);
                this.updateCalc();
                this.displayCalculator(this.state.isShowed());
            }
        },

        updateCalc: function () {
            const widgetContainer = $('body').find('.svea-pp-widget-container');
            if (widgetContainer.length >= 1) {
                widgetContainer.each((num,obj) => {
                    if (num >= 1) {
                        widgetContainer[num].remove();
                    }
                });
                // if SveaPartPayment exist add possibility to use update or re-initialize widget
                return window.SveaPartPayment ? window.SveaPartPayment.initializeWidgets() : false;
            }
            return false;
        }

    });
});
