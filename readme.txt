=== WooCommerce Sequential Order Numbers Pro ===
Contributors: Dualcube, putulsahoo, aveek
Donate link: http://dualcube.com/
Tags: woocommerce, order number, sequential order number, woocommerce sequential order number, wc sequential order number
Requires at least: 3.6.0
Tested up to: 4.2.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A new Wordpress Woocommerce plugin that helps you to have sequential order numbers rather than random ones.

== Description ==

The plugin helps you to find highest order id if there is some existing orders and counts the order number sequentially after highest order id.
The plugin helps you to count the order numbers sequentially from zero if there is no existing orders.

The plugin helps you to set order number manually what you desire.

The plugin helps you to add suffix, prefix, hash(#) with the order number. For WooCommerce 2.2 or older: This will display a hash (#) before order numbers on the frontend and admin. But WooCommerce 2.3 or newer from the plugin settings, as WooCommerce core automatically displays a hash for all order numbers.

You may include the current day, month, or year in your custom order number prefix or suffix and may include the current time: hour, minute, second in your custom order number prefix or suffix.

Order number length can be set, automatically adding as many zeroes to the beginning of the order number as needed.

Orders with only free products can be excluded from the paid order sequence for accounting purposes, and assigned their own custom prefix.


= Compatibility =

The plugin is fully compatible with the recent versions of Wordpress and Woocommerce.

* Compatible with Wordpress versions 3.6.0 or later
* Compatible with Woocommerce 2.2.x or later
* Multilingual Support is included with the plugin and is fully compatible with WPML.

** For more details [Click Here](http://plugins.dualcube.com/product/woocommerce-sequential-order-numbers-pro/)**

= Upcoming Features =

In the upcoming version we will be adding a “performance mode” for shops with an extremely large number of orders.


= Feedback =

All we want is some love. If you did not like this plugin or if it is buggy, please give us a shout and we will be happy to fix the issue add the feature. If you indeed liked it, please leave a 5/5 rating.  
In case you feel compelled to rate this plugin less than 5 stars - please do mention the reason and we will add or change options and fix bugs. It's very unpleasant to see silent low rates. For more information and instructions on this plugin please visit www.dualcube.com.


== Installation ==

1. Upload the `wc-sequential-order-numbers` folder to the `/wp-content/plugins/` directory OR search for "Woocommerce Sequential Order Numbers Pro" from your Wordpress admin.
2. Activate the plugin through the 'Plugins' menu in Wordpress.
3. Update your Order Numbers under WooCommerce Settings > General and save.
4. Sit back and watch the magic.


== Screenshots ==

1. Woocommerce setting panel.
2. Sequentially incrementing order number with suffix and prefix in woocommerce order panel.
3. Sequentially incrementing order number with suffix and prefix in fronend page.


== Frequently Asked Questions ==

= What is skip free order? =
With this enabled, orders with only free products and no additional fees or costs will be excluded from the paid order sequence. Useful when required by certain accounting rules.

= What is FREE IDENTIFIER? =
This option is only available when the Skip Free Orders option is enabled. This allows you to set a prefix for the free orders numbering sequence so you can have for instance: FREE-1, FREE-2, FREE-3, etc.

= Should I use Hash(#) for new version of woocommerce? =
No,WooCommerce 2.3 or newer from the plugin settings, as WooCommerce core automatically displays a hash for all order numbers. 


== Changelog ==

= 1.0.0 =
*   Initial release.

== Upgrade Notice ==

= 1.0.0 =
*   Initial release
