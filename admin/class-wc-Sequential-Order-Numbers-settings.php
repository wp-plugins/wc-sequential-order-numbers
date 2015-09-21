<?php
class WC_Sequential_Order_Numbers_Settings {
  
private $options;

  private $errors = null;
  
  private $order_number_prefix;
  
  private $order_number_size_including_prefix;
  
  private $order_number_suffix;
  
  private $order_number_size_including_suffix;
  
  private $order_number_size;

  private $order_number_start;

  public function __construct() {
    
    add_filter( 'woocommerce_general_settings', array(&$this, 'settings' ) );

    
    add_filter( 'pre_update_option_woocommerce_order_number_start', array(&$this, 'order_number_start_setting' ), 10, 2 );
    
    add_filter( 'wp_redirect', array(&$this, 'error_msg' ), 10, 2 );

  }
  
  public function order_number_start_setting( $newvalue, $oldvalue ) {

    global $wpdb;

    
    if ( (int) $newvalue === (int) $oldvalue ) {

     
      update_option( 'woocommerce_order_number_size', strlen( $newvalue ) );

      return $newvalue;
    }

    if ( ! ctype_digit( $newvalue ) || (int) $newvalue != $newvalue ) {

     
      $this->errors = __( 'Order Number Start must be a number greater than or equal to 0.' );

      return $oldvalue;
    }

    
    $order_number = $wpdb->get_var( "SELECT MAX(CAST(meta_value AS SIGNED)) FROM $wpdb->postmeta WHERE meta_key='_order_number'" );

    if ( ! is_null( $order_number ) && (int) $order_number >= $newvalue ) {

      $post_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_order_number' AND meta_value = %d", $order_number ) );

      if ( class_exists( 'WC_Order' ) ) {
        $order = new WC_Order( $post_id );
        $highest_order_number = $order->get_order_number();
      } else {
        $highest_order_number = $post_id;
      }

      $this->errors = sprintf( __( 'There is an existing order (%s) with a number greater than or equal to %s.  To set a new order number start please choose a higher number or permanently delete the relevant order(s).' ), $highest_order_number, (int) $newvalue );

      return $oldvalue;
    }

   
    update_option( 'woocommerce_order_number_size', strlen( $newvalue ) );

    
    return $newvalue;
  }

  public function error_msg( $location, $status) {
    global $woocommerce;

    if ( $this->errors ) {
      $location = add_query_arg( array( 'wc_error' => urlencode( $this->errors ) ), $location );
    } elseif( strpos( $location, urlencode( __( 'Order Number Start must be a number greater than or equal to 0' ) ) ) !== false ||
            strpos( $location, urlencode( __( 'There is an existing order' ) ) ) !== false ) {
      
      $location = remove_query_arg( 'wc_error', $location );
    }
    
    return $location;
  }

