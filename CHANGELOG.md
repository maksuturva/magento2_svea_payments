# Changelog

### [1.0.17] -
* Add module version string to requests and log entries.

### [1.0.16] - 2024-11-18
* Fixed migration sales script for separate payment methods.
* Fixed migration sales script for Svea service payment selection method.
* Only offline refunds available -text removed from the admin order page.
* Show message on admin page if Svea Seller ID is not set.
* Clear cached payment methods if Svea Payments settings change.

### [1.0.15] - 2024-11-14
* Enable changing amount of handling fee and handling fee tax to refund.
* Updated the migration script to also handle more unusual cases.
* Save payment method from payment verification.

### [1.0.14] - 2024-11-04
* Remove setting for generating invoice on order creation

### [1.0.13] - 2024-10-30
* Migration scripts updated
* Set the order status to cancelled when the customer cancels the payment
* Fix the amount in the product page part payment calculator to include tax

### [1.0.12] - 2024-10-23
* Create transactions for delayed capture orders when creating an invoice

### [1.0.11] - 2024-10-09
* Add setting for generating invoice on order creation

### [1.0.10] - 2024-10-08
* Handling fee tax rounding
* Invoice is not created when delayed capture is used

### [1.0.9] - 2024-10-04
* Check order status when captured order is shipped

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
