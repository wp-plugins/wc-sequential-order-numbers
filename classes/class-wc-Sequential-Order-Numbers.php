<?php
class WC_Sequential_Order_Numbers {

	public $plugin_url;

	public $plugin_path;

	public $version;

	public $token;
	
	public $text_domain;
	
	public $admin;

	public $frontend;

	private $file;
	
	public function __construct($file) {

		$this->file = $file;
		$this->plugin_url = trailingslashit(plugins_url('', $plugin = $file));
		$this->plugin_path = trailingslashit(dirname($file));
		$this->token = WC_SEQUENTIAL_ORDER_NUMBERS_PLUGIN_TOKEN;
		$this->text_domain = WC_SEQUENTIAL_ORDER_NUMBERS_TEXT_DOMAIN;
		$this->version = WC_SEQUENTIAL_ORDER_NUMBERS_PLUGIN_VERSION;
		
		add_action('init', array(&$this, 'init'), 0);
	}
	
	/**
	 * initilize plugin on WP init
	 */
	function init() {
		
		// Init Text Domain
		$this->load_plugin_textdomain();

		if (is_admin()) {
			$this->load_class('admin');
			$this->admin = new WC_Sequential_Order_Numbers_Admin();
		}

		if (!is_admin() || defined('DOING_AJAX')) {
			$this->load_class('frontend');
			$this->frontend = new WC_Sequential_Order_Numbers_Frontend();
		}

	}
	
	/**
   * Load Localisation files.
   *
   * Note: the first-loaded translation file overrides any following ones if the same translation is present
   *
   * @access public
   * @return void
   */
  public function load_plugin_textdomain() {
    $locale = apply_filters( 'plugin_locale', get_locale(), $this->token );

    load_textdomain( $this->text_domain, WP_LANG_DIR . "/wc-Sequential-Order-Numbers/wc-Sequential-Order-Numbers-$locale.mo" );
    load_textdomain( $this->text_domain, $this->plugin_path . "/languages/wc-Sequential-Order-Numbers-$locale.mo" );
  }

	public function load_class($class_name = '') {
		if ('' != $class_name && '' != $this->token) {
			require_once ('class-' . esc_attr($this->token) . '-' . esc_attr($class_name) . '.php');
		} // End If Statement
	}// End load_class()
	
	/** Cache Helpers *********************************************************/

	/**
	 * Sets a constant preventing some caching plugins from caching a page. Used on dynamic pages
	 *
	 * @access public
	 * @return void
	 */
  function activate_wc_sequential_order_numbers() {
		global $WC_Sequential_Order_Numbers;
		
		$WC_Sequential_Order_Numbers->load_class('install');
		new WC_Sequential_Order_Numbers_Install();
		
		update_option( 'wc_sequential_order_numbers_installed', 1 );
	}
	
	function nocache() {
		if (!defined('DONOTCACHEPAGE'))
			define("DONOTCACHEPAGE", "true");
		// WP Super Cache constant
	}

}
?>
