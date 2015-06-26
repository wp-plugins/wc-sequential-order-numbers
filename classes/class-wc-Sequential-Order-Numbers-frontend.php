<?php
class WC_Sequential_Order_Numbers_Frontend {

	public function __construct() {
		add_filter( 'woocommerce_order_number', array(&$this, 'order_number' ), 10, 2);
		
		add_filter( 'woocommerce_shortcode_order_tracking_order_id', array(&$this, 'find_order_number' ) );
	}

	public function order_number( $order_number, $order ) {
	
		$maybe_hash = $this->hash_before_order_number() ? _x( '#', 'hash before order number' ) : '';
	
		$order_number_formatted = get_post_meta( $order->id, '_order_number_formatted', true );
		if ( $order_number_formatted ) {
			return $maybe_hash . $order_number_formatted;
		}
	
		$post_status = isset( $order->post_status ) ? $order->post_status : get_post_status( $order->id );
		if ( 'auto-draft' == $post_status ) {
			global $wpdb;
			$order_number_start = get_option( 'woocommerce_order_number_start' );
			$order_number = $wpdb->get_var( $wpdb->prepare( "
				SELECT IF(MAX(CAST(meta_value AS SIGNED)) IS NULL OR MAX(CAST(meta_value AS SIGNED)) < %d, %d, MAX(CAST(meta_value AS SIGNED))+1)
				FROM {$wpdb->postmeta}
				WHERE meta_key='_order_number'",
				$order_number_start, $order_number_start ) );
			return $maybe_hash . $this->format_order_number( $order_number, $this->order_number_prefix(), $this->order_number_suffix(), $this->order_number_length() ) . ' (' . __( 'Draft', self::TEXT_DOMAIN ) . ')';
		}
		
	    return $order_number;
}

	private function skip_free_orders() {
		return 'yes' == get_option( 'woocommerce_order_number_skip_free_orders', 'no' );
	}
	
	private function hash_before_order_number() {
		return 'yes' == get_option( 'woocommerce_hash_before_order_number', 'no' );
	}
	
	public function find_order_number( $order_id ) {
		global $wpdb;
		$tablename = $wpdb->prefix.'postmeta';
		$row = $wpdb->get_row("SELECT * FROM $tablename WHERE meta_key = '_order_number_formatted' and meta_value = '$order_id'", ARRAY_A);
		return $row['post_id'];	
	}

}
