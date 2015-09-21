=== WC Sequential Order Numbers ===
Contributors: Dualcube, putulsahoo, aveek
Donate link: http://dualcube.com/
Tags: woocommerce, order number, sequential order number, woocommerce sequential order number, wc sequential order number
Requires at least: 3.6.0
Tested up to: 4.3
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A new WordPress WooCommerce plugin that helps you to have sequential order numbers rather than random ones.

== Description ==
The plugin helps you to find highest order id if there is some existing orders and counts the order number sequentially after highest order id.
The plugin helps you to count the order numbers sequentially from One(1) if there is no existing orders.

The plugin helps you to set order number manually what you desire.

The plugin helps you to add suffix, prefix, hash(#) with the order number. For WooCommerce 2.2 or older: This will display a hash (#) before order numbers on the frontend and admin. But WooCommerce 2.3 or newer from the plugin settings, as WooCommerce core automatically displays a hash for all order numbers.

You may include the current day, month, or year in your custom order number prefix or suffix and may include the current time: hour, minute, second in your custom order number prefix or suffix.

You can set a custom order number size and enable order number size including prefix and suffix. If you enable order number size including prefix and suffix the order number size will be calculated including prefix length and suffix length.

Orders with only free products can be excluded from the paid order sequence for accounting purposes, and assigned their own custom prefix.

**For more details [Click Here](http://plugins.dualcube.com/product/wc-sequential-order-numbers/)**

= Compatibility =
The plugin is fully compatible with the recent versions of WordPress and WooCommerce.

* Compatible with WordPress versions 3.6.0 or later
* Compatible with WooCommerce 2.2.x or later
* Multilingual Support is included with the plugin and is fully compatible with WPML.

= Upcoming Features =
In the upcoming version we will be adding a “performance mode” for shops with an extremely large number of orders.

= Feedback =
All we want is some love. If you did not like this plugin or if it is buggy, please give us a shout and we will be happy to fix the issue add the feature. If you indeed liked it, please leave a 5/5 rating.  
In case you feel compelled to rate this plugin less than 5 stars - please do mention the reason and we will add or change options and fix bugs. It's very unpleasant to see silent low rates. For more information and instructions on this plugin please visit www.dualcube.com.


== Installation ==
1. Upload the `wc-sequential-order-numbers` folder to the `/wp-content/plugins/` directory OR search for "WooCommerce Sequential Order Numbers Pro" from your WordPress admin.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Update your Order Numbers under WooCommerce Settings > General and save.
4. Sit back and watch the magic.


== Screenshots ==
1. WC Sequential Order Numbers setting panel.
2. An order number when prefix->DC and suffix->ES and start from 103.
3. Some orders with sequencial order number.
4. Admin can search order using sequencial order number.
5. Buyer can track his order status using sequencial order id.
6. Sample order number when size is 8 including prefix.
7. Sample order number when size is 8 including prefix and suffix.
8. Woocommerce my account page.
9. A sample order from front-end.


== Frequently Asked Questions ==
= What is skip free order? =
With this enabled, orders with only free products and no additional fees or costs will be excluded from the paid order sequence. Useful when required by certain accounting rules.

= What is FREE IDENTIFIER? =
This option is only available when the Skip Free Orders option is enabled. This allows you to set a prefix for the free orders numbering sequence so you can have for instance: FREE-1, FREE-2, FREE-3, etc.

= Should I use Hash(#) for new version of WooCommerce? =
No, WooCommerce 2.3 or newer from the plugin settings, as WooCommerce core automatically displays a hash for all order numbers. 


== Changelog ==
= 1.1.3 =
*   Fix - Minor bug fixed, now admin can make an order status "Complete" from dashboard.

= 1.1.2 =
*   Fix - Minor bug fixed to make it compatible with WooCommerce version 2.4+

= 1.1.1 =
*   Minor bug fixed with "WooCommerce Free Order"
*   Now admin can create order from backend with "Sequencial Number"

= 1.1.0 =
*   Admin can set custom order number size with prefix and suffix
*   Customer can track their order by order number

= 1.0.2 =
*   Minor bug fix

= 1.0.1 =
*   Minor bug fixed and compatible with latest WooCommerce

= 1.0.0 =
*   Initial release.

== Upgrade Notice ==
= 1.1.3 =
*   Fix - Minor bug fixed, now admin can make an order status "Complete" from dashboard.

= 1.1.2 =
*   Fix - Minor bug fixed to make it compatible with WooCommerce version 2.4+

= 1.1.1 =
*   Minor bug fixed with "WooCommerce Free Order"
*   Now admin can create order from backend with "Sequencial Number"

= 1.1.0 =
*   Admin can set custom order number size with prefix and suffix
*   Customer can track their order by order number

= 1.0.2 =
*   Minor bug fix

= 1.0.1 =
*   Minor bug fixed and compatible with latest WooCommerce

= 1.0.0 =
*   Initial release
