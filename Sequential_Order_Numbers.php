<?php
/*
Plugin Name: WC Sequential Order Numbers
Plugin URI: http://dualcube.com
Description: Helps to have incrementing order numbers rather than random ones and find highest order id if there is some existing orders and counts the order number sequentially after highest order id.

Author: Dualcube
Version: 1.0.0
Author URI: http://dualcube.com
*/

if ( ! class_exists( 'WC_Dependencies_Sequential_Order_No' ) )
	require_once 'includes/class-dc-dependencies.php';
require_once 'includes/wc-Sequential-Order-Numbers-core-functions.php';
require_once 'config.php';
if(!defined('ABSPATH')) exit; // Exit if accessed directly
if(!defined('WC_SEQUENTIAL_ORDER_NUMBERS_PLUGIN_TOKEN')) exit;
if(!defined('WC_SEQUENTIAL_ORDER_NUMBERS_TEXT_DOMAIN')) exit;

if( !WC_Dependencies_Sequential_Order_No::woocommerce_active_check() ) {
	add_action( 'admin_notices', 'woocommerce_inactive_notice' );
}

if(!class_exists('WC_Sequential_Order_Numbers')) {
	require_once( 'classes/class-wc-Sequential-Order-Numbers.php' );
	global $WC_Sequential_Order_Numbers;
	$WC_Sequential_Order_Numbers = new WC_Sequential_Order_Numbers( __FILE__ );
	$GLOBALS['WC_Sequential_Order_Numbers'] = $WC_Sequential_Order_Numbers;
	
	register_activation_hook( __FILE__, array('WC_Sequential_Order_Numbers', 'activate_wc_sequential_order_numbers') );
	
}
?>
