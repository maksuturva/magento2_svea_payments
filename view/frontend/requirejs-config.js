var config = {
    map: {
        '*': {
            'Magento_Checkout/js/action/select-payment-method': 'Svea_SveaPayment/js/action/select-payment-method',
        }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/price-box': {
                'Svea_SveaPayment/js/mixins/price-box-mixin': true
            }
        }
    }
};
