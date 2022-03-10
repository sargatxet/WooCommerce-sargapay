<?php
/*
 * Plugin Name: Sargapay
 * Plugin URI: https://github.com/sargatxet/WooCommerce-sargapay/
 * Description: Receive payments using Cardano ADA and Harmony ONE
 * Author: Sargatxet
 * Author URI: https://sargatxet.cloud/
 * Text Domain: sargapay-plugin
 * Domain Path: /languages
 * Version: 1.0.0
 * Requires PHP: 7.3
 * License: MIT
 * License URI: https://github.com/sargatxet/WooCommerce-sargapay/blob/main/LICENSE
 */

/*
    SargaPay. Harmony gateway plug-in for Woocommerce. 
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

require "vendor/autoload.php";

register_activation_hook(__FILE__, 'sargapay_activate');
register_deactivation_hook(__FILE__, 'sargapay_deactivate');
add_action('plugins_loaded', 'sargapay_plugin_init_gateway_class');
add_filter('cron_schedules', 'sargapay_cron_hook');

# Register verification 
if (!wp_next_scheduled('sargapay_cron_hook')) {
    wp_schedule_event(time(), 'every_ten_minutes', 'sargapay_cron_hook');
} else {
    wp_schedule_event(time(), 'every_ten_minutes', 'sargapay_cron_hook');
}

add_action('sargapay_cron_hook', 'sarga_check_confirmations');

// Register 10 min interval for cronjobs
function sargapay_cron_hook($schedules)
{
    $schedules['every_ten_minutes'] = array(
        'interval'  => 60 * 10,
        'display'   => __('Every 10 Minutes', 'sargapay-plugin')
    );
    return $schedules;
}

// Hook for transactions check, that'll fire every 10 minutes
function sarga_check_confirmations()
{
    new Sargapay_ONE;
    new Sargapay_ADA;
    Sargapay_ONE::validar_pagos();
    Sargapay_ADA::check_all_pendding_orders();
}

// Actions after plugin is activated
function sargapay_activate()
{
    // Check if woocommerce is active.
    if (!class_exists('WC_Payment_Gateway')) {
        die(__('Plugin NOT activated: WooCommerce is required', 'sargapay-plugin'));
    }
    if (PHP_VERSION_ID <= 70299) {
        die(__('Plugin NOT activated: Minimum PHP version required is 7.3', 'sargapay-plugin'));
    }
    // Create DB for Addresses, if it doesn't exist.
    require_once(plugin_basename("/includes/class-sargapay-dbs.php"));
    $dbs = new Sargapay_DBs();
    $dbs->run();
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
    # General 
    require_once(plugin_basename("/includes/class-sargapay-qr.php"));
    require_once(plugin_basename("/includes/class-sargapay-send-email.php"));
    require_once(plugin_basename("/includes/class-sargapay-api.php"));
    require_once(plugin_basename("/includes/class-sargapay-dbs.php"));
    # Harmony ONE
    require_once(plugin_basename("/includes/one/class-sargapay-one.php"));
    require_once(plugin_basename("/includes/one/class-sargapay-one-gateway.php"));
    # Cardano ADA
    require_once(plugin_basename("/includes/ada/class-sargapay-ada.php"));
    require_once(plugin_basename("/includes/ada/class-sargapay-save-address.php"));
    require_once(plugin_basename("/includes/ada/class-sargapay-ada-gateway.php"));

    // Init Plugin Class
    add_filter('woocommerce_payment_gateways', 'sargapay_one_plugin_add_gateway_class');
    add_filter('woocommerce_payment_gateways', 'sargapay_ada_plugin_add_gateway_class');

    // Add Settings link
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sargapay_plugin_settings_link');

    // Add Type Module to Javascript tags
    add_filter('script_loader_tag', 'add_type_attribute', 10, 3);

    // Load Transalations
    add_action('init', 'sargapay_load_textdomain');

    // Add QR and Payment Address to thank you page
    add_filter('woocommerce_thankyou_order_received_text', 'sargapay_one_thank_you_text', 20, 2);
    add_filter('woocommerce_thankyou_order_received_text', 'sargapay_ada_thank_you_text', 20, 2);

    // Ajax Admin Panel
    if (is_admin()) {
        add_action('admin_enqueue_scripts', 'admin_load_gen_addressjs');
        add_action('wp_ajax_save_address', 'save_address');
    }

    // Ajax visitors
    add_action('wp_enqueue_scripts', 'load_wp_gen_address');
    add_action('wp_ajax_nopriv_save_address', 'save_address');

    // Woocommerce Mail QR and Payment Address
    add_action('woocommerce_email_before_order_table', 'sargapay_add_content_wc_order_email', 20, 4);

    // Show cancel time for orders without payment woocommerce_view_order
    add_action('woocommerce_order_details_before_order_table', 'sargapay_one_view_order_cancel_notice');
    add_action('woocommerce_order_details_before_order_table', 'sargapay_ada_view_order_cancel_notice');

    // Add Payment Method to Woocommerce
    function sargapay_one_plugin_add_gateway_class($gateways)
    {
        $gateways[] = 'Sargapay_ONE_Gateway';
        return $gateways;
    }
    function sargapay_ada_plugin_add_gateway_class($gateways)
    {
        $gateways[] = 'Sargapay_ADA_Gateway';
        return $gateways;
    }

    //Function to add settings link
    function sargapay_plugin_settings_link($links)
    {
        // Build and escape the URL.
        $url = esc_url(add_query_arg(
            array('page' =>
            'wc-settings', 'tab' => 'checkout'),
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
        wp_enqueue_script('gen_addressjs', plugins_url('/js/admin_main.js', __FILE__), array('jquery', 'cardano_serialization_lib', 'cardano_asm', 'cardano_lib_bg', 'bech32'));
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
        wp_enqueue_script('wp_gen_address', plugins_url('/js/main.js', __FILE__), array('jquery'));
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
    function sargapay_load_textdomain()
    {
        load_plugin_textdomain('sargapay-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    /** Add QR and Payment address in Thank You Page    
     ** Add Warning header if testmode is on
     */

    function sargapay_ada_thank_you_text($thank_you_title, $order)
    {
        if (isset($order)) {
            if ($order->get_payment_method() === "sargapay-ada-plugin") {
                $message = '<div style="font-weight:bold; text-align:center; color:white; background:black;">' . esc_html(__('Remember that you have 24 hours to pay for your order before it\'s automatically canceled.', 'sargapay-plugin')) . '</div>';
                $order_id = $order->get_id();
                global $wpdb;
                new Sargapay_DBs;
                $table = Sargapay_DBs::get_sargapay_ada_tables();
                $query_address = $wpdb->get_results("SELECT pay_address, order_amount, network FROM $table WHERE order_id=$order_id");
                //ERROR DB
                if ($wpdb->last_error) {
                    //LOG Error             
                    write_log($wpdb->last_error);
                } else if (count($query_address) === 0) {
                    $message = "<p>" . esc_html(__('ERROR PLEASE CONTACT ADMIN TO PROCEED WITH THE ORDER', 'sargapay-plugin')) . "</p>";
                    write_log("ERROR DB query empty in Thank You Page");
                    return $thank_you_title . "<br>" . $message . '<br><br>';
                } else {
                    if ($query_address[0]->network == 1) {
                        $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay-plugin'));
                        echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                    }
                    // Get order amount in ada
                    $total_ada = $query_address[0]->order_amount;
                    // Get payment address
                    $payment_address = $query_address[0]->pay_address;
                    $qr = new Sargapay_QR();
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
                        <p style='text-align:center;'>" . esc_html(__('Payment Address Copied!', 'sargapay-plugin')) . "<br><span style='font-weight:bold;' id='address_copiado_sp'></span</p>
                    </div>
                </div>";
                    echo "<div style='text-align:center; font-weight:bold;'><h4>"
                        . esc_html(__('Payment Address', 'sargapay-plugin')) .
                        "</h4><p id='pay_add_p_field_tk_plugin' style='width:100%; overflow-wrap:anywhere;'>" . esc_html($payment_address) . "</p>"
                        . $qr->sargapay_generate($payment_address) .
                        '</div>';
                    // Amount Button     
                    echo
                    "<div id='copy_modal_amount' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'>" . esc_html(__('Amount Copied!', 'sargapay-plugin')) . "<br><span style='font-weight:bold;' id='amount_copiado_sp'><span></p>
                    </div>
                </div>";
                    echo '<p style="text-align: center;"><b>' . esc_html(__('ADA Total', 'sargapay-plugin')) . '</b><br><span id="pay_amount_span_field_tk_plugin">' . esc_html($total_ada) . '</span></p>' .
                        "<div style='display:flex; justify-content: space-evenly; margin:15px;'><button class='button' id='pay_add_button_field_tk_plugin'>" . esc_html(__('Copy Payment Address', 'sargapay-plugin')) . "</button><button class='button' id='pay_amount_button_field_tk_plugin'>" . esc_html(__('Copy Amount', 'sargapay-plugin')) . "</button></div>";

                    // SEND EMAIL  
                    // Create QR PNG FILE
                    $url_img = $qr->sargapay_QR_URL($payment_address);
                    // Email config
                    $email = $order->get_billing_email();
                    $subject = __("Payment Instructions ", 'sargapay-plugin') . get_bloginfo('name');
                    $file_name = $payment_address . ".png";
                    $testnet_bool = $query_address[0]->network;
                    // Email Sent                   
                    Sargapay_Send_Email::send_email($email, $subject, $testnet_bool, $total_ada, $payment_address, $url_img, $file_name, 2);
                    return $thank_you_title . "<br>" . $message . '<br><br>';
                }
            }
        }
        return $thank_you_title;
    }

    function sargapay_one_thank_you_text($thank_you_title, $order)
    {
        require_once(plugin_basename("/includes/class-sargapay-dbs.php"));
        if (isset($order)) {
            if ($order->get_payment_method() === "sargapay-one-plugin") {
                $message = '<div style="font-weight:bold; text-align:center; color:white; background:black;">' . esc_html(__('Remember that you have 24 hours to pay for your order before it\'s automatically canceled.', 'sargapay-plugin')) . '</div>';
                $order_id = $order->get_id();
                global $wpdb;
                new Sargapay_DBs;
                $table = Sargapay_DBs::get_sargapay_one_tables()->address_table;
                $query_order = $wpdb->get_results("SELECT order_amount, network FROM $table WHERE order_id=$order_id");
                //ERROR DB
                if ($wpdb->last_error) {
                    //LOG Error             
                    write_log($wpdb->last_error);
                } else if (count($query_order) === 0) {
                    $message = "<p>" . esc_html(__('ERROR PLEASE CONTACT THE ADMIN TO PROCEED WITH THE ORDER', 'sargapay-plugin')) . "</p>";
                    write_log("ERROR DB query empty in Thank You Page");
                    return $thank_you_title . "<br>" . $message . '<br><br>';
                } else {
                    if ($query_order[0]->network == 1) {
                        $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay-plugin'));
                        echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                    }
                    // Get order amount in one
                    $total_one = $query_order[0]->order_amount;
                    // Get payment address
                    $payment_address = WC()->payment_gateways->payment_gateways()['sargapay-one-plugin']->pay_address;
                    $qr = new Sargapay_QR();
                    $instruciones_pago =
                        "<style>
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
                    # Address Modal
                    $instruciones_pago .=
                        "<div id='copy_modal' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'>" . esc_html(__('Payment Address Copied!', 'sargapay-plugin')) . "<br><span style='font-weight:bold;' id='address_copiado_sp'></span></p>
                    </div>
                </div>";
                    # QR                    
                    $instruciones_pago .= "<div style='text-align:center; font-weight:bold;'><h4>"
                        . esc_html(__('Payment Address', 'sargapay-plugin')) .
                        "</h4><p id='pay_add_p_field_tk_plugin' style='width:100%; overflow-wrap:anywhere;'>" . esc_html($payment_address) . "</p>"
                        . $qr->sargapay_generate($payment_address) .
                        '</div>';
                    # Amount Modal     
                    $instruciones_pago .=
                        "<div id='copy_modal_amount' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'>" . esc_html(__('Amount Copied!', 'sargapay-one-plugin')) . "<br><span style='font-weight:bold;' id='amount_copiado_sp'><span></p>
                    </div>
                </div>";
                    # Input Data Modal
                    $instruciones_pago .=
                        "<div id='copy_modal_input_data' class='modal_tk_plugin'>
                    <div class='modal_tk_plugin_content'>
                        <span class='close_tk_plugin'>&times;</span>
                        <p style='text-align:center;'>" . esc_html(__('Input Data Copied!', 'sargapay-one-plugin')) . "<br><span style='font-weight:bold;' id='input_data_copiado_sp'><span></p>
                    </div>
                </div>";
                    $instruciones_pago .=  "<div style='display:flex; justify-content: space-evenly; margin:15px;'>" .
                        '<p style="text-align: center;"><b>' . esc_html(__('ONE Total', 'sargapay-one-plugin')) . '</b><br><span id="pay_amount_span_field_tk_plugin">' . esc_html($total_one) . '</span></p>' .
                        '<p style="text-align: center;"><b>' . esc_html(__('Input Data', 'sargapay-one-plugin')) . '</b><br><span id="input_data_span_field_tk_plugin">' . esc_html("0x" . dechex($order_id)) . '</span></p></div>' .
                        "<div style='display:flex; justify-content: space-evenly; margin:15px;'>" .
                        "<button class='button' id='pay_add_button_field_tk_plugin'>" . esc_html(__('Copy Payment Address', 'sargapay-plugin')) . "</button>" .
                        "<button class='button' id='pay_amount_button_field_tk_plugin'>" . esc_html(__('Copy Amount', 'sargapay-plugin')) . "</button>" .
                        "<button class='button' id='input_data_button_field_tk_plugin'>" . esc_html(__('Copy Input Data', 'sargapay-plugin')) . "</button></div>";
                    echo $instruciones_pago;
                    // SEND EMAIL  
                    // Create QR PNG FILE
                    $url_img = $qr->sargapay_QR_URL($payment_address);
                    // Email config
                    $email = $order->get_billing_email();
                    $subject = __("Payment Instructions ", 'sargapay-plugin') . get_bloginfo('name');
                    $file_name = $payment_address . ".png";
                    $testnet_bool = $query_order[0]->network;
                    // Email Sent                   
                    Sargapay_Send_Email::send_email($email, $subject, $testnet_bool, $total_one, $payment_address, $url_img, $file_name, 1, $order_id);
                    return $thank_you_title . "<br>" . $message . '<br><br>';
                }
            }
        }
        return $thank_you_title;
    }

    function sargapay_ada_view_order_cancel_notice($order)
    {
        if (!is_checkout() && $order->get_payment_method() === "sargapay-ada-plugin" && $order->get_status() === "on-hold") {
            require_once(plugin_basename("/includes/class-sargapay-dbs.php"));
            $order_id = $order->get_id();
            global $wpdb;
            new Sargapay_DBs;
            $table = Sargapay_DBs::get_sargapay_ada_tables();
            $query_order = $wpdb->get_results("SELECT  pay_address, order_amount, network FROM $table WHERE order_id=$order_id");
            //LOG ERROR DB
            if ($wpdb->last_error) {
                //LOG Error             
                write_log($wpdb->last_error);
            } else if (count($query_order) === 0) {
                echo "<p>" . __('ERROR PLEASE CONTACT THE ADMIN TO PROCEED WITH THE ORDER', 'sargapay-plugin') . "</p>";
                write_log("Emprty Query result in account page order");
            } else {
                if ($query_order[0]->network) {
                    $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay-plugin'));
                    echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                }
                // Get order amount in one
                $total_ada = $query_order[0]->order_amount;
                // Get payment address
                $payment_address =  $query_order[0]->pay_address;
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
                $text = esc_html(__("Time Left", 'sargapay-plugin'));
                $qr = new Sargapay_QR();
                echo "<p style='text-align: center; font-size: 20px'>$text <span id='timer_order'>$time_until_cancel</span></p>";
                echo '<p style="text-align: center;"><b>' . esc_html(__('Payment Address', 'sargapay-plugin')) . '</b><br>' . $payment_address .
                    $qr->sargapay_generate($payment_address) .
                    '</p>';
                echo '<div style="display:flex; justify-content: space-around; margin:15px; border"><p style="text-align: center;"><b>' . esc_html(__('Total ADA', 'sargapay-plugin')) . '</b><br>' . $total_ada . '</p></div>';
            }
        } else if ($order->get_payment_method() === "sargapay-ada-plugin" && $order->get_status() === "cancelled") {
            echo esc_html(__("24 hours have passed and your order was canceled, the payment address is no longer valid.", 'sargapay-plugin'));
        }
    }

    function sargapay_one_view_order_cancel_notice($order)
    {
        if (!is_checkout() && $order->get_payment_method() === "sargapay-one-plugin" && $order->get_status() === "on-hold") {
            require_once(plugin_basename("/includes/class-sargapay-dbs.php"));
            $order_id = $order->get_id();
            global $wpdb;
            new Sargapay_DBs;
            $table = Sargapay_DBs::get_sargapay_one_tables()->address_table;
            $query_order = $wpdb->get_results("SELECT order_amount, network FROM $table WHERE order_id=$order_id");
            //LOG ERROR DB
            if ($wpdb->last_error) {
                //LOG Error             
                write_log($wpdb->last_error);
            } else if (count($query_order) === 0) {
                echo "<p>" . __('ERROR PLEASE CONTACT THE ADMIN TO PROCEED WITH THE ORDER', 'sargapay-plugin') . "</p>";
                write_log("Emprty Query result in account page order");
            } else {
                if ($query_order[0]->network) {
                    $testnet_msg  = esc_html(__("BE AWARE THIS IS A TESTNET PAYMENT ADDRESS", 'sargapay-plugin'));
                    echo "<p style='background:red; font-weight:bold; color:white; text-align:center;'> $testnet_msg </p>";
                }
                // Get order amount in one
                $total_one = $query_order[0]->order_amount;
                // Get payment address
                $payment_address =  WC()->payment_gateways->payment_gateways()['sargapay-one-plugin']->pay_address;
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
                $text = esc_html(__("Time Left", 'sargapay-plugin'));
                $qr = new Sargapay_QR();
                echo "<p style='text-align: center; font-size: 20px'>$text <span id='timer_order'>$time_until_cancel</span></p>";
                echo '<p style="text-align: center;"><b>' . esc_html(__('Payment Address', 'sargapay-plugin')) . '</b><br>' . $payment_address .
                    $qr->sargapay_generate($payment_address) .
                    '</p>';
                echo '<div style="display:flex; justify-content: space-around; margin:15px; border"><p style="text-align: center;"><b>' . esc_html(__('Total ONE', 'sargapay-plugin')) . '</b><br>' . $total_one . '</p>';
                echo '<p style="text-align: center;"><b>' . esc_html(__('Input Data', 'sargapay-plugin')) . '</b><br>' . esc_html("0x" . dechex($order_id)) . '</p></div>';
            }
        } else if ($order->get_payment_method() === "sargapay-one-plugin" && $order->get_status() === "cancelled") {
            echo esc_html(__("24 hours have passed and your order was canceled, the payment address is no longer valid.", 'sargapay-plugin'));
        }
    }
    // we add data protocol to render qr img on emails
    add_filter('kses_allowed_protocols', function ($protocols) {
        $protocols[] = 'data';
        return $protocols;
    });

    function sargapay_add_content_wc_order_email($order, $sent_to_admin, $plain_text, $email)
    {
        if ($email->id == 'customer_on_hold_order') {
            if ($order->get_payment_method() === "sargapay-one-plugin" || $order->get_payment_method() === "sargapay-ada-plugin") {
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
