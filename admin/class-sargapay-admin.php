<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    sargapay
 * @subpackage sargapay/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    sargapay
 * @subpackage sargapay/includes
 * @author     trakadev <trakadev@protonmail.com>
 */
class Sargapay_Admin
{

    /**
     * The ID of this plugin.
     * Used on slug of plugin menu.
     * Used on Root Div ID for React too.
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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name       The name of this plugin.
     * @param      string $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;
    }

    /**
     * Add Admin Page Menu page.
     *
     * @since    1.0.0
     */
    public function add_admin_menu()
    {

        add_menu_page(
            esc_html__('Sargapay', 'sargapay'),
            esc_html__('Sargapay', 'sargapay'),
            'manage_options',
            $this->plugin_name,
            array($this, 'add_setting_root_div')
        );
    }

    /**
     * Add Root Div For React.
     *
     * @since    1.0.0
     */
    public function add_setting_root_div()
    {
        echo '<div id="' . $this->plugin_name . '"></div>';
    }

    /**
     * Register the CSS/JavaScript Resources for the admin area.
     *
     * Use Condition to Load it Only When it is Necessary
     *
     * @since    1.0.0
     */
    public function enqueue_resources()
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

        $screen              = get_current_screen();
        $admin_scripts_bases = array('toplevel_page_' . $this->plugin_name);
        if (!(isset($screen->base) && in_array($screen->base, $admin_scripts_bases)) && !str_contains($screen->base, "woocommerce_page_wc-settings")) {
            return;
        }

        wp_enqueue_style('at-grid', SARGAPAY_URL . 'assets/library/at-grid/at-grid.min.css', array(), $this->version);

        $at_grid_css_var = "
            :root{
                --at-container-sm: 540px;
                --at-container-md: 720px;
                --at-container-lg: 960px;
                --at-container-xl: 1140px;
                --at-gutter:15px;
            }
        ";
        wp_add_inline_style('at-grid', $at_grid_css_var);

        $version = $this->version;
        
        wp_enqueue_script($this->plugin_name);

        wp_print_script_tag(
            array(
                'id' => 'cardano_lib_bg',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib_bg.js'),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_print_script_tag(
            array(
                'id' => 'cardano_asm',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.asm.js'),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_print_script_tag(
            array(
                'id' => 'cardano_serialization_lib',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.js'),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_enqueue_style($this->plugin_name, SARGAPAY_URL . 'build/admin/style-settings.css', array('wp-components'), $version);

        $localize = array(
            'version' => $this->version,
            'root_id' => $this->plugin_name,
        );        
        wp_localize_script($this->plugin_name, 'wpSargapayPluginBuild', $localize);
        wp_localize_script('jquery', 'wp_ajax_sargapay_save_address', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function register_admin_resources(){
         /*Scripts dependency files*/
         $deps_file = SARGAPAY_PATH . 'build/admin/settings.asset.php';

         /*Fallback dependency array*/
         $dependency = [];
         $version = $this->version;
 
         /*Set dependency and version*/
         if (file_exists($deps_file)) {
             $deps_file  = require($deps_file);
             $dependency = $deps_file['dependencies'];
             $version    = $deps_file['version'];
         }
         wp_register_script($this->plugin_name, SARGAPAY_URL . 'build/admin/settings.js', $dependency, $version, false);
    }

    public function add_translation_json(){
        wp_set_script_translations($this->plugin_name, $this->plugin_name, plugin_dir_path( dirname(__FILE__) ) . 'languages/');
    }

    public function check_permission()
    {
        return current_user_can('manage_woocommerce');
    }

    public function sargapay_api()
    {
        $namespace = 'sargapay/v1';

        register_rest_route(
            $namespace,
            '/admin-settings',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_admin_settings'],
                'permission_callback' => [$this, 'check_permission'],
            )
        );
        register_rest_route(
            $namespace,
            '/admin-settings',
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'save_admin_settings'],
                'permission_callback' => [$this, 'check_permission'],
            )
        );
    }

    public function get_admin_settings()
    {
        # Get Settings from Payment Gateway
        $settings = WC()->payment_gateways->payment_gateways()['sargapay_cardano']->settings;

        $orders = [];

        # Get orders made with sargapay pro
        $orders_ids = wc_get_orders(array('numberposts' => -1, "payment_method" => 'sargapay_cardano'));
        foreach ($orders_ids as $order) {

            $order_id = $order->get_id();
            $order_status = $order->get_status();
            $order_date = $order->get_date_created()->date('d-m-Y');
            $sargapay_order_details = $this->get_sargapay_order_details($order_id);
            $order_total_ada = $sargapay_order_details->total;
            $order_payment_addr = $sargapay_order_details->payment_addr;
            $order_ada_prcie = $sargapay_order_details->ada_price;
            $order_currency = $sargapay_order_details->currency;

            array_push(
                $orders,
                [
                    "id" => $order_id,
                    "status" => $order_status,
                    "date" => $order_date,
                    "total" => $order_total_ada,
                    "addr" => $order_payment_addr,
                    "currency" => $order_currency,
                    "price" => $order_ada_prcie,
                ]
            );
        }

        $url = get_site_url();

        $addrs_count = $this->get_count_addresses();

        $settings = array_merge($settings, ["orders" => $orders]);

        $settings = array_merge($settings, ["url" => $url]);

        $settings = array_merge($settings, ["addrs_count" => $addrs_count]);

        return $settings;
    }

    private function get_count_addresses()
    {
        global $wpdb;
        $mpk =  WC()->payment_gateways->payment_gateways()['sargapay_cardano']->mpk;
        $mainnet_addr = 0;
        $testnet_addr = 0;
        $wpdb->get_results(
            $wpdb->prepare(
                "SELECT address_index FROM {$wpdb->prefix}wc_sargapay_address WHERE testnet=0 AND status_order='unused' AND mpk = %s",
                $mpk
            )
        );
        $mainnet_addr = $wpdb->num_rows;
        
        $wpdb->get_results(
            $wpdb->prepare(
                "SELECT address_index FROM {$wpdb->prefix}wc_sargapay_address WHERE testnet=1 AND status_order='unused' AND mpk = %s",
                $mpk
            )
        );
        $testnet_addr = $wpdb->num_rows;

        return ["mainnet" => $mainnet_addr, "testnet" => $testnet_addr];
    }

    private function get_sargapay_order_details($order_id)
    {
        global $wpdb;
        $resp = new stdClass;
        $resp->total = "Error";
        $resp->payment_addr = "Error";
        $resp->currency = "Error";
        $resp->ada_price = "Error";
        $query_result =
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT order_amount, pay_address, ada_price, currency FROM {$wpdb->prefix}wc_sargapay_address WHERE order_id=%d",
                    $order_id
                )
            );
        if ($wpdb->last_error === "" && count($query_result) > 0) {
            $resp->total = $query_result[0]->order_amount;
            $resp->payment_addr = $query_result[0]->pay_address;
            $resp->ada_price = $query_result[0]->ada_price;
            $resp->currency = $query_result[0]->currency;
        }

        return $resp;
    }

