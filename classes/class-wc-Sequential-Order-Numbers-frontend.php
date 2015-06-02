<?php
class WC_Sequential_Order_Numbers_Frontend {

	public function __construct() {
		add_filter( 'woocommerce_order_number', array(&$this, 'get_order_number' ), 10, 2);
	}

	public function get_order_number( $order_number, $order ) {
	
		// maintain the hash?
		$maybe_hash = $this->get_has_hash_before_order_number() ? _x( '#', 'hash before order number' ) : '';
		
		// can't trust $order->order_custom_fields object
		$order_number_formatted = get_post_meta( $order->id, '_order_number_formatted', true );
		if ( $order_number_formatted ) {
			return $maybe_hash . $order_number_formatted;
		}
	
		// return a 'draft' order number that will not be saved to the db (this
		//  means that when adding an order from the admin, the order number you
		//  first see may not be the one you end up with, but it's better than the
		//  alternative of showing the underlying post id)
		$post_status = isset( $order->post_status ) ? $order->post_status : get_post_status( $order->id );
		if ( 'auto-draft' == $post_status ) {
			global $wpdb;
			$order_number_start = get_option( 'woocommerce_order_number_start' );
			$order_number = $wpdb->get_var( $wpdb->prepare( "
				SELECT IF(MAX(CAST(meta_value AS SIGNED)) IS NULL OR MAX(CAST(meta_value AS SIGNED)) < %d, %d, MAX(CAST(meta_value AS SIGNED))+1)
				FROM {$wpdb->postmeta}
				WHERE meta_key='_order_number'",
				$order_number_start, $order_number_start ) );
			return $maybe_hash . $this->format_order_number( $order_number, $this->get_order_number_prefix(), $this->get_order_number_suffix(), $this->get_order_number_length() ) . ' (' . __( 'Draft', self::TEXT_DOMAIN ) . ')';
		}
		return $order_number;
	}

	private function skip_free_orders() {
		return 'yes' == get_option( 'woocommerce_order_number_skip_free_orders', 'no' );
	}
	
	private function get_has_hash_before_order_number() {
		return 'yes' == get_option( 'woocommerce_hash_before_order_number', 'no' );
	}

}
