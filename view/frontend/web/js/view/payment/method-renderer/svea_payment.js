/*browser:true*/
/*global define*/
define([
    'jquery',
    'ko',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/action/set-payment-information',
    'Magento_Checkout/js/action/select-payment-method',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/place-order',
    'Svea_SveaPayment/js/action/fetch-payment-methods'
], function (
    $,
    ko,
    Component,
    setPaymentInformation,
    selectPaymentMethodAction,
    additionalValidators,
    quote,
    checkoutData,
    customerData,
    customer,
    errorProcessor,
    fullScreenLoader,
    placeOrderAction,
    fetchPaymentMethods
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: "Svea_SveaPayment/payment/form",
        },
        checkoutConfig: {},
        subMethods: null,
        redirectAfterPlaceOrder: false,
        selectedMethod: null,
        lastTotal: null,

        initialize: function () {
            this._super();
            this.subMethods = ko.observable([]);
            this.selectedMethod =  ko.observable(null);
            this.preparePaymentHook();
            this.prepareQuoteTotalHook();
            this.checkoutConfig = window.checkoutConfig.payment[this.getCode()];
            if (this.checkoutConfig) {
                if (this.checkoutConfig['template']) {
                    this.template = this.checkoutConfig['template'];
				}
                this.prepareMethods();
            }
        },

        preparePaymentHook: function () {
            this.selectedMethod.subscribe(function (value) {
                if (this.isOpen()) {
                    this.updateTotals();
                }
            }, this);
        },

        prepareQuoteTotalHook: function () {
            quote.totals.subscribe(function (totals) {
                let total = this.getQuoteGrandTotal();
                if (this.isOpen() && this.lastTotal != total) {
                    this.fetchMethods();
                }
                this.lastTotal = total;
            }, this);
        },

        fetchMethods: function () {
            fetchPaymentMethods(function (data) {
                this.subMethods(data);
            }.bind(this));
        },

        prepareMethods: function () {
           // if (this.checkoutConfig['defaultPaymentMethod']) {
                for (let method of this.checkoutConfig['methods']) {
                    method.identifier = this.getCode() + '_' + method.code;
                    if (method.code === this.checkoutConfig['defaultPaymentMethod']) {
                        this.selectedMethod(method.code);
                    }
                }
           // }
            this.subMethods(this.checkoutConfig['methods']);
        },

        getData: function () {
            return {
                "method": this.item.method,
                "extension_attributes": {
                    svea_preselected_payment_method : this.selectedMethod(),
                }
            };
        },

        getQuoteGrandTotal: function () {
            let totals = quote.getTotals()();
            if (totals) {
                return totals['grand_total'];
            }
            return quote['grand_total'];
        },

        isOpen: function () {
            return this.getCode() === this.isChecked();
        },

        isOrderButtonActive: function () {
            return this.isPlaceOrderActionAllowed() && this.hasValidPreselectedMethod();
        },

        hasValidPreselectedMethod: function () {
            return !this.isPreselectRequired() || this.selectedMethod() != null;
        },

        placeOrder: function (data, event) {
            if (event) {
                event.preventDefault();
            }

            let self = this,
                placeOrder,
                emailValidationResult = customer.isLoggedIn(),
                loginFormSelector = 'form[data-role=email-with-possible-login]';
            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
            }

            if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);

                $.when(placeOrder).fail(function () {
                    self.isPlaceOrderActionAllowed(true);
                }).done(this.afterPlaceOrder.bind(this));

                return true;
            }

            return false;
        },

        afterPlaceOrder: function () {
            let self = this;

            if (typeof this.checkoutConfig.paymentDataUrl === "undefined" || this.checkoutConfig.paymentDataUrl === null) {
                console.error('Payment data URL is undefined')
            }

            customerData.invalidate(['cart']);

            $.post(this.checkoutConfig.paymentDataUrl)
                .done(function (response) {
                    window.location.replace(response.redirectUrl);
                }).fail(function (response) {
                    errorProcessor.process(response, self.messageContainer);
                    fullScreenLoader.stopLoader();
                }
            );
        },

        validate: function () {
            if (this.isPreselectRequired() && !this.selectedMethod()) {
                return false;
            }
            let form = `form[data-role=${this.getCode()}-form]`;

            return $(form).validation() && $(form).validation('isValid');
        },

        selectSubMethod: function (data, event) {
            this.selectedMethod(event.target.value);
            return true;
        },

        isPreselectRequired: function () {
            return this.checkoutConfig['preselectRequired'];
        },

        updateTotals: function () {
            selectPaymentMethodAction(this.getData());
        },

        getMethodData: function (value) {
            let data = this.checkoutConfig['methodData'];
            if (value) {
                data = data[value];
            }

            return data;
        },

        getTermsUrl: function () {
            return this.getMethodData('termsurl');
        },

        getTermsText: function () {
            return this.getMethodData('termstext');
        },
    });
});