    public function validate_admin_settings(WP_REST_Request $request, WC_Payment_Gateway $gateway)
    {

        $is_sargapay_testmode = $request->get_param('testmode') ? true : false;

        # Check mpk 
        if (empty($request->get_param('mpk'))) {
            return __("Error: master public key can't be empty", 'sargapay');
        } else if (!preg_match("/^[A-Za-z0-9_]+$/", $request->get_param('mpk'))) {
            return __("Error: master public key has invalid characters", 'sargapay');
        }

        if ($is_sargapay_testmode) {

            #check apikey for testnet
            $blockfrost_test_key = $request->get_param('blockfrost_test_key');

            if (empty($blockfrost_test_key)) {
                return __('Error: blockforst testnet api key can\'t be empty', 'sargapay');
            } else {
                $api_call = $gateway->check_API_KEY(1, $blockfrost_test_key);
                if ($api_call === 1) return __('Error: blockforst testnet api call failed', 'sargapay');
            }
        } else {

            #check apikey for mainnet
            $blockfrost_key = $request->get_param('blockfrost_key');
            if (empty($blockfrost_key)) {
                return __('Error: blockforst mainnet api key can\'t be empty', 'sargapay');
            } else {
                $api_call = $gateway->check_API_KEY(0, $blockfrost_key);
                if ($api_call === 1) return __('Error: blockforst mainnet api call failed', 'sargapay');
            }
        }

        if (empty($request->get_param('confirmations'))) {
            return __('Error: confirmations can\'t be empty', 'sargapay');
        } else if (!is_numeric($request->get_param('confirmations'))) {
            return __('Error: confirmations is not a number', 'sargapay');
        } else if ($request->get_param('confirmations') < 1) {
            return __('Error: confirmations should be 1 or more', 'sargapay');
        }

        $currency = $request->get_param('currency');
        if (empty($currency)) {
            return __('Error: currency can\'t be empty', 'sargapay');
        } else if (!($currency === 'USD' || $currency === 'EUR' || $currency === 'ADA')) {
            return __('Error: currency is not supported select USD, EUR or ADA', 'sargapay');
        }

        $markup = $request->get_param('markup');
        if (!is_numeric($markup)) {
            return __('Error: markup is not a number', 'sargapay');
        }

        $time_wait = $request->get_param('time_wait');
        if (empty($time_wait)) {
            return __('Error: time to wait can\'t be empty', 'sargapay');
        } else if (!is_numeric($time_wait)) {
            return __('Error: time to wait is not a number', 'sargapay');
        }

        return 0;
    }

