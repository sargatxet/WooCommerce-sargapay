<?php

/**
 * Both admin and public-facing functionality of the plugin.
 *
 * @link       https://sargatxet.cloud/
 * @since      1.0.0
 *
 * @package    Sargapay
 * @subpackage Sargapay/includes
 */

/**
 * Both admin and public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for both admin and public-facing
 *
 * @since      1.0.0
 * @package    Sargapay
 * @subpackage Sargapay/includes
 * @author     trakadev <trakadev@protonmail.com>
 */
class Sargapay_Include
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
	}

	/**
	 * Init something related to both admin and public-facing areas functionality.
	 * Callback function of init
	 * Example remove it if not needed
	 *
	 * @since    1.0.0
	 */
	public function init_something()
	{
	}	
}
