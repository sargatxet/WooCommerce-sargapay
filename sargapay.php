<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://sargatxet.cloud/
 * @since             1.0.0
 * @package           sargapay
 *
 * @wordpress-plugin
 * Plugin Name:       Sargapay
 * Plugin URI:        https://sargatxet.cloud/sargapay-cardano/
 * Description:       WordPress payment gateway for crypto.
 * Version:           2.1.0
 * Author:            Sargatxet
 * Author URI:        https://sargatxet.cloud/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sargapay
 * Domain Path:       /languages/
 * Requires PHP: 	  7.4
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


// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin path.
 * Current plugin url.
 * Current plugin version.
 */

define('SARGAPAY_PATH', plugin_dir_path(__FILE__));
define('SARGAPAY_URL', plugin_dir_url(__FILE__));
define('SARGAPAY_VERSION', '2.1.0');

add_filter('cron_schedules', 'sargapay_cron_hook');
add_action('sargapay_cron_hook', 'sargapay_check_confirmations_cardano');
add_action('plugins_loaded', 'sargapay_load_plugin_textdomain');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sargapay-activator.php
 */
function sargapay_activate()
{
    // Check if woocommerce is active.
    if (!class_exists('WC_Payment_Gateway')) {
        die(__('Plugin NOT activated: WooCommerce is required', 'sargapay'));
    }
    if (PHP_VERSION_ID <= 70399) {
        die(__('Plugin NOT activated: Minimum PHP version required is 7.4', 'sargapay'));
    }
    require_once SARGAPAY_PATH . 'includes/class-sargapay-activator.php';
    Sargapay_Activator::activate();

    // Create DB for Addresses, if it doesn't exist.
    require_once SARGAPAY_PATH . 'paymentGateway/cardano/functions/sargapay-createDB.php';
    sargapay_create_address_table();

    //Register verification 
    if (!wp_next_scheduled('sargapay_cron_hook')) {
        wp_schedule_event(time(), 'every_ten_minutes', 'sargapay_cron_hook');
    }
}

function sargapay_load_plugin_textdomain()
{
    load_plugin_textdomain(
        'sargapay',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
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
function sargapay_check_confirmations_cardano()
{
    require_once SARGAPAY_PATH . 'paymentGateway/cardano/class-sargapay-confirm-payment.php';
    $check_confirm = new Sargapay_ConfirmPayment();
    $check_confirm->sargapay_check_all_pendding_orders();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sargapay-pro-plugin-deactivator.php
 */
function sargapay_deactivate()
{
    require_once SARGAPAY_PATH . 'includes/class-sargapay-deactivator.php';
    Sargapay_Deactivator::deactivate();
    // REMOVE CRONJOB to verify paymanets
    wp_clear_scheduled_hook('sargapay_cron_hook');
}

register_activation_hook(__FILE__, 'sargapay_activate');
register_deactivation_hook(__FILE__, 'sargapay_deactivate');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require SARGAPAY_PATH . 'includes/class-sargapay.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_sargapay()
{
    // Add Settings link
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'sargapay_settings_link');
    $plugin = new Sargapay();
    $plugin->run();
}
run_sargapay();

function sargapay_plugin_log($entry, $mode = 'a', $file = 'sargapay')
{
    // Get WordPress uploads directory.
    $upload_dir = wp_upload_dir();
    $upload_dir = $upload_dir['basedir'];
    // If the entry is array, json_encode.
    if (is_array($entry)) {
        $entry = sanitize_text_field(json_encode($entry));
    } else {
        $entry = sanitize_text_field($entry);
    }
    // Write the log file.
    $file  = $upload_dir . '/' . $file . '.log';
    $file  = fopen($file, $mode);
    $bytes = fwrite($file, current_time('mysql') . "::" . $entry . "\n");
    fclose($file);
    return $bytes;
}
