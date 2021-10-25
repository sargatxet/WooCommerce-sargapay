<?php
/*
 * Plugin Name: Sargatxet ADA Pay Gateway for WooCommerce
 * Plugin URI: https://github.com/sargatxet/WooCommerce-sargapay/
 * Description: Recive payments using Cardano ADA
 * Author: SargaTxet
 * Author URI: https://cardano.sargatxet.cloud/
 * Text Domain: sargapay-plugin
 * Domain Path: /languages
 * Version: 0.1.0
 * License: MIT
 * License URI: https://github.com/sargatxet/WooCommerce-sargapay/blob/main/LICENSE
 */


register_activation_hook(__FILE__, 'SARGAPAY_activate');
register_deactivation_hook(__FILE__, 'SARGAPAY_deactivate');
add_action('plugins_loaded', 'sargapay_plugin_init_gateway_class');


add_filter('cron_schedules', 'SARGAPAY_cron_hook');
add_action('SARGAPAY_cron_hook', 'check_confirmations_cardano');


// Actions after plugin is activated
function SARGAPAY_activate()
{
    // Check if woocommerce is active.
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }
    //Register verification 
    if (!wp_next_scheduled('SARGAPAY_cron_hook')) {
        wp_schedule_event(time(), 'every_ten_minutes', 'SARGAPAY_cron_hook');
    }
    // Create DB for Addresses, if it doesn't exist.
    require_once(plugin_basename("TKAP_Create_db.php"));
    SARGAPAY_create_address_table();
}

// Register 10 min interval for cronjobs
function SARGAPAY_cron_hook($schedules)
{
    $schedules['every_ten_minutes'] = array(
        'interval'  => 60 * 10,
        'display'   => __('Every 10 Minutes', 'sargapay-plugin')
    );
    return $schedules;
}

// Hook for transactions check, that'll fire every 10 minutes
function check_confirmations_cardano()
{
    $check_confirm = new ConfirmPayment();
    $check_confirm->check_all_pendding_orders();
}

/* Deactivate Actions
 * Cronjobs
 */
function SARGAPAY_deactivate()
{
    // REMOVE CRONJOB to verify paymanets
    wp_clear_scheduled_hook('SARGAPAY_cron_hook');
}


