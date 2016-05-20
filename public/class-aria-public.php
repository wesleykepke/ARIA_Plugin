<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public
 * @author     Your Name <email@example.com>
 */
class ARIA_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $ARIA;

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Plugin_Name_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Plugin_Name_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/aria-frontend.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script("jquery");
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/aria-public.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script('cry1', plugin_dir_url( __FILE__ ) . 'js/hmac-sha1.js' );
		wp_enqueue_script( 'cry2', plugin_dir_url( __FILE__ ) . 'js/enc-base64-min.js' );

		wp_register_script( 'resend_script', plugin_dir_url( __FILE__ ) . 'js/resend_link.js', array('jquery'), '1.0', false );
		wp_enqueue_script('resend_script');


		wp_register_script( 'student_reg_script', plugin_dir_url( __FILE__ ) . 'js/student_reg.js', array('jquery'), '1.0', false );
		wp_enqueue_script('student_reg_script');

		wp_register_script( 'teacher_reg_script', plugin_dir_url( __FILE__ ) . 'js/teacher_reg.js', array('jquery'), '1.0', false );
		wp_enqueue_script('teacher_reg_script');

		wp_register_script( 'modify_schedule_script', plugin_dir_url( __FILE__ ) . 'js/modify_schedule.js', array('jquery'), '1.0', false );
		wp_enqueue_script('modify_schedule_script');

		wp_register_script( 'score_input_script', plugin_dir_url( __FILE__ ) . 'js/score_input.js', array('jquery'), '1.0', false );
		wp_enqueue_script('score_input_script');
	}

}
