# Changelog

### [1.4.3] - 2025-12-10
* Make order status check buttons and cron always use the correct seller ID.
* Store settlement reference (pmt_reference in Svea API) in the payments table in database and display it in admin order view.

### [1.4.2] - 2025-11-13
* New layout options for Part Payment calculator, button(old layout), mini, and full.
* Delivery reporting of strictly virtual orders, orders with only virtual products will be reported to Svea as delivered when payment completes.
* Layout compatibiliy improvements with Mageplaza One Step Checkout extension.
* Bugfix: Remove browser console warning related to unused svea_payment variable.

### [1.4.1] - 2025-10-22
* Fix handling of multiple Credit Memos on one order. The correct product will now be reported to Svea.

### [1.4.0] - 2025-09-24
* Fixed jQuery error on the checkout page when using Redirect to Svea Payments method. 
* Svea Payment API changed to use gross values instead of net prices, this will reduce occurances of rounding rows on Svea Extranet.
* Reworked totals and discount calculations to give correct row sums and total, what you pay in Svea is the same as what is shown in Magento.

### [1.3.1] - 2025-08-28
* Fixed duplicate collated payment method titles on the checkout page.
* Added option to use custom API endpoint url for communication.

### [1.3.0] - 2025-06-18
* Corrected discount rows sent to Svea to include tax.
* Corrected discount handling to follow Magento tax settings, the calculations are now including tax correct values for discounts for the following settings. 
  * Catalog Prices:           Including/Excluding Tax
  * Apply Customer Tax:       Before/After Discount
  * Apply Discount On Prices: Including/Excluding tax
* Note that Magento warns on some combinations (Catalog Excluding Tax, Tax After Discount, Discount Including Tax) as they **do not** produce valid discount calculations, check that results match what you expect.

### [1.2.1] - 2025-04-29
* Fixed labeling in payment icon forms

### [1.2.0] - 2025-04-29
* Complete overhaul of Credit Memo handling for partial refunds.
  * When returning an item it is now reflected in Svea Extranet
  * Item VAT is correctly accounted for in information to customer
  * Refunding shipping and handling costs appear as additional rows in Extranet

### [1.1.2] - 2025-04-02
* Revert miniumum required PHP version to 8.1.
* Improve compatibility with Magento Cloud Pro, or other hosting that uses database clustering.

### [1.1.1] - 2025-03-27
* The module now works with PHP 8.4
* Better handling of null fields on old orders.

### [1.1.0] - 2025-03-17
* Set Payment Pending on order when redirecting to payment page. Magento will automatically cancel abandoned orders after set timeout, default 8 hours.
* Uniform handling of return urls

### [1.0.18] - 2024-12-12
* Fixed digital only products were not purchaseable because of missing shipping address.

### [1.0.17] - 2024-11-27
* Better shipping notification text for custom delivery type
* Fix shipping notification for customized products
* Update tooltips to match actual defaults.
* Add missing translation of Invoicing fee tax.
* Stop removing leading zeroes on order number.
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
