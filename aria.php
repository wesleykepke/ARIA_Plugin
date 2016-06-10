<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://wesleykepke.github.io/ARIA_Plugin/
 * @since             1.0.0
 * @package           ARIA
 *
 * @wordpress-plugin
 * Plugin Name:       ARIA (Administration, Registration, and Information Assistant)
 * Plugin URI:        http://wesleykepke.github.io/ARIA_Plugin/
 * Description:       This plugin allows the NNMTA to create, schedule, manage, and create documents for their music festivals.
 * Version:           2.0.0
 * Author:            KREW (Kyle Lee, Renee Iinuma, Ernest Landrito, and Wesley Kepke)
 * Author URI:        http://wesleykepke.github.io/ARIA_Plugin/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ARIA
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_aria() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-aria-activator.php';
	ARIA_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_aria() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-plugin-name-deactivator.php';
	Plugin_Name_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_aria' );
register_deactivation_hook( __FILE__, 'deactivate_aria' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-aria.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_aria() {

	$plugin = new ARIA();
	$plugin->run();

}
run_aria();
