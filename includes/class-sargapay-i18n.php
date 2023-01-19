<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    Sargapay
 * @subpackage Sargapay/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    sargapay
 * @subpackage sargapay/includes
 * @author     trakadev <trakadev@protonmail.com>
 */
class Sargapay_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain()
	{

		load_plugin_textdomain(
			'sargapay',
			false,
			plugin_dir_path( dirname(__FILE__) ) . 'languages/'
		);
	}
}
