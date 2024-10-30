<?php

/**
 * Enables customer to choose pickup point in woocommerce checkout flow with google maps interface. Shows points name, address and opening hours. Saves the points data to order and sets pickup point ID as extra field in woocommerce order. Plugin supports carriers: PostNord, GLS, Bring,  DHL and DAO. Is compatable with default woocommerce shipping and flexible shipping plugin.
 *
 * @link              https://lagersystem.dk
 * @since             1.0.0
 * @package           Lagersystem_Parcelpickup_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Lagersystem.dk: Parcelpickup locator for Woocommerce
 * Plugin URI:        https://lagersystem.dk/udleveringssted-modul
 * Description:       This free plugin enables the customer to browse and select a parcel pickup place of their choosing, in the checkout flow . Supports PostNord, GLS, DHL, DAO356 and Bring. Requires a free API key from Lagersystem and Google Maps.
 * Version:           2.0.10
 * Author:            Lagersystem.dk
 * Author URI:        https://lagersystem.dk
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lagersystem-parcelpickup-woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LAGERSYSTEM_PARCELPICKUP_WOOCOMMERCE_VERSION', '2.0.10' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lagersystem-parcelpickup-woocommerce-activator.php
 */
function activate_lagersystem_parcelpickup_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-lagersystem-parcelpickup-woocommerce-activator.php';
	Lagersystem_Parcelpickup_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lagersystem-parcelpickup-woocommerce-deactivator.php
 */
function deactivate_lagersystem_parcelpickup_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-lagersystem-parcelpickup-woocommerce-deactivator.php';
	Lagersystem_Parcelpickup_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_lagersystem_parcelpickup_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_lagersystem_parcelpickup_woocommerce' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-lagersystem-parcelpickup-woocommerce.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_lagersystem_parcelpickup_woocommerce() {

	$plugin = new Lagersystem_Parcelpickup_Woocommerce();
	$plugin->run();

}
run_lagersystem_parcelpickup_woocommerce();
