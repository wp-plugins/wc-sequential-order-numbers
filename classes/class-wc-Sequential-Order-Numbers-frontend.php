<?php
class WC_Sequential_Order_Numbers_Frontend {

	public function __construct() {
		
		add_action( 'woocommerce_checkout_update_order_meta', array(&$this, 'create_sequential_order_number' ), 10, 2 );
		add_action( 'woocommerce_process_shop_order_meta', array(&$this, 'create_sequential_order_number' ), 15, 2 );
		add_action( 'woocommerce_before_resend_order_emails', array(&$this, 'create_sequential_order_number' ), 10, 1 );
		
		add_filter( 'woocommerce_order_number', array(&$this, 'get_order_number' ), 10, 2);
		
		add_filter( 'woocommerce_shortcode_order_tracking_order_id', array(&$this, 'find_sequencial_order_id_by_order_number' ) );
		
		add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array(&$this, 'subscriptions_renewal_order_meta' ), 10, 4 );
		add_action( 'woocommerce_subscriptions_renewal_order_created', array(&$this, 'subscriptions_sequential_order_number' ), 9, 4 );
	}
	
	public function create_sequential_order_number( $post_id, $post = array() ) {
		if( is_array( $post ) || ( 'shop_order' == $post->post_type && 'auto-draft' != $post->post_status ) ) {
			$post_id = is_a( $post, 'WC_Order' ) ? $post->id : $post_id;
			$order_number = get_post_meta( $post_id, '_order_number' );
			if ( empty( $order_number ) ) {
				if ( $this->skip_free_orders() && $this->is_free_order( $post_id ) ) {
					if ( $this->generate_sequential_order_number( $post_id, '_order_number_free', $this->free_order_number_start(), $this->free_order_number_prefix() ) ) {
						update_post_meta( $post_id, '_order_number', -1 );
					}
				} else {
					$this->generate_sequential_order_number( $post_id, '_order_number', get_option( 'woocommerce_order_number_start' ), $this->order_number_prefix(), $this->order_number_suffix(), $this->order_number_size() );
				}
			}
		}
  }
  
	private function is_free_order( $order_id ) {
		$is_free = true;
		$order = new WC_Order( $order_id );
		if ( $order->get_total() > 0 ) {
			$is_free = false;
		}
		return $is_free;
	}
	
	private function generate_sequential_order_number( $post_id, $order_number_meta_name, $order_number_start, $order_number_prefix = '', $order_number_suffix = '', $order_number_size = 1 ) {
		global $wpdb;
		$success = false;
		if (!isset($order_number_start)) {
			$order_number_start  = get_post_meta( $post_id, '_order_number_meta' );
		}
		for ( $i = 0; $i < 3 && ! $success; $i++ ) {
			$query = $wpdb->prepare( "
				INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
				SELECT %d,'{$order_number_meta_name}',IF(MAX(CAST(meta_value AS SIGNED)) IS NULL OR MAX(CAST(meta_value AS SIGNED)) < %d, %d, MAX(CAST(meta_value AS SIGNED))+1)
					FROM {$wpdb->postmeta}
					WHERE meta_key='{$order_number_meta_name}'",
				$post_id, $order_number_start, $order_number_start );
			$success = $wpdb->query( $query );
			

			if ( $success ) {
				$order_number = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_id = %d", $wpdb->insert_id ) );
				update_post_meta( $post_id, '_order_number_formatted', $this->format_order_number( $order_number, $order_number_prefix, $order_number_suffix, $order_number_size ) );
				$order_number_meta = array(
					'prefix' => $order_number_prefix,
					'suffix' => $order_number_suffix,
					'size' => $order_number_size
				);
				update_post_meta( $post_id, '_order_number_meta', $order_number_meta );
			}
		}
		return $success;
	}
	
	private function format_order_number( $order_number, $order_number_prefix = '', $order_number_suffix = '', $order_number_size = 1 ) {
		$order_number = (int) $order_number;
		if ( $order_number_size && ctype_digit( $order_number_size ) ) {
			$order_number = sprintf( "%0{$order_number_size}d", $order_number );
		}
		$formatted = $order_number_prefix . $order_number . $order_number_suffix;
		$replacements = array(
			'{D}'    => date_i18n( 'j' ),
			'{DD}'   => date_i18n( 'd' ),
			'{M}'    => date_i18n( 'n' ),
			'{MM}'   => date_i18n( 'm' ),
			'{YY}'   => date_i18n( 'y' ),
			'{YYYY}' => date_i18n( 'Y' ),
			'{H}'    => date_i18n( 'G' ),
			'{HH}'   => date_i18n( 'H' ),
			'{N}'    => date_i18n( 'i' ),
			'{S}'    => date_i18n( 's' )
		);
		return str_replace( array_keys( $replacements ), $replacements, $formatted );
	}
	
	
	
	private function free_order_number_start() {
		return get_option( 'woocommerce_free_order_number_start' );
	}
	
	private function free_order_number_prefix() {
		return get_option( 'woocommerce_free_order_number_prefix', __( 'FREE-' ) );
	}
	
	private function order_number_prefix() {
		if ( ! isset( $this->order_number_prefix ) )
			$this->order_number_prefix = get_option( 'woocommerce_order_number_prefix', "" );
		return $this->order_number_prefix;
	}
	
	private function order_number_suffix() {
		if ( ! isset( $this->order_number_suffix ) )
			$this->order_number_suffix = get_option( 'woocommerce_order_number_suffix', "" );
		return $this->order_number_suffix;
	}

	private function order_number_size() {
		if ( ! isset( $this->order_number_size ) )
			$order_number_size_including_prefix_enable = get_option( 'woocommerce_order_number_size_including_prefix', 'no' );
			$order_number_size_including_suffix_enable = get_option( 'woocommerce_order_number_size_including_suffix', 'no' );
		if ( $order_number_size_including_prefix_enable =="yes" ) {
			$prefix_length = strlen(get_option( 'woocommerce_order_number_prefix', "" ));
		} else {
			 $prefix_length = 0;
		}
	
		if ( $order_number_size_including_suffix_enable =="yes" ) {
			$suffix_length = strlen(get_option( 'woocommerce_order_number_suffix', "" ));
		} else {
			$suffix_length = 0;
		}
	
		$this->order_number_size = (string)(get_option( 'woocommerce_order_number_size', 1 )-$prefix_length-$suffix_length);
		$order_number_length = strlen( get_option('woocommerce_order_number_size', 1) );
		return $this->order_number_size;
	}

	public function get_order_number( $order_number, $order ) {
		global $WC_Sequential_Order_Numbers;
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
			return $maybe_hash . $this->format_order_number( $order_number, $this->order_number_prefix(), $this->order_number_suffix(), $this->order_number_length() ) . ' (' . __( 'Draft', $WC_Sequential_Order_Numbers->text_domain ) . ')';
		}
		
	    return $order_number;
	}

	private function skip_free_orders() {
		return 'yes' == get_option( 'woocommerce_order_number_skip_free_orders', 'no' );
	}
	
	private function hash_before_order_number() {
		return 'yes' == get_option( 'woocommerce_hash_before_order_number', 'no' );
	}
	
	public function find_sequencial_order_id_by_order_number( $order_id ) {
		global $wpdb;
		$tablename = $wpdb->prefix.'postmeta';
		$row = $wpdb->get_row("SELECT * FROM $tablename WHERE meta_key = '_order_number_formatted' and meta_value = '$order_id'", ARRAY_A);
		return $row['post_id'];	
	}
	
	public function subscriptions_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		$order_meta_query .= " AND meta_key NOT IN ( '_order_number', '_order_number_formatted', '_order_number_free', '_order_number_meta' )";
		return $order_meta_query;
	}
	
	public function subscriptions_sequential_order_number( $renewal_order, $original_order, $product_id, $new_order_role ) {
		$order_post = get_post( $renewal_order->id );
		$this->create_sequential_order_number( $order_post->ID, $order_post );
	}
}
?>