  public function settings( $settings ) {

    $updated_settings = array();

    foreach ( $settings as $section ) {

      $updated_settings[] = $section;

      
      if ( isset( $section['id'] ) && 'general_options' == $section['id'] &&
         isset( $section['type'] ) && 'sectionend' == $section['type'] ) {

        $updated_settings[] = array( 'name' => __( 'Order Numbers' ), 'type' => 'title', 'desc' => '', 'id' => 'order_number_options' );

        $updated_settings[] = array(
          'name'     => __( 'Order Number Start' ),
          'desc_tip' => sprintf( __( 'The starting number for the incrementing portion of the order numbers, unless there is an existing order with a higher number.  Use leading zeroes to pad your order numbers to a desired minimum length.  Any newly placed orders will be numbered like: %s' ), $this->format_order_number( get_option( 'woocommerce_order_number_start' ), $this->order_number_prefix(), $this->order_number_suffix(), $this->woocommerce_order_number_size() ) ),
          'id'       => 'woocommerce_order_number_start',
          'type'     => 'text',
          'css'      => 'min-width:300px;',
          'default'  => '',
          'desc'     => sprintf( __( 'Sample order number: %s' ), '<span id="sample_order_number">' . $this->format_order_number( get_option( 'woocommerce_order_number_start' ), $this->order_number_prefix(), $this->order_number_suffix(), $this->order_number_size() ) . '</span>' )
        );

        $updated_settings[] = array(
          'name'     => __( 'Hash Before Order number ' ),
          'id'       => 'woocommerce_hash_before_order_number',
          'type'     => 'checkbox',
          'css'      => 'min-width:300px;',
          'default'  => 'no', 
          'desc'     => __( 'Display a hash (#) before order numbers on frontend and admin.' )
        );

        $updated_settings[] = array(
          'name'     => __( 'Order Number Prefix' ),
          'desc_tip' => __( 'Set your custom order number prefix.  You may use {DD}, {MM}, {YYYY} for the current day, month and year respectively.' ),
          'id'       => 'woocommerce_order_number_prefix',
          'type'     => 'text',
          'css'      => 'min-width:300px;',
          'default'  => ''
        );

        
        $updated_settings[] = array(
          'name'     => __( 'Order Number Suffix' ),
          'desc_tip' => __( 'Set your custom order number suffix.  You may use {DD}, {MM}, {YYYY} for the current day, month and year respectively.' ),
          'id'       => 'woocommerce_order_number_suffix',
          'type'     => 'text',
          'css'      => 'min-width:300px;',
          'default'  => ''
        );

        $updated_settings[] = array(
          'name'     => __( 'Order Number Size' ),
          'id'       => 'woocommerce_order_number_size',
          'type'     => 'number',
          'css'      => 'min-width:50px;',
          'default'  => '',
          'desc'     => __( 'Set a custom order number size.' )
        );

        $updated_settings[] = array(
          'name'     => __( 'Order Number Size Including Prefix' ),
          'id'       => 'woocommerce_order_number_size_including_prefix',
          'type'     => 'checkbox',
          'css'      => 'min-width:300px;',
          'default'  => 'no'
        );

        $updated_settings[] = array(
          'name'     => __( 'Order Number Size Including Suffix' ),
          'id'       => 'woocommerce_order_number_size_including_suffix',
          'type'     => 'checkbox',
          'css'      => 'min-width:300px;',
          'default'  => 'no'
        );

        
        $updated_settings[] = array(
          'name'     => __( 'Skip Free Orders' ),
          'desc'     => __( 'Skip order numbers for free orders' ),
          'desc_tip' => __( 'With this enabled an order number will not be assigned to an order consisting solely of free products.' ),
          'id'       => 'woocommerce_order_number_skip_free_orders',
          'type'     => 'checkbox',
          'css'      => 'min-width:300px;',
          'default'  => 'no', 
          'std'      => 'no' 
        );

        $updated_settings[] = array(
          'name'     => __( 'Free Order Identifer' ),
          'desc'     => sprintf( __( 'Example free order identifier: %s' ), '<span id="sample_free_order_number">' . $this->format_order_number( $this->free_order_number_start(), $this->free_order_number_prefix() ) . '</span>' ),
          'desc_tip' => __( 'The text to display in place of the order number for free orders.  This will be displayed anywhere an order number would otherwise be shown: to the customer, in emails, and in the admin.' ),
          'id'       => 'woocommerce_free_order_number_prefix',
          'type'     => 'text',
          'css'      => 'min-width:300px;',
          'default'  => __( 'FREE-' ), 
          'std'      => __( 'FREE-' ) 
        );

        $updated_settings[] = array( 'type' => 'sectionend', 'id' => 'order_number_options' );
      }
    }
    return $updated_settings;
  }

  
  private function free_order_number_start() {
    return get_option( 'woocommerce_free_order_number_start' );
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
      $this->order_number_size = get_option( 'woocommerce_order_number_size', 1 );

    return $this->order_number_size;
  }

  private function order_number_size_including_prefix() {

    if ( ! isset( $this->order_number_size_including_prefix ) )
      $this->order_number_size_including_prefix = get_option( 'woocommerce_order_number_size_including_prefix', "" );

    return $this->order_number_size_including_prefix;
  }

  private function order_number_size_including_suffix() {

    if ( ! isset( $this->order_number_size_including_suffix ) )
      $this->order_number_size_including_suffix = get_option( 'woocommerce_order_number_size_including_suffix', "" );

    return $this->order_number_size_including_suffix;
  }

  private function free_order_number_prefix() {
    return get_option( 'woocommerce_free_order_number_prefix', __( 'FREE-' ) );
  }

  private function woocommerce_order_number_size() {
    
  }

}