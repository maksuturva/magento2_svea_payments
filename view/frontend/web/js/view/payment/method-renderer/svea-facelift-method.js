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

    const subPMethods = {};
    subPMethods.payment_method_subgroup_1 = 'payment_method_subgroup_1';
    subPMethods.payment_method_subgroup_2 = 'payment_method_subgroup_2';
    subPMethods.payment_method_subgroup_3 = 'payment_method_subgroup_3';
    subPMethods.payment_method_subgroup_4 = 'payment_method_subgroup_4';
    subPMethods.payment_method_subgroup_5 = 'payment_method_subgroup_5';

    return Component.extend({
        defaults: {
            template: "Svea_SveaPayment/payment/form",
        },
        checkoutConfig: {},
        paymentGroup: null,
        subMethods: null,
        allMethods: null,
        redirectAfterPlaceOrder: false,
        selectedMethod: null,
        lastTotal: null,

        initialize: function () {
            let self = this;
            this._super().initObservable();
            this.allMethods = ko.observableArray([]);
            this.selectedMethod = ko.observable(null);
            this.paymentGroup = ko.observable();
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
            for(let subPMethod in subPMethods) {
                if (!this.checkoutConfig['methods'].hasOwnProperty(subPMethod)) {
                    continue;
                }
                this.allMethods.push(this.checkoutConfig['methods'][subPMethods[subPMethod]]);
                for (let method of this.checkoutConfig['methods'][subPMethods[subPMethod]]['methods']) {
                    method.identifier = this.getCode() + '_' + method.code;
                    method.paymentgroup = this.getCode() + '_' + subPMethods[subPMethod];
                    if (method.code === this.checkoutConfig['defaultPaymentMethod']) {
                        this.selectedMethod(method.code);
                    }
                }
            }
        },

        getSelectedMethod: function () {
            return this.selectSubMethod();
        },
        getPaymentGroup: function () {
            return this.paymentGroup();
        },
        getData: function () {
            return {
                "method": this.item.method,
                "extension_attributes": {
                    svea_preselected_payment_method : this.selectedMethod(),
                    svea_method_group : this.getPaymentGroup(),
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
                        customerData.invalidate(['checkout-data']);
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
        selectMethod: function (a,b,c) {
            this.paymentGroup(b.paymentgroup);
            this.selectedMethod(b.code);
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
