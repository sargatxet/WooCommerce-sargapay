<?php
/*
 * Plugin Name: Sargapay
 * Plugin URI: https://github.com/sargatxet/WooCommerce-sargapay/
 * Description: Recive payments using Cardano ADA
 * Author: Sargatxet
 * Author URI: https://cardano.sargatxet.cloud/
 * Text Domain: sargapay
 * Domain Path: /languages
 * Version: 1.0.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/*
    SargaPay. Cardano gateway plug-in for Woocommerce. 
    Copyright (C) 2021  Sargatxet Pools

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


register_activation_hook(__FILE__, 'sargapay_activate');
register_deactivation_hook(__FILE__, 'sargapay_deactivate');
add_action('plugins_loaded', 'sargapay_plugin_init_gateway_class');


add_filter('cron_schedules', 'sargapay_cron_hook');
add_action('sargapay_cron_hook', 'sargapay_check_confirmations_cardano');


// Actions after plugin is activated
function sargapay_activate()
{
    // Check if woocommerce is active.
    if (!class_exists('WC_Payment_Gateway')) {
        die(__('Plugin NOT activated: WooCommerce is required', 'sargapay'));
    }
    if (PHP_VERSION_ID <= 70399) {
        die(__('Plugin NOT activated: Minimun PHP version required  is 7.4', 'sargapay'));
    }
    //Register verification 
    if (!wp_next_scheduled('sargapay_cron_hook')) {
        wp_schedule_event(time(), 'every_ten_minutes', 'sargapay_cron_hook');
    }
    // Create DB for Addresses, if it doesn't exist.
    require_once(plugin_basename("sargapay-createDB.php"));
    sargapay_create_address_table();
}

// Register 10 min interval for cronjobs
function sargapay_cron_hook($schedules)
{
    $schedules['every_ten_minutes'] = array(
        'interval'  => 60 * 10,
        'display'   => __('Every 10 Minutes', 'sargapay')
    );
    return $schedules;
}

// Hook for transactions check, that'll fire every 10 minutes
function check_confirmations_cardano()
{
    $check_confirm = new Sargapay_ConfirmPayment();
    $check_confirm->sargapay_check_all_pendding_orders();
}

/* Deactivate Actions
 * Cronjobs
 */
function sargapay_deactivate()
{
    // REMOVE CRONJOB to verify paymanets
    wp_clear_scheduled_hook('sargapay_cron_hook');
}


