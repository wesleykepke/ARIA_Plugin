<?php

/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wesleykepke.github.io/ARIA/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

require_once("class-aria-api.php");

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
 */
class ARIA {

  /**
   * The loader that's responsible for maintaining and registering all hooks that power
   * the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
   */
  protected $loader;

  /**
   * The unique identifier of this plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $plugin_name    The string used to uniquely identify this plugin.
   */
  protected $plugin_name;

  /**
   * The current version of the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string    $version    The current version of the plugin.
   */
  protected $version;

  /**
   * Define the core functionality of the plugin.
   *
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks for the admin area and
   * the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function __construct() {

    $this->plugin_name = 'ARIA';
    $this->version = '1.0.0';

    $this->load_dependencies();
    $this->set_locale();
    $this->define_admin_hooks();
    $this->define_public_hooks();

  }

  /**
   * Load the required dependencies for this plugin.
   *
   * Include the following files that make up the plugin:
   *
   * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
   * - Plugin_Name_i18n. Defines internationalization functionality.
   * - Plugin_Name_Admin. Defines all hooks for the admin area.
   * - Plugin_Name_Public. Defines all hooks for the public side of the site.
   *
   * Create an instance of the loader which will be used to register the hooks
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function load_dependencies() {

    /**
     * The class responsible for orchestrating the actions and filters of the
     * core plugin.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-aria-loader.php';
    $this->loader = new ARIA_Loader();

    /**
     * The class responsible for defining internationalization functionality
     * of the plugin.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-plugin-name-i18n.php';

    /**
     * The class responsible for defining all actions that occur in the admin area.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-plugin-name-admin.php';

    /**
     * The class responsible for defining all actions that occur in the public-facing
     * side of the site.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-aria-public.php';

    // Include all of the ARIA files needed as dependencies.
    require_once("class-aria-create-competition.php");
    require_once("class-aria-music.php");
    require_once("class-aria-form-hooks.php");
    require_once("class-aria-teacher-upload.php");
    require_once("class-aria-resend-email-form.php");
    require_once(ARIA_ROOT . '/admin/scheduler/scheduler.php');
    require_once(ARIA_ROOT . '/admin/scheduler/modify-schedule.php');
    require_once(ARIA_ROOT . '/admin/scheduler/score-input.php');

    // Register all of the hooks needed by ARIA

    /*
    The action registered for this hook is for adding the form to create a new
    music competition.

    We need to figure out what we are going to do if the user accidentally
    deletes the "NNMTA: Create Competition" form after the plugin is
    initialized. Currently, if the user deletes it, the only way to get it back
    is to deactivate and reactivate the plugin.
    */
    $this->loader->add_action(
      'gform_confirmation',
      'ARIA_Create_Competition',
      'aria_create_teacher_and_student_forms', 10, 4);

    /*
    The action registered for this hook updates the list of competitions that
    can be selected for doc. generation on the doc. generation page.
    */
    $this->loader->add_action(
      'gform_enqueue_scripts',
      'Scheduling_Algorithm',
      'before_schedule_render', 10, 4
    );

		/*
    The action registered for this hook updates the list of competitions that
    can be selected on the modify schedule page.
    */
    $this->loader->add_action(
      'gform_enqueue_scripts',
      'Modify_Schedule',
      'before_modify_schedule_render', 10, 2
    );

		/*
    The action registered for this hook updates the list of competitions that
    can be selected on the score input page.
    */
    $this->loader->add_action(
      'gform_enqueue_scripts',
      'Score_Input',
      'before_score_input_render', 10, 2
    );

    /*
    The action registered for this hook updates the list of teachers that
    can be selected by students upon competition registration.
    */
    $this->loader->add_action(
      'gform_enqueue_scripts',
      'ARIA_Form_Hooks',
      'aria_before_student_submission', 10, 2
    );

    /*
    The action registered for this hook is for adding scheduling functionality.
    */
    $this->loader->add_action(
      'gform_confirmation',
      'Scheduling_Algorithm',
      'aria_scheduling_algorithm', 10, 4
    );

    /*
    The action registered for this hook is for adding score input functionality.
    */
    $this->loader->add_action(
      'gform_confirmation',
      'Score_Input',
      'render_score_input_form', 10, 4
    );

		/*
    The action registered for this hook is for rendering a saved schedule.
    */
    $this->loader->add_action(
      'gform_confirmation',
      'Modify_Schedule',
      'render_schedule', 10, 4
    );

    /*
    The action registered for this hook updates the list of competitions that
    can be selected for adding a teacher to.
    */
    $this->loader->add_action(
      'gform_enqueue_scripts',
      'ARIA_Teacher',
      'aria_before_teacher_upload', 10, 4
    );

    /*
    The action registered for this hook updates the list of competitions that
    can be selected for resending the URL
    */
    $this->loader->add_action(
      'gform_enqueue_scripts',
      'ARIA_Resend_Email',
      'aria_before_resend_form', 10, 4
    );


