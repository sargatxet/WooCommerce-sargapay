<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    sargapay
 * @subpackage sargapay/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    sargapay
 * @subpackage sargapay/includes
 * @author     trakadev <trakadev@protonmail.com>
 */
class Sargapay_Deactivator {

	/**
	 * Short Description.
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		
		// REMOVE CRONJOB to verify paymanets
		wp_clear_scheduled_hook('sargapay_cron_hook');

	}

}
