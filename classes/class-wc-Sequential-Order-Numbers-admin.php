<?php
class WC_Sequential_Order_Numbers_Admin {
  
  public $settings;

	private $errors = null;
	
	private $order_number_prefix;
	
	private $order_number_suffix;
	
	private $order_number_length;
	

	public function __construct() {

		add_action( 'wp_insert_post', array( $this, 'set_sequential_order_number' ), 10, 2 );
		
		add_action( 'woocommerce_checkout_update_order_meta', array(&$this, 'set_sequential_order_number' ), 10, 2 );
		
		add_action( 'woocommerce_process_shop_order_meta', array(&$this, 'set_sequential_order_number' ), 15, 2 );
		
		add_action( 'woocommerce_before_resend_order_emails', array(&$this, 'set_sequential_order_number' ), 10, 1 );
		// return our custom order number for display
		add_filter( 'woocommerce_order_number', array(&$this, 'get_order_number' ), 10, 2);

		// order tracking page search by order number
		add_filter( 'woocommerce_shortcode_order_tracking_order_id', array(&$this, 'find_order_by_order_number' ) );

		// WC Subscriptions support: prevent unnecessary order meta from polluting parent renewal orders, and set order number for subscription orders
		add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array(&$this, 'subscriptions_remove_renewal_order_meta' ), 10, 4 );
		
		add_action( 'woocommerce_subscriptions_renewal_order_created', array(&$this, 'subscriptions_set_sequential_order_number' ), 9, 4 );

		// keep the admin order search/order working properly
		add_filter( 'request', array(&$this, 'woocommerce_custom_shop_order_orderby' ), 20 );
		
		add_filter( 'woocommerce_shop_order_search_fields', array(&$this, 'custom_search_fields' ) );

		// sort by underlying _order_number on the Pre-Orders table
		add_filter( 'wc_pre_orders_edit_pre_orders_request', array(&$this, 'custom_orderby' ) );
		
		add_filter( 'wc_pre_orders_search_fields', array(&$this, 'custom_search_fields' ) );

		$this->load_class('settings');
      	
