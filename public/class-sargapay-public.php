<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    sargapay
 * @subpackage sargapay/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    sargapay
 * @subpackage sargapay/Public
 * @author     trakadev <trakadev@protonmail.com>
 */
class Sargapay_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Unique ID for this class.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $id    The ID of this class.
	 */
	private $id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->id     = $this->plugin_name . '-public';
	}

	/**
	 * Register the JavaScript and stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_public_resources()
	{
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_React_Plugin_Boilerplate_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_React_Plugin_Boilerplate_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style($this->id,  SARGAPAY_URL . 'public/css/sargapay-public.css', array(), $this->version, 'all');
		wp_enqueue_script($this->id, SARGAPAY_URL . 'public/js/sargapay-public.js', array('jquery'), $this->version, false);
		if ((is_checkout() && !empty(is_wc_endpoint_url('order-received'))) || is_account_page()) {
			wp_enqueue_style('modals_thanks', SARGAPAY_URL . 'paymentGateway/cardano/assets/css/modal_thank_you.css', array(), $this->version);
			wp_enqueue_style('wallet_btn', SARGAPAY_URL . 'paymentGateway/cardano/assets/css/wallets_btns.css', array(), $this->version);
		}
	}

	// Load JS to Gen Cardano Address when a loged in user visit the site
	public function load_gen_address()
	{
		wp_print_script_tag(
			array(
				'id' => 'wp_gen_address',
				'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/main_index.js'),
				'defer' => true,
				'type' => 'module'
			)
		);

		include_once(ABSPATH . 'wp-includes/pluggable.php');
		if (is_user_logged_in()) {

			wp_localize_script('jquery', 'wp_ajax_sargapay_save_address', array(
				'ajax_url' => admin_url('admin-ajax.php')
			));
			wp_localize_script('jquery', 'wp_ajax_sargapay_get_settings_vars', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'noWallet_txt' => esc_html(__('Cardano Wallet Not Found!', "sargapay")),
				'unknown_txt' => esc_html(__('Something Went Wrong!', 'sargapay')),
				'paid_txt' => esc_html(__('Paid', 'sargapay')),
				'is_user_logged_in' => is_user_logged_in(),
				'error_wrong_network_txt' => esc_html(__('Wrong Network, Please Select the Correct Network', 'sargapay'))
			));
		} else {
			wp_localize_script('jquery', 'wp_ajax_nopriv_sargapay_save_address', array(
				'ajax_url' => admin_url('admin-ajax.php')
			));
			wp_localize_script('jquery', 'wp_ajax_nopriv_sargapay_get_settings_vars', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'noWallet_txt' => esc_html(__('Cardano Wallet Not Found!', "sargapay")),
				'unknown_txt' => esc_html(__('Something Went Wrong!', 'sargapay')),
				'paid_txt' => esc_html(__('Paid', 'sargapay')),
				'is_user_logged_in' => is_user_logged_in(),
				'error_wrong_network_txt' => esc_html(__('Wrong Network, Please Select the Correct Network', 'sargapay'))
			));
		}

		if ((is_checkout() && !empty(is_wc_endpoint_url('order-received'))) || is_account_page()) {
			wp_print_script_tag(
				array(
					'id' => 'wp_sarga_hot_wallets',
					'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/hotWallets.js'),
					'defer' => true,
					'type' => 'module'
				)
			);

			wp_enqueue_script('wp_sarga_alerts', SARGAPAY_URL . 'paymentGateway/cardano/assets/js/sweetalert2.all.min.js', array('jquery'));
		}

		if (is_account_page()) {
			wp_enqueue_script('wp_sarga_countDown',  SARGAPAY_URL . 'paymentGateway/cardano/assets/js/countDown.js', array('jquery'), $this->version);
		}
	}
}
