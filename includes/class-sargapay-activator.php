<?php

/**
 * Fired during plugin activation
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    sargapay
 * @subpackage sargapay/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    sargapay
 * @subpackage sargapay/includes
 * @author     trakadev <trakadev@protonmail.com>
 */
class Sargapay_Activator
{

	/**
	 * Short Description.
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		if (!class_exists('WC_Payment_Gateway')) {
			deactivate_plugins(plugin_basename(__FILE__));
			return;
		}
	}
}