		$this->settings = new WC_Sequential_Order_Numbers_Settings();

	}

	/**
	 * Search for an order with order_number $order_number.  This method can be
	 * useful for 3rd party plugins that want to rely on the Sequential Order
	 * Numbers plugin and perform lookups by custom order number.
	 *
	 * @param string $order_number order number to search for
	 * @return int post_id for the order identified by $order_number, or 0
	 */

	public function find_order_by_order_number( $order_number ) {

		// search for the order by custom order number
		$query_args = array(
			'numberposts' => 1,
			'meta_key'    => '_order_number_formatted',
			'meta_value'  => $order_number,
			'post_type'   => 'shop_order',
			'post_status' => 'publish',
			'fields'      => 'ids'
		);

		list( $order_id ) = get_posts( $query_args );

		// order was found
		if ( null !== $order_id ) return $order_id;

		// if we didn't find the order, then it may be that this plugin was disabled and an order was placed in the interim
		$order = new WC_Order( $order_number );
		if ( isset( $order->order_custom_fields['_order_number_formatted'][0] ) ) {
			// _order_number was set, so this is not an old order, it's a new one that just happened to have post_id that matched the searched-for order_number
			return 0;
		}

		return $order->id;
	}

	

	public function set_sequential_order_number( $post_id, $post = array() ) {

		// when creating an order from the admin don't create order numbers for auto-draft
		//  orders, because these are not linked to from the admin and so difficult to delete
		if ( is_array( $post ) || ( 'shop_order' == $post->post_type && 'auto-draft' != $post->post_status ) ) {

			$post_id = is_a( $post_id, 'WC_Order' ) ? $post_id->id : $post_id;
			$order_number = get_post_meta( $post_id, '_order_number' );

			// if no order number has been assigned, this will be an empty array
			if ( empty( $order_number ) ) {

				if ( $this->skip_free_orders() && $this->is_free_order( $post_id ) ) {
					// assign sequential free order number
					if ( $this->generate_sequential_order_number( $post_id, '_order_number_free', $this->get_free_order_number_start(), $this->get_free_order_number_prefix() ) ) {
						// so that sorting still works in the admin
						update_post_meta( $post_id, '_order_number', -1 );
					}
				} else {
					// normal operation
					$this->generate_sequential_order_number( $post_id, '_order_number', get_option( 'woocommerce_order_number_start' ), $this->get_order_number_prefix(), $this->get_order_number_suffix(), $this->get_order_number_length() );
				}
			}
		}
	}

	private function generate_sequential_order_number( $post_id, $order_number_meta_name, $order_number_start, $order_number_prefix = '', $order_number_suffix = '', $order_number_length = 1 ) {
		global $wpdb;

		$success = false;
		if (!isset($order_number_start)) {
			$order_number_start  = get_post_meta( $post_id, '_order_number_meta' );
		}
		print_r($order_number_start);

		for ( $i = 0; $i < 3 && ! $success; $i++ ) {
			// add $order_number_meta_name equal to $order_number_start if there are no existing orders with an $order_number_meta_name meta
			//  or $order_number_start is larger than the max existing $order_number_meta_name meta.  Otherwise, $order_number_meta_name
			//  will be set to the max $order_number_meta_name + 1
			$query = $wpdb->prepare( "
				INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value)
				SELECT %d,'{$order_number_meta_name}',IF(MAX(CAST(meta_value AS SIGNED)) IS NULL OR MAX(CAST(meta_value AS SIGNED)) < %d, %d, MAX(CAST(meta_value AS SIGNED))+1)
					FROM {$wpdb->postmeta}
					WHERE meta_key='{$order_number_meta_name}'",
				$post_id, $order_number_start, $order_number_start );
			$success = $wpdb->query( $query );

			if ( $success ) {
				// on success, set the formatted order number
				$order_number = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_id = %d", $wpdb->insert_id ) );
                
                print_r($order_number_start);
                    
				update_post_meta( $post_id, '_order_number_formatted', $this->format_order_number( $order_number, $order_number_prefix, $order_number_suffix, $order_number_length ) );

				// save the order number configuration at the time of creation, so the integer part can be renumbered at a later date if needed
				$order_number_meta = array(
					'prefix' => $order_number_prefix,
					'suffix' => $order_number_suffix,
					'length' => $order_number_length
				);
				update_post_meta( $post_id, '_order_number_meta', $order_number_meta );
			}
		}
		return $success;
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

	public function woocommerce_custom_shop_order_orderby( $vars ) {
		global $typenow, $wp_query;
		if ( 'shop_order' != $typenow ) return $vars;

		return $this->custom_orderby( $vars );
	}

	/**
	 * Mofifies the given $args argument to sort on our meta integral _order_number
	 * @param array $vars associative array of orderby parameteres
	 * @return array associative array of orderby parameteres
	 */
	public function custom_orderby( $args ) {
		// Sorting
		if ( isset( $args['orderby'] ) && 'ID' == $args['orderby'] ) {
			$args = array_merge( $args, array(
				'meta_key' => '_order_number',  // sort on numerical portion for better results
				'orderby'  => 'meta_value_num',
			) );
		}

		return $args;
	}

	/**
	 * Add our custom _order_number_formatted to the set of search fields so that
	 * the admin search functionality is maintained
	 *
	 * @param array $search_fields array of post meta fields to search by
	 *
	 * @return array of post meta fields to search by
	 */
	public function custom_search_fields( $search_fields ) {

		array_push( $search_fields, '_order_number_formatted' );

		return $search_fields;
	}

	/**
	 * Sets an order number on a subscriptions-created order
	 * @param WC_Order $renewal_order the new renewal order object
	 * @param WC_Order $original_order the original order object
	 * @param int $product_id the product post identifier
	 * @param string $new_order_role the role the renewal order is taking, one of 'parent' or 'child'
	 */
	public function subscriptions_set_sequential_order_number( $renewal_order, $original_order, $product_id, $new_order_role ) {
		$order_post = get_post( $renewal_order->id );
		$this->set_sequential_order_number( $order_post->ID, $order_post );
	}


	/**
	 * Don't copy over order number meta when creating a parent or child renewal order
	 * @param array $order_meta_query query for pulling the metadata
	 * @param int $original_order_id Post ID of the order being used to purchased the subscription being renewed
	 * @param int $renewal_order_id Post ID of the order created for renewing the subscription
	 * @param string $new_order_role The role the renewal order is taking, one of 'parent' or 'child'
	 * @return string
	 */
	public function subscriptions_remove_renewal_order_meta( $order_meta_query, $original_order_id, $renewal_order_id, $new_order_role ) {

		$order_meta_query .= " AND meta_key NOT IN ( '_order_number', '_order_number_formatted', '_order_number_free', '_order_number_meta' )";

		return $order_meta_query;
	}
	
	/**
	 * Returns $order_number formatted with the order number prefix/
	 * postfix, if set
	 *
	 * @param int $order_number incrementing portion of the order number
	 * @param string $order_number_prefix optional order number prefix string
	 * @param string $order_number_suffix optional order number suffix string
	 * @param int $order_number_length optional order number length
	 *
	 * @return string formatted order number
	 */
	private function format_order_number( $order_number, $order_number_prefix = '', $order_number_suffix = '', $order_number_length = 1 ) {

		$order_number = (int) $order_number;

		// any order number padding?
		if ( $order_number_length && ctype_digit( $order_number_length ) ) {
			$order_number = sprintf( "%0{$order_number_length}d", $order_number );
		}

		$formatted = $order_number_prefix . $order_number . $order_number_suffix;

		// pattern substitution
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


	/**
	 * Returns true if the order number should be displayed with a leading hash (#)
	 * Defaults to true
	 *
	 * @return boolean true if the order number should be displayed with a leading hash
	 */
	private function get_has_hash_before_order_number() {
		return 'yes' == get_option( 'woocommerce_hash_before_order_number', 'no' );
	}


	/**
	 * Returns the order number prefix, if set
	 *
	 * @return string order number prefix
	 */
	private function get_order_number_prefix() {

		if ( ! isset( $this->order_number_prefix ) )
			$this->order_number_prefix = get_option( 'woocommerce_order_number_prefix', "" );

		return $this->order_number_prefix;
	}


	/**
	 * Returns the order number suffix, if set
	 *
	 * @return string order number suffix
	 */
	private function get_order_number_suffix() {

		if ( ! isset( $this->order_number_suffix ) )
			$this->order_number_suffix = get_option( 'woocommerce_order_number_suffix', "" );

		return $this->order_number_suffix;
	}


	/**
	 * Returns the order number length, defaulting to 1 if not set
	 * @return string order number length
	 */
	private function get_order_number_length() {

		if ( ! isset( $this->order_number_length ) )
			$this->order_number_length = get_option( 'woocommerce_order_number_length', 1 );

		return $this->order_number_length;
	}


	/**
	 * Returns true if order numbers should be skipped for orders consisting
	 * solely of free products
	 * @return boolean true if order numbers should be skipped for free orders
	 */
	private function skip_free_orders() {
		return 'yes' == get_option( 'woocommerce_order_number_skip_free_orders', 'no' );
	}


	/**
	 * Returns the value to use in place of the order number for free orders
	 * when 'skip free orders' is enabled
	 * @return string text to use in place of the order number for free orders
	 */
	private function get_free_order_number_prefix() {
		return get_option( 'woocommerce_free_order_number_prefix', __( 'FREE-' ) );
	}


	/**
	 * Gets the free order number incrementing piece
	 * @return int free order number incrementing portion
	 */
	private function get_free_order_number_start() {
		return get_option( 'woocommerce_free_order_number_start' );
	}


	/**
	 * Returns true if this order consists entirely of free products AND
	 * @param int $order_id order identifier
	 * @return boolean true if the order consists solely of free products
	 */
	private function is_free_order( $order_id ) {

		$is_free = true;
		$order = new WC_Order( $order_id );

		// easy check: order total
		if ( $order->get_total() > 0 ) $is_free = false;

		// free order
		return apply_filters( 'wc_sequential_order_numbers_is_free_order', $is_free, $order_id );
	}

	function load_class($class_name = '') {
	  global $WC_Sequential_Order_Numbers;
		if ('' != $class_name) {
			require_once ($WC_Sequential_Order_Numbers->plugin_path . '/admin/class-' . esc_attr($WC_Sequential_Order_Numbers->token) . '-' . esc_attr($class_name) . '.php');
		} // End If Statement
	}// End load_class()
}