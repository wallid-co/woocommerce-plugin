=== Wallid Pay By Bank for WooCommerce ===
Contributors: Wallid
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 1.1.9
Stable tag: 1.1.9
Tags: woocommerce, open banking, pay by bank, checkout, payments
License: GPLv2 or later

Accept Open Banking payments in WooCommerce with fast bank-to-bank checkout.

== Description ==

Wallid enables merchants to accept account-to-account payments in WooCommerce using Open Banking. Customers pay directly from their bank account to the merchant's bank account, without using debit or credit cards.

Once the Wallid payment method is enabled at checkout, payments are authorised within the customer's own banking app and completed via the Faster Payments network.

Key features:
- Instant settlement: payments are transferred directly to the merchant's bank account, typically in real time, without payout delays.
- Transparent pricing: 1% + 25p payment fees with discounts for high volume.
- Secure bank authorisation: customers authenticate payments using their bank's existing security mechanisms. No card details are collected or stored.
- WooCommerce-native integration: integrates directly with WooCommerce checkout and order management, requiring no custom development.

Wallid Merchant Portal:

Merchants can manage and monitor their payments through the Wallid Merchant Portal.

- View detailed information for WooCommerce payments
- Manage bank account details used for settlement
- Initiate instant refunds
- Get reconciliation reports

About Wallid:

Wallid is an Open Banking-based payment platform that provides an alternative to card payments. It allows businesses to receive payments directly into their bank account via online checkouts.
Wallid operates using regulated Open Banking infrastructure and is designed to support secure, direct bank-to-bank payments for UK & EU merchants.

Getting started requirements:

- WordPress 5.8 or newer
- WooCommerce 6.6 or newer
- PHP version 7.0 or newer (7.2+ recommended)

== Frequently Asked Questions ==

= Where do I get my Wallid credentials? =

Create a merchant account at https://www.getwallid.co.uk, complete verification, then request activation from Wallid support. Once approved, you will receive your terminal credentials.

= Does this plugin support refunds? =

Instant refunds are managed through the Wallid Merchant Portal.

= Which countries are supported? =

Wallid supports UK and EU merchants. Availability depends on your account setup and verification status.

== Screenshots ==

1. Wallid payment method shown during WooCommerce checkout.
2. Wallid settings page in WooCommerce admin.
3. Merchant payment flow after redirect to Wallid.
4. Order notes showing webhook status updates.

== Changelog ==

= 1.1.9 =
* General fixes and stability improvements.

= 1.1.8 =
* Initial public package improvements for Open Banking checkout flow.

== Upgrade Notice ==

= 1.1.9 =
General stability improvements and better checkout experience.
