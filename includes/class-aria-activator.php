<?php

/**
 * Fired during plugin activation.
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
	 * This function contains code that should be executed on plugin activation.
	 *
	 * This function makes calls to specific functions that need to be executed
   * when the ARIA plugin is activated in the WordPress admin dashboard.
	 *
	 * @since    1.0.0
	 */
  public static function activate() {
    // define all the files that are required
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');

    // make sure that the Gravity Forms plugin is enabled
    if (is_plugin_active('gravityforms/gravityforms.php')) {
      // create various forms upon initialization
      require_once("class-aria-create-competition.php");
      require_once("class-aria-music.php");
      require_once(ARIA_ROOT . "/admin/scheduler/scheduler.php");
      require_once("class-aria-teacher-upload.php");
      ARIA_Create_Competition::aria_create_competition_activation();
      ARIA_Music::aria_create_music_upload_form();
      Scheduling_Algorithm::aria_create_scheduling_page();
      ARIA_TEACHER::aria_create_teacher_upload_form(); 
    }
    else {
      wp_die("Error: ARIA requires the Gravity Forms plugin to be installed
        and enabled. Please enable the Gravity Forms plugin and reactivate
        ARIA.");
    }
  }
}
