<?php
class WC_Sequential_Order_Numbers_Settings {
  
private $options;

  private $errors = null;
  /**
   * @var string
   */
  private $order_number_prefix;
  /**
   * @var string
   */
  private $order_number_suffix;
  /**
   * @var int
   */
  private $order_number_length;

  private $order_number_start;

  
  /**
   * Start up
   */
  public function __construct() {
    // inject our admin options
    add_filter( 'woocommerce_general_settings', array(&$this, 'admin_settings' ) );

    // roll my own Settings field error check, which isn't beautiful, but is important
    add_filter( 'pre_update_option_woocommerce_order_number_start', array(&$this, 'validate_order_number_start_setting' ), 10, 2 );
    add_filter( 'wp_redirect', array(&$this, 'add_settings_error_msg' ), 10, 2 );

  }

  /**
   * Validate the order number start setting, by verifying that
   * $newvalue is an integer.
   *
   * @param string $newvalue the new value to set
   * @param string $oldvalue the previous value
   *
   * @return string $newvalue if it is a positive integer, $oldvalue otherwise
   */
  public function validate_order_number_start_setting( $newvalue, $oldvalue ) {

    global $wpdb;

    // no change to starting order number
    if ( (int) $newvalue === (int) $oldvalue ) {

      // $newvalue can include left hand zero padding to set a number length, update the value if that is all that's changed
      update_option( 'woocommerce_order_number_length', strlen( $newvalue ) );

      return $newvalue;
    }

    if ( ! ctype_digit( $newvalue ) || (int) $newvalue != $newvalue ) {

      // bad value
      $this->errors = __( 'Order Number Start must be a number greater than or equal to 0.' );

      return $oldvalue;
    }

    // check for an existing order number with a greater incrementing value
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

    // $newvalue can include left hand zero padding to set a number length, update this value first in case nothing else changed
    update_option( 'woocommerce_order_number_length', strlen( $newvalue ) );

    // good value, and remove any padding zeroes
    return $newvalue;
  }


  /**
   * Filter to add the settings error message, if needed, and remove
   * it from the $location url otherwise
   *
   * @param string $location url
   * @param int $status
   *
   * @return string the location to redirect to
   */
  public function add_settings_error_msg( $location, $status) {
    global $woocommerce;

    if ( $this->errors ) {
      $location = add_query_arg( array( 'wc_error' => urlencode( $this->errors ) ), $location );
    } elseif( strpos( $location, urlencode( __( 'Order Number Start must be a number greater than or equal to 0' ) ) ) !== false ||
            strpos( $location, urlencode( __( 'There is an existing order' ) ) ) !== false ) {
      // otherwise if my error is currently in the url, remove it.  This must be done because it will be kept in the url by the settings form wp_nonce_field() call
      $location = remove_query_arg( 'wc_error', $location );
    }

    return $location;
  }


  /**
   * Inject our admin settings into the Settings > General page
   *
   * @param array $settings associative-array of WooCommerce settings
   * @return array associative-array of WooCommerce settings
   */
  public function admin_settings( $settings ) {

    $updated_settings = array();

    foreach ( $settings as $section ) {

      $updated_settings[] = $section;

      // New section after the "General Options" section
      if ( isset( $section['id'] ) && 'general_options' == $section['id'] &&
         isset( $section['type'] ) && 'sectionend' == $section['type'] ) {

        $updated_settings[] = array( 'name' => __( 'Order Numbers' ), 'type' => 'title', 'desc' => '', 'id' => 'order_number_options' );

        $updated_settings[] = array(
          'name'     => __( 'Order Number Start' ),
          'desc_tip' => sprintf( __( 'The starting number for the incrementing portion of the order numbers, unless there is an existing order with a higher number.  Use leading zeroes to pad your order numbers to a desired minimum length.  Any newly placed orders will be numbered like: %s' ), $this->format_order_number( get_option( 'woocommerce_order_number_start' ), $this->get_order_number_prefix(), $this->get_order_number_suffix(), $this->get_order_number_length() ) ),
          'id'       => 'woocommerce_order_number_start',
          'type'     => 'text',
          'css'      => 'min-width:300px;',
          'default'  => '',
          'desc'     => sprintf( __( 'Sample order number: %s' ), '<span id="sample_order_number">' . $this->format_order_number( get_option( 'woocommerce_order_number_start' ), $this->get_order_number_prefix(), $this->get_order_number_suffix(), $this->get_order_number_length() ) . '</span>' )
        );

        $updated_settings[] = array(
          'name'     => __( 'Hash Before Order number ' ),
          'id'       => 'woocommerce_hash_before_order_number',
          'type'     => 'checkbox',
          'css'      => 'min-width:300px;',
          'default'  => 'no', // WC >= 2.0
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
          'name'     => __( 'Skip Free Orders' ),
          'desc'     => __( 'Skip order numbers for free orders' ),
          'desc_tip' => __( 'With this enabled an order number will not be assigned to an order consisting solely of free products.' ),
          'id'       => 'woocommerce_order_number_skip_free_orders',
          'type'     => 'checkbox',
          'css'      => 'min-width:300px;',
          'default'  => 'no', // WC >= 2.0
          'std'      => 'no' // WC < 2.0
        );

        $updated_settings[] = array(
          'name'     => __( 'Free Order Identifer' ),
          'desc'     => sprintf( __( 'Example free order identifier: %s' ), '<span id="sample_free_order_number">' . $this->format_order_number( $this->get_free_order_number_start(), $this->get_free_order_number_prefix() ) . '</span>' ),
          'desc_tip' => __( 'The text to display in place of the order number for free orders.  This will be displayed anywhere an order number would otherwise be shown: to the customer, in emails, and in the admin.' ),
          'id'       => 'woocommerce_free_order_number_prefix',
          'type'     => 'text',
          'css'      => 'min-width:300px;',
          'default'  => __( 'FREE-' ), // WC >= 2.0
          'std'      => __( 'FREE-' ) // WC < 2.0
        );

        $updated_settings[] = array( 'type' => 'sectionend', 'id' => 'order_number_options' );
      }
    }
    return $updated_settings;
  }


  /**
   * Render the admin settings javascript which will live-update the sample
   * order number for improved feedback when configuring
   *
   * @since 1.3
   */
  
  private function get_free_order_number_start() {
    return get_option( 'woocommerce_free_order_number_start' );
  }
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
  private function get_order_number_prefix() {

    if ( ! isset( $this->order_number_prefix ) )
      $this->order_number_prefix = get_option( 'woocommerce_order_number_prefix', "" );

    return $this->order_number_prefix;
  }
  private function get_order_number_suffix() {

    if ( ! isset( $this->order_number_suffix ) )
      $this->order_number_suffix = get_option( 'woocommerce_order_number_suffix', "" );

    return $this->order_number_suffix;
  }
  private function get_order_number_length() {

    if ( ! isset( $this->order_number_length ) )
      $this->order_number_length = get_option( 'woocommerce_order_number_length', 1 );

    return $this->order_number_length;
  }
  private function get_free_order_number_prefix() {
    return get_option( 'woocommerce_free_order_number_prefix', __( 'FREE-' ) );
  }

}
  
  