<?php
/**
 * Plugin Name:       DualStock Manager
 * Plugin URI:        https://dualequipamientos.com
 * Description:       Omnichannel stock control with multi-location internal distribution (Showroom, Deposito 1, Deposito 2).
 * Version:           0.2.1
 * Author:            Google Deepmind Agent
 * Text Domain:       dualstock-manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'DSM_VERSION', '0.2.1' );
define( 'DSM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_dualstock_manager() {
	require_once DSM_PLUGIN_DIR . 'includes/class-dsm-activator.php';
	DSM_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_dualstock_manager() {
	// Flush rewrite rules if necessary
}

register_activation_hook( __FILE__, 'activate_dualstock_manager' );
register_deactivation_hook( __FILE__, 'deactivate_dualstock_manager' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once DSM_PLUGIN_DIR . 'includes/class-dualstock-manager.php';

/**
 * Begins execution of the plugin.
 */
function run_dualstock_manager() {
	$plugin = new DualStock_Manager();
	$plugin->run();
}

run_dualstock_manager();
