<?php
class WC_Sequential_Order_Numbers_Admin {
  
  public $settings;

	private $errors = null;
	
	private $order_number_prefix;
	
	private $order_number_suffix;
	
	private $order_number_size;
	

	public function __construct() {

		add_action( 'woocommerce_checkout_update_order_meta', array(&$this, 'create_sequential_order_number' ), 10, 2 );
		add_action( 'woocommerce_process_shop_order_meta', array(&$this, 'create_sequential_order_number' ), 15, 2 );
		add_action( 'woocommerce_before_resend_order_emails', array(&$this, 'create_sequential_order_number' ), 10, 1 );
		
		add_filter( 'woocommerce_order_number', array($this, 'get_order_number'), 10, 2);
		
		add_filter( 'woocommerce_shortcode_order_tracking_order_id', array(&$this, 'find_sequencial_order_id_by_order_number' ) );
		
		add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array(&$this, 'subscriptions_renewal_order_meta' ), 10, 4 );
		add_action( 'woocommerce_subscriptions_renewal_order_created', array(&$this, 'subscriptions_sequential_order_number' ), 9, 4 );
		
		add_filter( 'request', array(&$this, 'custom_shop_order' ), 20 );
		add_filter( 'woocommerce_shop_order_search_fields', array(&$this, 'order_search_fields' ) );

		add_filter( 'wc_pre_orders_edit_pre_orders_request', array(&$this, 'custom_order' ) );
		add_filter( 'wc_pre_orders_search_fields', array(&$this, 'order_search_fields' ) );