// PLugin Init
function sargapay_plugin_init_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) {
        deactivate_plugins(plugin_basename(__FILE__));
        return;
    }

    require_once(plugin_basename("sargapay-gateway.php"));
    require_once(plugin_basename("sargapay-thank-you-page.php"));
    require_once(plugin_basename("sargapay-cancel-order.php"));
    require_once(plugin_basename("sargapay-generateQR.php"));
    require_once(plugin_basename("sargapay-send-email.php"));
    require_once(plugin_basename("sargapay-settings.php"));
    require_once(plugin_basename("sargapay-save-address.php"));
    require_once(plugin_basename("sargapay-confirm-payment.php"));

    // Init Plugin Class
    add_filter('woocommerce_payment_gateways', 'sargapay_plugin_add_gateway_class');

    // Add Settings link
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sargapay_settings_link');

    // Add Type Module to Javascript tags
    add_filter('script_loader_tag', 'add_type_attribute', 10, 3);

    // Load Transalations
    add_action('init', 'sargapay_load_textdomain');

    // Add QR and Payment Address to thank you page
    add_filter('woocommerce_thankyou_order_received_text', 'sargapay_thank_you_text', 20, 2);

    // Ajax Admin Panel
    if (is_admin()) {
        add_action('admin_enqueue_scripts', 'sargapay_admin_load_gen_addressjs');
        add_action('wp_ajax_save_address', 'sargapay_save_address');
    }

    // Ajax visitors
    add_action('wp_enqueue_scripts', 'sargapay_load_wp_gen_address');
    add_action('wp_ajax_nopriv_save_address', 'sargapay_save_address');

    # Get APIKEY and Network for hotwallets
    add_action('wp_ajax_nopriv_get_settings_vars', 'sargapay_get_settings_vars');
    add_action('wp_ajax_get_settings_vars', 'sargapay_get_settings_vars');

    // Woocommerce Mail QR and Payment Address
    add_action('woocommerce_email_before_order_table', 'sargapay_add_content_wc_order_email', 20, 4);

    // Show cancel time for orders without payment
    add_action('woocommerce_view_order', 'sargapay_view_order_cancel_notice');

    add_action('init', 'sargapay_register_styles');

    add_action('wp_enqueue_scripts', 'sargapay_enqueue_styles');

    function sargapay_register_styles()
    {
        wp_register_style('wallet_btn', plugins_url('/assets/css/walletsBtns.css', __FILE__));
        wp_register_style('modals_thanks', plugins_url('/assets/css/modalThankYou.css', __FILE__));
    }


    function sargapay_enqueue_styles()
    {
        #check if is thankyou page
        if (is_checkout() && !empty(is_wc_endpoint_url('order-received'))) {
            wp_enqueue_style('modals_thanks');
        }
        if ((is_checkout() && !empty(is_wc_endpoint_url('order-received'))) || is_account_page()) {
            wp_enqueue_style('wallet_btn');
        }
    }

    // Load JS to Gen Cardano Address
    function sargapay_admin_load_gen_addressjs()
    {
        wp_enqueue_script('gen_addressjs', plugins_url('assets/js/main.js', __FILE__), array('jquery', 'cardano_serialization_lib', 'cardano_asm', 'cardano_lib_bg', 'bech32'));
        wp_enqueue_script('cardano_serialization_lib', plugins_url('assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.js', __FILE__), array('cardano_asm'));
        wp_enqueue_script('cardano_asm', plugins_url('assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.asm.js', __FILE__), array('cardano_lib_bg'));
        wp_enqueue_script('cardano_lib_bg', plugins_url('assets/js/cardano-serialization-lib-asmjs/cardano_serialization_lib_bg.js', __FILE__), false);
        wp_enqueue_script('bech32', plugins_url('assets/js/bech32.js', __FILE__), false);
        wp_localize_script('gen_addressjs', 'wp_ajax_save_address_vars', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    // Load JS to Gen Cardano Address when a loged in user visit the site
    function sargapay_load_wp_gen_address()
    {
        wp_enqueue_script('wp_gen_address', plugins_url('assets/js/main_index.js', __FILE__), array('jquery', 'wp-i18n'));
        wp_localize_script('wp_gen_address', 'wp_ajax_save_address_vars', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));

        wp_localize_script('wp_gen_address', 'wp_ajax_nopriv_get_settings_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'noWallet_txt' => esc_html(__('Cardano Wallet Not Found!', "sargapay-plugin")),
            'unknown_txt' => esc_html(__('Something Went Wrong!', 'sargapay-plugin')),
            'paid_txt' => esc_html(__('Paid', 'sargapay-plugin')),
            'is_user_logged_in' => is_user_logged_in(),
            'error_wrong_network_txt' => esc_html(__('Wrong Network, Please Select the Correct Network', 'sargapay-plugin'))
        ));

        if ((is_checkout() && !empty(is_wc_endpoint_url('order-received'))) || is_account_page()) {
            wp_enqueue_script('wp_sarga_hot_wallets', plugins_url('assets/js/hotWallets.js', __FILE__), array('jquery'));
            wp_enqueue_script('wp_sarga_alerts', "//cdn.jsdelivr.net/npm/sweetalert2@11", array('jquery'));
        }

        if(is_account_page()) {
            wp_enqueue_script('sargapay_countdown', plugins_url('assets/js/countDown.js', __FILE__), array('jquery'));
        }
    }

    // Add Type = Module to js 
    function add_type_attribute($tag, $handle, $src)
    {
        if (
            'bech32' === $handle ||
            'cardano_lib_bg' === $handle ||
            'cardano_asm' === $handle ||
            'cardano_serialization_lib' === $handle ||
            'gen_addressjs' === $handle ||
            'wp_gen_address' === $handle ||
            'wp_sarga_hot_wallets' === $handle
        ) {
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
            return $tag;
        } else {
            return $tag;
        }
    }
    /**
     * Load plugin textdomain.
     */
    function sargapay_load_textdomain()
    {
        load_plugin_textdomain('sargapay', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    // we add data protocol to render qr img on emails
    add_filter('kses_allowed_protocols', function ($protocols) {
        $protocols[] = 'data';
        return $protocols;
    });
}

//Activate Logs
if (!function_exists('write_log')) {
    function write_log($log)
    {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}