// PLugin Init
function sargapay_plugin_init_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) {
        deactivate_plugins(plugin_basename(__FILE__));
        return;
    }

    require_once(plugin_basename("TKAP_GenerateQR.php"));
    require_once(plugin_basename("TKAP_ConfirmPayment.php"));
    require_once(plugin_basename("TKAP_send_email.php"));
    require_once(plugin_basename("TKAP_SaveAddress.php"));
    require_once(plugin_basename('TKAP_Gateway.php'));

    // Init Plugin Class
    add_filter('woocommerce_payment_gateways', 'sargapay_plugin_add_gateway_class');

    // Add Type Module to Javascript tags
    add_filter('script_loader_tag', 'add_type_attribute', 10, 3);

    // Load Transalations
    add_action('init', 'SARGAPAY_load_textdomain');

    // Add QR and Payment Address to thank you page
    add_filter('woocommerce_thankyou_order_received_text', 'plugin_thank_you_text', 20, 2);

    // Ajax Admin Panel
    if (is_admin()) {
        add_action('admin_enqueue_scripts', 'admin_load_gen_addressjs');
        add_action('wp_ajax_save_address', 'save_address');
    }

    // Ajax visitors
    add_action('wp_enqueue_scripts', 'load_wp_gen_address');
    add_action('wp_ajax_nopriv_save_address', 'save_address');

    // Woocommerce Mail QR and Payment Address
    add_action('woocommerce_email_before_order_table', 'SARGAPAY_add_content_wc_order_email', 20, 4);

    // Show cancel time for orders without payment
    add_action('woocommerce_view_order', 'view_order_cancel_notice');

    // Filter to add link to settings
    add_filter('plugin_action_links_sargapay-plugin/TKAP_plugin.php', 'SARGAPAY_settings_link');

    // Add Payment Method to Woocommerce
    function sargapay_plugin_add_gateway_class($gateways)
    {
        $gateways[] = 'SARGAPAY_WC_Gateway';
        return $gateways;
    }

    // Add Settings Link to Installed Plugins Page
    function SARGAPAY_settings_link($links)
    {
        // Build and escape the URL.
        $url = esc_url(add_query_arg(
            array('page' =>
            'wc-settings', 'tab' => 'checkout', 'section' => 'sargapay-plugin'),
            get_admin_url() . 'admin.php'
        ));
        // Create the link.
        $settings_link = "<a href='$url'>" . __('Settings') . '</a>';
        // Adds the link to the end of the array.
        array_push(
            $links,
            $settings_link
        );
        return $links;
    }

    // Load JS to Gen Cardano Address
    function admin_load_gen_addressjs()
    {
        wp_enqueue_script('gen_addressjs', plugins_url('/js/main.js', __FILE__), array('jquery', 'cardano_serialization_lib', 'cardano_asm', 'cardano_lib_bg', 'bech32'));
        wp_enqueue_script('cardano_serialization_lib', plugins_url('/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.js', __FILE__), array('cardano_asm'));
        wp_enqueue_script('cardano_asm', plugins_url('/js/cardano-serialization-lib-asmjs/cardano_serialization_lib.asm.js', __FILE__), array('cardano_lib_bg'));
        wp_enqueue_script('cardano_lib_bg', plugins_url('/js/cardano-serialization-lib-asmjs/cardano_serialization_lib_bg.js', __FILE__), false);
        wp_enqueue_script('bech32', plugins_url('/js/bech32.js', __FILE__), false);
        wp_localize_script('gen_addressjs', 'wp_ajax_save_address_vars', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }
    // Load JS to Gen Cardano Address when a loged in user visit the site
    function load_wp_gen_address()
    {
        wp_enqueue_script('wp_gen_address', plugins_url('/js/main_index.js', __FILE__), array('jquery'));
        wp_localize_script('wp_gen_address', 'wp_ajax_save_address_vars', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
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
            'wp_gen_address' === $handle
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
    function SARGAPAY_load_textdomain()
    {
        load_plugin_textdomain('sargapay-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    /** Add QR and Payment address in Thank You Page    
     ** Add Warning header if testmode is on
     */
    function plugin_thank_you_text($thank_you_title, $order)
    {
        if (isset($order)) {
            if ($order->get_payment_method() === "sargapay-plugin") {
                $message = '<div style="font-weight:bold; text-align:center; color:white; background:black;">' . esc_html(__('Remember that you have 24 hours to pay for your order before it\'s automatically canceled.', 'sargapay-plugin')) . '</div>';
                $order_id = $order->get_id();
                global $wpdb;
                $table = $wpdb->prefix . "wc_sarga_address";
                $query_address = $wpdb->get_results("SELECT pay_address, order_amount, testnet FROM $table WHERE order_id=$order_id");
                //ERROR DB
                if ($wpdb->last_error) {
                    //LOG Error             
                    write_log($wpdb->last_error);
                } else if (count($query_address) === 0) {
                    $message = "<p>" . esc_html(__('ERROR PLEASE CONTACT ADMIN TO PROCCED WITH THE ORDER', 'sargapay-plugin')) . "</p>";
                    write_log("ERROR DB query empty in Thank You Page ");
                    return $thank_you_title . "<br>" . $message . '<br><br>';
                } else {
                    if ($query_address[0]->testnet) {
                        $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay-plugin'));
                        echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                    }
                    // Get order amount in ada
                    $total_ada = $query_address[0]->order_amount;
                    // Get payment address
                    $payment_address = $query_address[0]->pay_address;
                    $qr = new GenerateQR();
                    echo "<style>
                .modal_tk_plugin {
                    display: none; 
                    position: fixed; 
                    z-index: 1; 
                    padding-top: 100px; 
                    left: 0;
                    top: 0;
                    width: 100%; 
                    height: 100%; 
                    overflow: auto; 
                    background-color: rgb(0,0,0); 
                    background-color: rgba(0,0,0,0.4); 
                  }
                  .modal_tk_plugin_content {
                    background-color: #fefefe;
                    margin: auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 80%;
                  }
                  .close_tk_plugin {
                    color: #aaaaaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                  }
                  
                  .close_tk_plugin:hover,
                  .close_tk_plugin:focus {
                    color: #000;
                    text-decoration: none;
                    cursor: pointer;
                  }                  
                  </style>";
                    // Qr Button
                    echo
                    "<div id='copy_modal' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'>" . esc_html(__('Payment Address Copied!', 'sargapay-plugin')) . "</p>
                    </div>
                </div>";
                    echo "<div style='text-align:center; font-weight:bold;'><h4>"
                        . esc_html(__('Payment Address', 'sargapay-plugin')) .
                        "</h4><p id='pay_add_p_field_tk_plugin' style='width:100%; overflow-wrap:anywhere;'>" . esc_html($payment_address) . "</p>"
                        . $qr->generate($payment_address) .
                        '</div>';
                    // Amount Button     
                    echo
                    "<div id='copy_modal_amount' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'>" . esc_html(__('Amount Copied!', 'sargapay-plugin')) . "</p>
                    </div>
                </div>";
                    echo '<p style="text-align: center;"><b>' . esc_html(__('ADA Total', 'sargapay-plugin')) . '</b><br><span id="pay_amount_span_field_tk_plugin">' . esc_html($total_ada) . '</span></p>' .
                        "<div style='display:flex; justify-content: space-evenly; margin:15px;'><button class='button' id='pay_add_button_field_tk_plugin'>" . esc_html(__('Copy Payment Address', 'sargapay-plugin')) . "</button><button class='button' id='pay_amount_button_field_tk_plugin'>" . esc_html(__('Copy Amount', 'sargapay-plugin')) . "</button></div>";

                    // SEND EMAIL  
                    // Create QR PNG FILE
                    $url_img = $qr->QR_URL($payment_address);
                    // Email config
                    $email = $order->get_billing_email();
                    $subject = __("Payment Instructions ", 'sargapay-plugin') . get_bloginfo('name');
                    $file_name = $payment_address . ".png";
                    $testnet_bool = $query_address[0]->testnet;
                    // Email Sent                   
                    send_email_woocommerce_style($email, $subject, $testnet_bool,  $total_ada, $payment_address, $url_img, $file_name,);
                    return $thank_you_title . "<br>" . $message . '<br><br>';
                }
            }
        }
        return $thank_you_title;
    }

    function view_order_cancel_notice($order_id)
    {
        //add pending status and show confirmations
        $order = wc_get_order($order_id);
        if ($order->get_payment_method() === "sargapay-plugin") {
            if (
                $order->get_status() === "on-hold"
            ) {
                global $wpdb;
                $table = $wpdb->prefix . "wc_sarga_address";
                $query_address = $wpdb->get_results("SELECT pay_address, order_amount, testnet FROM $table WHERE order_id=$order_id");
                //LOG ERROR DB
                if ($wpdb->last_error) {
                    //LOG Error             
                    write_log($wpdb->last_error);
                } else if (count($query_address) === 0) {
                    echo "<p>" . __('ERROR PLEASE CONTACT THE ADMIN TO PROCCED WITH THE ORDER', 'sargapay-plugin') . "</p>";
                    write_log("Emprty Query result in account page order");
                } else {
                    if ($query_address[0]->testnet) {
                        $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS ", 'sargapay-plugin'));
                        echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                    }
                    // Get order amount in ada
                    $total_ada = $query_address[0]->order_amount;
                    // Get payment address
                    $payment_address = $query_address[0]->pay_address;
                    $date_created_dt = $order->get_date_created();
                    // Get the timezone
                    $timezone = $date_created_dt->getTimezone();
                    // Get the timestamp in seconds
                    $date_created_ts = $date_created_dt->getTimestamp();
                    // Get current WC_DateTime object instance
                    $now_dt = new WC_DateTime();
                    // Set the same time zone
                    $now_dt->setTimezone($timezone);
                    // Get the current timestamp in seconds
                    $now_ts = $now_dt->getTimestamp();
                    // 24hours in seconds            
                    $twenty_four_hours = 24 * 60 * 60;
                    // Get the difference (in seconds)
                    $diff_in_seconds = $now_ts - $date_created_ts;
                    $seconds_until_cancel = $twenty_four_hours - $diff_in_seconds;
                    $time_until_cancel = gmdate("H:i:s", $seconds_until_cancel);
                    $text = esc_html(__("Tienes para realizar la transacción ", 'sargapay-plugin'));
                    $qr = new GenerateQR();
                    echo '<p>' . $text . $time_until_cancel . '</p>';
                    echo '<p style="text-align: center;"><b>' . esc_html(__('Payment Address', 'sargapay-plugin')) . '</b><br>' . $payment_address .
                        $qr->generate($payment_address) .
                        '</p>';
                    echo '<p style="text-align: center;"><b>' . esc_html(__('Total ADA', 'sargapay-plugin')) . '</b><br>' . $total_ada . '</p>';
                }
            } else if (
                $order->get_status() === "cancelled"
            ) {
                echo esc_html(__("24 hours have passed and your order was canceled, the payment address is no longer valid.", 'sargapay-plugin'));
            }
        }
    }
    // we add data protocol to render qr img on emails
    add_filter('kses_allowed_protocols', function ($protocols) {
        $protocols[] = 'data';
        return $protocols;
    });

    function SARGAPAY_add_content_wc_order_email($order, $sent_to_admin, $plain_text, $email)
    {
        if ($email->id == 'customer_on_hold_order') {
            if ($order->get_payment_method() === "sargapay-plugin") {
                if ($plain_text === false) {
                    echo "<p>." . esc_html(__('Instructions for payment will be send soon!', 'sargapay-plugin')) . "</p>";
                } else {
                    echo esc_html(__("Instructions for payment will be send soon!\n", 'sargapay-plugin'));
                }
            }
        }
    }
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
