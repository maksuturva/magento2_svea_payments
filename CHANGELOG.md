# Changelog

### [1.0.8] - 2024-10-04
* Fix delivery info sending when using delayed capture

### [1.0.7] - 2024-09-25
* Remove non existent file include
* Hide empty fees in checkout
* Remove default handling fee
  * Default fee is removed when migrating from the old module. Please make sure you have set up invoicing fees per payment method, since this default is no longer applied anywhere.

### [1.0.6] - 2024-09-17
* Limit payment methods available for invoicing fees
* Sales migration legacy support for the payment method code

### [1.0.5] - 2024-08-14
* Prevent legacy data migration

### [1.0.4] - 2024-08-07
* Bump composer version

### [1.0.3] - 2024-08-02
* Prevent loss of precision

### [1.0.2] - 2024-06-20
* Handling fee tax for payable payment methods
* Hide Svea button if part payment is not available

### [1.0.1] - 2024-05-27
* Remove migration button from admin

### [1.0.0] - 2024-03-05
* Initial release
