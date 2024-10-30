<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://lagersystem.dk
 * @since      1.0.0
 *
 * @package    Lagersystem_Parcelpickup_Woocommerce
 * @subpackage Lagersystem_Parcelpickup_Woocommerce/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Lagersystem_Parcelpickup_Woocommerce
 * @subpackage Lagersystem_Parcelpickup_Woocommerce/includes
 * @author     Lagersystem <info@lagersystem.dk>
 */
class Lagersystem_Parcelpickup_Woocommerce_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'lagersystem-parcelpickup-woocommerce',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