    /*
    The action registered for this hook is for resending a url
    */
    $this->loader->add_action(
      'gform_confirmation',
      'ARIA_Resend_Email',
      'aria_after_resend_form', 10, 4
    );


    /*
    The action registered for this hook is for adding a new teacher to a
    specified music competition.
    */
    $this->loader->add_action(
      'gform_confirmation',
      'ARIA_Teacher',
      'aria_after_teacher_upload', 10, 4
    );

    /*
   * Payment testing


    $this->loader->add_action(
      'gform_paypal_fulfillment',
      'ARIA_Registration_Handler',
      'aria_test_payment', 10, 4
      );
    */

    /*
    The action registered for this hook is to invoke processing after the
    festival chairman has uploaded teacher information to be stored in a
    teacher-master form.
    */
/*
    $this->loader->add_action('gform_after_submission',
      'ARIA_Teacher', 'aria_upload_teachers', 9, 2);
*/
    /*
    The action registered for this hook is to invoke processing after a student
    has submitted their registration.
    */

    $this->loader->add_action('gform_paypal_fulfillment',
      'ARIA_Form_Hooks', 'aria_after_student_submission', 10, 2);

    /*
    $this->loader->add_action('gform_after_submission',
      'ARIA_Form_Hooks', 'aria_after_student_submission', 10, 2);
  */
    /*
    The action registered for this hook is to invoke processing after a teacher
    has submitted their registration.
    */
    $this->loader->add_action('gform_after_submission',
      'ARIA_Form_Hooks', 'aria_after_teacher_submission', 10, 2);

    /*
    The filter that performs form validation on the student registration form.
    */
    /*
    $this->loader->add_filter('gform_form_validation', 'ARIA_Create_Competition',
      'aria_student_form_validation', 10, 4);
    */

    /*
    The filter that makes sure that the teacher pages have correct hashes.
    */
    $this->loader->add_filter('gform_enqueue_scripts',
      'ARIA_Form_Hooks',
      'aria_before_teacher_render', 10, 2);


    $this->loader->add_action( 'gform_after_update_entry',
        'ARIA_Form_Hooks', 'aria_student_master_post_update_entry', 10, 3 );


    /*
    The action registered for this hook if for adding music upload/download
    functionality.

    Like the action hook above, we need to handle the case where the user
    deletes this form while the plugin is active.
    */
    $this->loader->add_action(
      'gform_confirmation',
      'ARIA_Music',
      'aria_add_music_from_csv', 10, 4);

    /*
    The following two add_actions are for testing purposes only!
    $this->loader->add_action(
    'gform_after_submission_194',
    'ARIA_Form_Hooks',
    'after_student_master_submission', 10, 2);

    $this->loader->add_action(
    'gform_after_submission_195',
    'ARIA_Form_Hooks',
    'aria_teacher_master_submission', 10, 2);
    */

    /*
    The filter registered for this hook is to modify the upload path for NNMTA
    music
    */
    $this->loader->add_filter('gform_upload_path', 'ARIA_Music',
      'aria_modify_upload_path', 10, 2);

    /*
    The filter registered for this hook exposes the new, custom query variables
    to WP_Query.
    */
    $this->loader->add_filter('query_vars', 'ARIA_Form_Hooks',
      'aria_add_query_vars_filter', 10, 1);

    $this->loader->add_filter( 'wp_mail_content_type', 'ARIA_API',
      'set_content_type', 10, 1);
  }

  /**
   * Define the locale for this plugin for internationalization.
   *
   * Uses the Plugin_Name_i18n class in order to set the domain and to register the hook
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function set_locale() {

    $plugin_i18n = new Plugin_Name_i18n();

    $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

  }

  /**
   * Register all of the hooks related to the admin area functionality
   * of the plugin.
   *
   * @since    1.0.0
   * @access   private
   */
  private function define_admin_hooks() {

    $plugin_admin = new Plugin_Name_Admin( $this->get_plugin_name(), $this->get_version() );

    $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
    $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

  }

  /**
   * Register all of the hooks related to the public-facing functionality
   * of the plugin.
   *
   * @since    1.0.0
   * @access   private
   */
  private function define_public_hooks() {

    $plugin_public = new ARIA_Public( $this->get_plugin_name(), $this->get_version() );

    $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
    $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

  }

  /**
   * Run the loader to execute all of the hooks with WordPress.
   *
   * @since    1.0.0
   */
  public function run() {
    $this->loader->run();
  }

  /**
   * The name of the plugin used to uniquely identify it within the context of
   * WordPress and to define internationalization functionality.
   *
   * @since     1.0.0
   * @return    string    The name of the plugin.
   */
  public function get_plugin_name() {
    return $this->plugin_name;
  }

  /**
   * The reference to the class that orchestrates the hooks with the plugin.
   *
   * @since     1.0.0
   * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
   */
  public function get_loader() {
    return $this->loader;
  }

  /**
   * Retrieve the version number of the plugin.
   *
   * @since     1.0.0
   * @return    string    The version number of the plugin.
   */
  public function get_version() {
    return $this->version;
  }

}