    public function save_admin_settings(WP_REST_Request $request)
    {
        $payment_gateway_id = 'sargapay_cardano';

        # Get an instance of the WC_Payment_Gateways object
        $payment_gateways   = WC_Payment_Gateways::instance();

        $payment_gateway    = $payment_gateways->payment_gateways()[$payment_gateway_id];

        # Validate New Settings
        $validated = $this->validate_admin_settings($request, $payment_gateway);

        if ($validated === 0) {

            # Settings > General 
            $this->update_is_sargapay_enabled($request, $payment_gateway);
            $this->update_is_testmode_enabled($request, $payment_gateway);
            $this->update_is_light_wallets_enabled($request, $payment_gateway);
            $this->update_title($request, $payment_gateway);
            $this->update_description($request, $payment_gateway);
            $this->update_confirmations($request, $payment_gateway);
            $this->update_currency($request, $payment_gateway);

            # Settings > Keys
            $this->update_mpk($request, $payment_gateway);
            $this->update_blockfrost_key($request, $payment_gateway);
            $this->update_blockfrost_test_key($request, $payment_gateway);

            # Settings > Advanced
            $this->update_markup($request, $payment_gateway);
            $this->update_time_wait($request, $payment_gateway);

            return new WP_REST_Response($this->get_admin_settings(), 200);
        } else {

            return new WP_REST_Response(array_merge($this->get_admin_settings(), ["error_msg" => $validated]), 200);
        }
    }

    /**
     * Updates Sargapay enabled status.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_is_sargapay_enabled(WP_REST_Request $request, WC_Payment_Gateway $gateway)
    {
        $is_sargapay_enabled = $request->get_param('enabled');

        if (null === $is_sargapay_enabled) {
            return;
        }

        $gateway->update_option('enabled', $is_sargapay_enabled ? 'yes' : 'no');
    }

    /**
     * Updates Sargapay testmode status.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_is_testmode_enabled(WP_REST_Request $request, WC_Payment_Gateway $gateway)
    {
        $is_testmode_enabled = $request->get_param('testmode');
        $gateway->update_option('testmode', $is_testmode_enabled ? 'yes' : 'no');
    }

    /**
     * Updates Sargapay light Wallets status.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_is_light_wallets_enabled(WP_REST_Request $request, WC_Payment_Gateway $gateway)
    {
        $is_light_wallets_enabled = $request->get_param('lightWallets');
        $gateway->update_option('lightWallets', $is_light_wallets_enabled ? 'yes' : 'no');
    }

    /**
     * Updates Sargapay title status.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_title(WP_REST_Request $request, WC_Payment_Gateway $gateway)
    {
        $title = $request->get_param('title');
        $gateway->update_option('title', $title);
    }

    /**
     * Updates descriptions.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_description(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $description = $request->get_param('description');
        $gateway->update_option('description', $description);
    }
    /**
     * Updates confirmations needed to validate a payment.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_confirmations(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $confirmations = $request->get_param('confirmations');
        $gateway->update_option('confirmations', $confirmations);
    }
    /**
     * Updates confirmations needed to validate a payment.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_currency(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $currency = $request->get_param('currency');
        $gateway->update_option('currency', $currency);
    }
    /**
     * Updates master public key.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_mpk(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $mpk = $request->get_param('mpk');

        if (empty($mpk) || !preg_match("/^[A-Za-z0-9_]+$/", $mpk)) {
            return false;
        }

        $gateway->update_option('mpk', $mpk);

        return true;
    }
    /**
     * Updates blockfrost key.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     * @return bool 
     */
    private function update_blockfrost_key(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $blockfrost_key = $request->get_param('blockfrost_key');

        if (empty($blockfrost_key)) {
            return false;
        }

        $api_call = $gateway->check_API_KEY(0, $blockfrost_key);

        if ($api_call === 1) {
            return false;
        }

        $gateway->update_option('blockfrost_key', $blockfrost_key);

        return true;
    }
    /**
     * Updates blockfrost testnet key.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_blockfrost_test_key(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $blockfrost_test_key = $request->get_param('blockfrost_test_key');


        if (empty($blockfrost_test_key)) {
            return false;
        }

        $api_call = $gateway->check_API_KEY(1, $blockfrost_test_key);

        if ($api_call === 1) {
            return false;
        }

        $gateway->update_option('blockfrost_test_key', $blockfrost_test_key);

        return true;
    }

    /**
     * Updates markup.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_markup(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $markup = $request->get_param('markup');
        $gateway->update_option('markup', $markup);
    }

    /**
     * Updates time wait.
     *
     * @param WP_REST_Request $request Request object.
     * @param WC_Payment_Gateway $gateway WC Payment Gateway object.
     */
    private function update_time_wait(WP_REST_Request $request,  WC_Payment_Gateway $gateway)
    {
        $time_wait = $request->get_param('time_wait');
        $gateway->update_option('time_wait', $time_wait);
    }

    // Load JS to Gen Cardano Address
    public function load_gen_addressjs()
    {
        wp_print_script_tag(
            array(
                'id' => 'cardano_lib_bg',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib_bg.js'),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_print_script_tag(
            array(
                'id' => 'cardano_asm',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.asm.js'),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_print_script_tag(
            array(
                'id' => 'cardano_serialization_lib',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.js',),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_print_script_tag(
            array(
                'id' => 'bech32',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/bech32.js'),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_print_script_tag(
            array(
                'id' => 'gen_address',
                'src' => esc_url(SARGAPAY_URL . 'paymentGateway/cardano/assets/js/main.js'),
                'defer' => true,
                'type' => 'module'
            )
        );

        wp_localize_script('jquery', 'wp_ajax_sargapay_save_address', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }
}
