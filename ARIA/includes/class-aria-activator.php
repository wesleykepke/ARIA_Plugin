<?php

/**
 * Fired during plugin activation
 *
 * @link       http://wesleykepke.github.io/ARIA/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
 */
class ARIA_Activator {

	/**
	 * This function defines all of the code that should be executed on plugin activation.
	 *
	 * This function makes calls to specific functions that need to be executed when
	 * the ARIA plugin is activated in the WordPress admin dashboard.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// define all the files that are required
		require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		require_once("class-aria-create-competition.php");

		// make sure that the Gravity Forms plugin is enabled
    if (is_plugin_active('gravityforms/gravityforms.php')) {
			// create the form for creating new music competitions
			ARIA_Create_Competition::aria_create_competition_activation();
//			wp_enqueue_script( 'jquery' );
//			wp_enqueue_script('cry1', 'http://crypto-js.googlecode.com/svn/tags/3.1.2/build/rollups/hmac-sha1.js' );
//			wp_enqueue_script( 'cry2', 'http://crypto-js.googlecode.com/svn/tags/3.1.2/build/components/enc-base64-min.js' );
//			wp_enqueue_script( 'aria', '/wp-content/plugins/ARIA/public/js/aria_dropdown.js' );
		}

		else {
			wp_die("Error: ARIA requires the Gravity Forms plugin to be installed
			and enabled. Please enable the Gravity Forms plugin and reactivate
			ARIA.");
		}
	}
}
