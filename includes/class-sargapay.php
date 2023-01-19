<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    sargapay
 * @subpackage sargapay/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    sargapay
 * @subpackage sargapay/includes
 * @author     trakadev <trakadev@protonmail.com>
 */
class Sargapay
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Sargapay_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		$this->version = SARGAPAY_VERSION;
		$this->plugin_name = 'sargapay';

		$this->load_dependencies();
		$this->define_include_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Sargapay_Loader. Orchestrates the hooks of the plugin.
	 * - Sargapay_Admin. Defines all hooks for the admin area.
	 * - Sargapay_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * Plugin Core Functions.
		 */
		require_once SARGAPAY_PATH . 'includes/functions.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once SARGAPAY_PATH . 'includes/class-sargapay-loader.php';

		/**
		 * The class responsible for defining all actions that occur in both admin and public-facing areas.
		 */
		require_once SARGAPAY_PATH . 'includes/class-sargapay-include.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once SARGAPAY_PATH . 'admin/class-sargapay-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once SARGAPAY_PATH . 'public/class-sargapay-public.php';

		$this->loader = new Sargapay_Loader();

		# Payment Gateway Functions
		# Cardano	
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-save-address.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/class-sargapay-generateQR.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-save-address.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-createDB.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-send-email.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-settings.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-thank-you-page.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-cancel-order.php';
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-cardano-currency.php';

		add_action('plugins_loaded', [$this, 'sargapay_init_gateway_class']);
		// Add QR and Payment Address to thank you page
		add_filter('woocommerce_thankyou_order_received_text', 'sargapay_thank_you_text', 20, 2);
		add_filter('woocommerce_currencies', 'add_sarga_cardano_currency');
		add_filter('woocommerce_currency_symbol', 'add_sarga_cardano_currency_symbol', 10, 2);
	}


	function sargapay_init_gateway_class()
	{
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		# Deactivate Plugin if Woocommerce is not installed
		if (!class_exists('WC_Payment_Gateway')) {
			deactivate_plugins(plugin_basename(__FILE__));
			return;
		}

		// Init Plugin Class
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/class-sargapay-gateway.php';
		add_filter('woocommerce_payment_gateways', 'sargapay_add_gateway_class');

		// Add Settings link
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-settings.php';
		add_filter('plugin_action_links_' . SARGAPAY_PATH, 'sargapay_settings_link');

		// we add data protocol to render qr img on emails
		add_filter('kses_allowed_protocols', function ($protocols) {
			$protocols[] = 'data';
			return $protocols;
		});

		function sargapay_add_gateway_class($gateways)
		{
			$gateways[] = 'Sargapay_Cardano_Gateway';
			return $gateways;
		}
	}

	/**
	 * Register all of the hooks related to both admin and public-facing areas functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_include_hooks()
	{

		$plugin_admin = new Sargapay_Include($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('init', $plugin_admin, 'init_something');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new Sargapay_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
		$this->loader->add_action('wp_register_script', $plugin_admin, 'register_admin_resources');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_resources');
		$this->loader->add_action('init', $plugin_admin, 'register_admin_resources');
		$this->loader->add_action('init', $plugin_admin, 'add_translation_json');

		// Register API for Settings on FrontEnd Admin Dashboard
		$this->loader->add_action('rest_api_init', $plugin_admin, 'sargapay_api');

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, "load_gen_addressjs");

		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-save-address.php';
		add_action('wp_ajax_sargapay_save_address', 'sargapay_save_address');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Sargapay_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_public_resources');

		// Ajax visitors
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'load_gen_address');

		include_once(ABSPATH . 'wp-includes/pluggable.php');
		# Get APIKEY and Network for light wallets
		if (is_user_logged_in()) {
			require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-save-address.php';
			add_action('wp_ajax_sargapay_save_address',  'sargapay_save_address');
			require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-settings.php';
			add_action('wp_ajax_sargapay_get_settings_vars',  'sargapay_get_settings_vars');
		} else {
			require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-save-address.php';
			add_action('wp_ajax_nopriv_sargapay_save_address',  'sargapay_save_address');
			require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-settings.php';
			add_action('wp_ajax_nopriv_sargapay_get_settings_vars', 'sargapay_get_settings_vars');
		}

		// Woocommerce Mail QR and Payment Address
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-send-email.php';
		add_action('woocommerce_email_before_order_table', 'sargapay_add_content_wc_order_email', 20, 4);

		// Show cancel time for orders without payment
		require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-cancel-order.php';
		add_action('woocommerce_view_order', 'sargapay_view_order_cancel_notice');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Sargapay_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
