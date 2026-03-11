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

    const PAYMENT_METHOD_GROUPS_PAY_NOW = [
        'payment_method_subgroup_1',
        'payment_method_subgroup_2',
        'payment_method_subgroup_3'
    ];

    const PAYMENT_METHOD_GROUPS_PAY_LATER = [
        'payment_method_subgroup_4'
    ];

    const PAYMENT_METHOD_GROUPS_ALL = [...PAYMENT_METHOD_GROUPS_PAY_NOW, ...PAYMENT_METHOD_GROUPS_PAY_LATER];

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
            // Observable to track which radio is selected
            this.selectionPayNowOrLater = ko.observable('');
            
            // Auto-select logic on initialization
            this.autoSelectPaymentMethod();
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
            PAYMENT_METHOD_GROUPS_ALL.forEach(function (group) {

                if (!this.checkoutConfig['methods'].hasOwnProperty(group)) {
                    return;
                }

                this.allMethods.push(this.checkoutConfig['methods'][group]);

                for (let method of this.checkoutConfig['methods'][group]['methods']) {

                    method.identifier = this.getCode() + '_' + method.code;
                    method.paymentgroup = this.getCode() + '_' + group;

                    if (method.code === this.checkoutConfig['defaultPaymentMethod']) {
                        this.selectedMethod(method.code);
                    }
                }

            }.bind(this));
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

        /** Facelift payment options */

        autoSelectPaymentMethod: function () {
            var payNowAvailable = this.paymentSubMethodPayNowAvailable();
            var payLaterAvailable = this.paymentSubMethodPayLaterAvailable();

            // Only select if exactly one of them is available
            if (payNowAvailable && !payLaterAvailable) {
                this.showPaymentMethods('payNow');
                this.setSelected('payNow'); // updates selectionPayNowOrLater
            } else if (payLaterAvailable && !payNowAvailable) {
                this.showPaymentMethods('payLater');
                this.setSelected('payLater'); // updates selectionPayNowOrLater
            }
        },

        // Helper for CSS binding
        isSelected: function (option) {
            return this.selectionPayNowOrLater() === option;
        },

        setSelected: function (option) {
            this.selectionPayNowOrLater(option); // update observable
        },

        showPaymentMethods: function (selection) {
            // hide all
            var nodes = document.querySelectorAll(".checkout__radio-container");
            nodes.forEach(node => {
                if (node && !node.classList.contains('none')) {
                    node.classList.add('none');
                }
            });

            // show selected
            var groups = selection === 'payNow' ? PAYMENT_METHOD_GROUPS_PAY_NOW : PAYMENT_METHOD_GROUPS_PAY_LATER;

            groups.forEach(function (group) {
                var node = document.querySelector("#" + group);
                if (node) {
                    node.classList.remove('none');
                }
            });

            this.showPaymentSubMethods();
        },

        showPaymentSubMethods: function (selectedId) {

            if (!selectedId) {
                return;
            }

            // Remove leading "#" if present
            selectedId = selectedId.replace(/^#/, '');

            // Determine which group set the selectedId belongs to
            let groupSet = null;

            if (PAYMENT_METHOD_GROUPS_PAY_NOW.includes(selectedId)) {
                groupSet = PAYMENT_METHOD_GROUPS_PAY_NOW;
            } else if (PAYMENT_METHOD_GROUPS_PAY_LATER.includes(selectedId)) {
                groupSet = PAYMENT_METHOD_GROUPS_PAY_LATER;
            }

            if (!groupSet) {
                return;
            }

            document.querySelectorAll('.checkout__radio-container').forEach(parent => {

                // Only process parents belonging to the same group set
                if (!groupSet.includes(parent.id)) {
                    return;
                }

                const chButtonSet = parent.querySelectorAll('.payment__button-set');

                chButtonSet.forEach(child => {
                    if (parent.id === selectedId) {
                        // toggle 'none' on matching parent
                        child.classList.toggle('none');
                    } else {
                        // ensure 'none' is added for non-matching parents
                        child.classList.add('none');
                    }
                });

                const chHeaderIcons = parent.querySelectorAll('.payment__button-header-icons');

                chHeaderIcons.forEach(child => {
                    if (parent.id === selectedId) {
                        // toggle 'none' on matching parent
                        child.classList.toggle('none');
                    } else {
                        // ensure 'none' is removed from non-matching parents
                        child.classList.remove('none');
                    }
                });

            });
        },

        paymentSubMethodPayNowAvailable: function () {
            return this.paymentSubMethodsAvailable.apply(this, PAYMENT_METHOD_GROUPS_PAY_NOW);
        },

        paymentSubMethodPayLaterAvailable: function () {
            return this.paymentSubMethodsAvailable.apply(this, PAYMENT_METHOD_GROUPS_PAY_LATER);
        },

        // Checks if payment SubMethods are available for codes mathing those given as parameter(s)
        // Parameter(s): code (for example: payment_method_subgroup_1)
        // Usage: paymentSubMethodsAvailable('payment_method_subgroup_1','payment_method_subgroup_2');
        paymentSubMethodsAvailable: function () {
            var codes = Array.prototype.slice.call(arguments);
            var groups = this.allMethods();
            var map = {};

            groups.forEach(function (smethod) {
                map[smethod.code] = ko.unwrap(smethod.methods);
            });

            return codes.some(function (code) {
                var methods = map[code];
                return methods && methods.length > 0;
            });
        },

        // Checks if ONLY those payment SubMethods are available that match codes given as parameter(s)
        // Parameter(s): code (for example: payment_method_subgroup_1)
        // Usage: paymentSubMethodsOnlyAvailable('payment_method_subgroup_1','payment_method_subgroup_2');
        paymentSubMethodsOnlyAvailable: function () {
            var codes = Array.prototype.slice.call(arguments);
            var groups = this.allMethods();
            var map = {};

            console.log(codes);

            groups.forEach(function (smethod) {
                map[smethod.code] = ko.unwrap(smethod.methods);
            });

            return groups.every(function (smethod) {
                var methods = map[smethod.code] || [];
                var hasMethods = methods.length > 0;

                if (codes.indexOf(smethod.code) !== -1) {
                    // codes we care about must have methods
                    return hasMethods;
                }

                // all other groups must NOT have methods
                return !hasMethods;
            });
        },

        // Checks if ONLY those payment SubMethods are available that match codes given as parameter(s),
        // Checks based on the payment group set (is payment the only one available in its group set)
        // Parameter(s): code (for example: payment_method_subgroup_1)
        // Usage: paymentSubMethodsOnlyAvailablePerGroup('payment_method_subgroup_1','payment_method_subgroup_2');
        paymentSubMethodsOnlyAvailablePerGroup: function () {
            var codes = Array.prototype.slice.call(arguments);
            var groups = this.allMethods();

            var sets = [
                PAYMENT_METHOD_GROUPS_PAY_NOW,
                PAYMENT_METHOD_GROUPS_PAY_LATER
            ];

            return sets.some(function (set) {

                var active = groups
                    .filter(function (smethod) {
                        return set.indexOf(smethod.code) !== -1;
                    })
                    .filter(function (smethod) {
                        var methods = ko.unwrap(smethod.methods) || [];
                        return methods.length > 0;
                    })
                    .map(function (smethod) {
                        return smethod.code;
                    });

                if (active.length === 0) {
                    return false;
                }

                return active.every(function (code) {
                    return codes.indexOf(code) !== -1;
                }) && codes.every(function (code) {
                    return set.indexOf(code) === -1 || active.indexOf(code) !== -1;
                });
            });
        },

        getTopImages: function (smethod, priority) {
            if (!smethod || !smethod.methods || !Array.isArray(priority)) {
                return [];
            }

            return priority.map(function (code) {
                return smethod.methods.find(function (m) {
                    return m.code === code;
                });
            }).filter(Boolean);
        },

        getImageUrl: function (smethod, imgFileName) {
            var methods = ko.unwrap(smethod.methods);

            if (methods && methods.length) {
                var originalUrl = ko.unwrap(methods[0].imageurl);

                if (originalUrl) {
                    // Remove filename and keep folder path
                    var basePath = originalUrl.substring(0, originalUrl.lastIndexOf('/') + 1);

                    return basePath + imgFileName;
                }
            }

            return '';
        }
    });
});