		$this->load_class('settings');
		$this->settings = new WC_Sequential_Order_Numbers_Settings();

	}
	
	public function create_sequential_order_number( $post_id, $post = array() ) {
		if( is_array( $post ) || ( 'shop_order' == $post->post_type && 'auto-draft' != $post->post_status ) ) {
			$post_id = is_a( $post, 'WC_Order' ) ? $post->id : $post_id;
			$order_number = get_post_meta( $post_id, '_order_number' );
			if ( empty( $order_number ) ) {
				if ( $this->skip_free_orders() && $this->is_free_order( $post_id ) ) {
					$themeta_free = get_post_meta($post->ID, '_order_number_free', TRUE);
					if($themeta_free != '') {
						if ( $this->generate_sequential_order_number( $post_id, '_order_number_free', $this->free_order_number_start(), $this->free_order_number_prefix() ) ) {
							update_post_meta( $post_id, '_order_number', -1 );
						}
					}
				} else {
					$themeta = get_post_meta($post->ID, '_order_number', TRUE);
					if($themeta != '') {
						$this->generate_sequential_order_number( $post_id, '_order_number', get_option( 'woocommerce_order_number_start' ), $this->order_number_prefix(), $this->order_number_suffix(), $this->order_number_size() );
					}
				}
			}
		}
  }


	public function find_sequencial_order_id_by_order_number( $order_number ) {

		$query_args = array(
			'numberposts' => 1,
			'meta_key'    => '_order_number_formatted',
			'meta_value'  => $order_number,
			'post_type'   => 'shop_order',
			'post_status' => 'publish',
			'fields'      => 'ids'
		);

		list( $order_id ) = get_posts( $query_args );
		
		if ( null !== $order_id ) return $order_id;
		
		$order = new WC_Order( $order_number );
		if ( isset( $order->order_custom_fields['_order_number_formatted'][0] ) ) {
			return 0;
		}

		return $order->id;
	}

	private function generate_sequential_order_number( $post_id, $order_number_meta_name, $order_number_start, $order_number_prefix = '', $order_number_suffix = '', $order_number_size = 1 ) {
		global $wpdb;

		$success = false;
		if (!isset($order_number_start)) {
			$order_number_start  = get_post_meta( $post_id, '_order_number_meta' );
		}
		for ( $i = 0; $i < 3 && ! $success; $i++ ) {
			$query = $wpdb->prepare("
				INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
				SELECT %d,'{$order_number_meta_name}',IF(MAX(CAST(meta_value AS SIGNED)) IS NULL OR MAX(CAST(meta_value AS SIGNED)) < %d, %d, MAX(CAST(meta_value AS SIGNED))+1)
					FROM {$wpdb->postmeta}
					WHERE meta_key='{$order_number_meta_name}'",
				 $post_id, $order_number_start, $order_number_start);
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
			return $maybe_hash . $this->format_order_number( $order_number, $this->order_number_prefix(), $this->order_number_suffix(), $this->order_number_size() ) . ' (' . __( 'Draft', $WC_Sequential_Order_Numbers->text_domain ) . ')';
		}
	  return $order_number;
	}

	public function custom_shop_order( $vars ) {
		global $typenow, $wp_query;
		if ( 'shop_order' != $typenow ) return $vars;
		return $this->custom_order( $vars );
	}

	public function custom_order( $args ) {
		if ( isset( $args['orderby'] ) && 'ID' == $args['orderby'] ) {
			$args = array_merge( $args, array(
				'meta_key' => '_order_number',  
				'orderby'  => 'meta_value_num',
			) );
		}
		return $args;
	}

	public function order_search_fields( $search_fields ) {
		array_push( $search_fields, '_order_number_formatted' );
		return $search_fields;
	}

	public function subscriptions_sequential_order_number( $renewal_order, $original_order, $product_id, $new_order_role ) {
		$order_post = get_post( $renewal_order->id );
		$this->create_sequential_order_number( $order_post->ID, $order_post );
	}

	public function subscriptions_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {
		$order_meta_query .= " AND meta_key NOT IN ( '_order_number', '_order_number_formatted', '_order_number_free', '_order_number_meta' )";
		return $order_meta_query;
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


	private function hash_before_order_number() {
		return 'yes' == get_option( 'woocommerce_hash_before_order_number', 'no' );
	}

	private function order_number_size_including_prefix() {
		return 'yes' == get_option( 'woocommerce_order_number_size_including_prefix', 'no' );
	}

	private function order_number_size_including_suffix() {
		return 'yes' == get_option( 'woocommerce_order_number_size_including_suffix', 'no' );
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
		if ( ! isset( $this->order_number_size )  )
			$order_number_size_including_prefix_enable = get_option( 'woocommerce_order_number_size_including_prefix', 'no' );
			$order_number_size_including_suffix_enable = get_option( 'woocommerce_order_number_size_including_suffix', 'no' );
		if ( $order_number_size_including_prefix_enable =="yes" ){
			$prefix_length = strlen(get_option( 'woocommerce_order_number_prefix', "" ));
		} else {
			 $prefix_length = 0;
		}
	
		if ( $order_number_size_including_suffix_enable =="yes" ){   
			$suffix_length = strlen(get_option( 'woocommerce_order_number_suffix', "" ));
		} else {
			$suffix_length = 0;
		}
	
		$this->order_number_size = (string)(get_option( 'woocommerce_order_number_size', 1 )-$prefix_length-$suffix_length);
		$order_number_length = strlen(get_option('woocommerce_order_number_size', 1));
		return $this->order_number_size;
	}

	private function skip_free_orders() {
		return 'yes' == get_option( 'woocommerce_order_number_skip_free_orders', 'no' );
	}

	private function free_order_number_prefix() {
		return get_option( 'woocommerce_free_order_number_prefix', __( 'FREE-' ) );
	}

	private function free_order_number_start() {
		return get_option( 'woocommerce_free_order_number_start' );
	}

	private function is_free_order( $order_id ) {
		$is_free = true;
		$order = new WC_Order( $order_id );
		if ( $order->get_total() > 0 ) {
			$is_free = false;
		}
		return $is_free;
	}

	function load_class($class_name = '') {
	  global $WC_Sequential_Order_Numbers;
		if ('' != $class_name) {
			require_once ($WC_Sequential_Order_Numbers->plugin_path . '/admin/class-' . esc_attr($WC_Sequential_Order_Numbers->token) . '-' . esc_attr($class_name) . '.php');
		} // End If Statement
	}// End load_class()
}
?>