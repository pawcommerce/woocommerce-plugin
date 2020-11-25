=== PawCommerce for WooCommerce ===
Tags: dogecoin, woocommerce, e-commerce, payments
Requires at least: 3.7.0
Tested up to: 5.5
Stable tag: 1.0.4
License: MIT
License URI: https://github.com/pawcommerce/woocommerce-plugin/blob/master/license.txt

PawCommerce is the easiest and most secure way to accept Dogecoin,
from shibes, for shibes.

https://www.pawcommerce.com/

== Description ==

This plugin allows you to accept Dogecoin securely and trustlessly within your
WooCommerce shop, through pawcommerce.com.

== Installation ==

1. Download the latest release zip file from https://github.com/pawcommerce/woocommerce-plugin/releases
2. Upload the zip file through Plugins -> Add new -> Upload Plugin
3. Activate the plugin after installation or through the 'Plugins' menu in WordPress.
4. In the WooCommerce Settings page go to the Payment Gateways tab, then click PawCommerce
5. Check "Enable PawCommerce".
6. Enter your PawCommerce token in the Token field
7. Click "Save changes".

== Changelog ==

= 1.0.4 =
* Add invoice id/url, confirmed amount and overpayment amount in order metadata
* Add a custom column to the WooCommerce order table that shows overpayment
  amounts  

= 1.0.3 =
* Show transactions and address alias in custom meta fields
* Show # of confirmations in notes when status=paid

= 1.0.2 =
* Working IPN integration
* Make default currency description "Dogecoin"

= 1.0.1 =
* Alpha release
