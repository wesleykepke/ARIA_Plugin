<?php

/**
 * The document generator.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ARIA_ROOT . "/includes/class-aria-api.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-scheduler.php");
require_once(ARIA_ROOT . "/admin/scheduler/scheduler.php");

class Doc_Generator {

  /**
   * This function handles processing after the festival chairman has elected to
   * generate documents for a given competition.
   *
   * This function will, through the use of other functions, generate all
   * competition documents and send parents/teachers emails with information
   * regarding when their students and chilren are playing (respectively).
   *
   * @param 	Entry Object  $entry  The entry that was just submitted.
   * @param 	Form Object   $form   The form used to submit entries.
   * @param 	String/Array 	$confirmation 	The confirmation message to be filtered.
   * @param 	Bool 	$ajax 	Specifies if this form is configured to be submitted via AJAX.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function doc_gen_and_email($confirmation, $form,
  	                                       $entry, $ajax) {
    // only perform processing if it's the doc. gen. form
     if (!array_key_exists('isDocGenForm', $form)
        || !$form['isDocGenForm']) {
          return $confirmation;
    }

    // determine which competition to gen. docs. and send emails for
    $field_mapping = self::doc_gen_field_id_array();
    $title = $entry[strval($field_mapping['active_competitions'])];
    $related_forms = ARIA_API::aria_find_related_forms_ids($title);

    // locate that serialized version of the scheduler object
    $title = str_replace(' ', '_', $title);
    $file_path = ARIA_FILE_UPLOAD_LOC . $title . ".txt";
    if (file_exists($file_path)) {
      $scheduler = file_get_contents($file_path);
      $scheduler = unserialize($scheduler);
    }
    else {
    	wp_die("<h1>ERROR: It seems as if no such schedule has been created yet for " .
    		$entry[strval($field_mapping['active_competitions'])] . ". Have
    		you tried running the scheduler yet?</h1>");
    }

    // use the scheduler object to prepare the format(s) required for doc. generation
    $comp_sections = $scheduler->get_section_info_for_doc_gen();
    //wp_die(print_r($comp_sections));

    // send all participating teachers emails regarding when their students are playing
    // and their volunteer information
    Scheduling_Algorithm::send_teachers_competition_info($related_forms['teacher_master_form_id'],
    	                                                   $scheduler,
    	                                                   $entry[strval($field_mapping['active_competitions'])]);

    // send all associated parents emails regarding when/where their child/children
    // are performing


    //print_r($scheduler);
    //wp_die();
  }

  /**
   * This function handles processing after the festival chairman has elected to
   * generate documents for a given competition.
   *
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_doc_gen_page() {
    // prevent form from being created twice
    if (ARIA_API::aria_get_doc_gen_form_id() !== -1) {
    	return;
    }

    $field_mapping = self::doc_gen_field_id_array();
    $form = new GF_Form(DOC_GEN_FORM_NAME, "");

    // drop-down menu of active competitions
    $active_competitions_field = new GF_Field_Select();
    $active_competitions_field->label = "Active Competitions";
    $active_competitions_field->id = $field_mapping['active_competitions'];
    $active_competitions_field->isRequired = false;
    $active_competitions_field->description = "Please select the name of the" .
    " competition that you would like to generate documents for and send " .
    " teachers/parents emails regarding scheduling information.";
    $active_competitions_field->descriptionPlacement = "above";
    $active_competitions_field->choices = array("Select from below");
    $form->fields[] = $active_competitions_field;

    // add a default submission message for the doc. gen. form
    $successful_submission_message = 'Congratulations! You have just successfully' .
    ' generated all competition documents and sent teachers/parents emails.';
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

    // identify form as the scheduling page
    $form_arr = $form->createFormArray();
    $form_arr['isDocGenForm'] = true;

    // add form to dashboard
    $form_id = GFAPI::add_form($form_arr);
    if (is_wp_error($form_id)) {
      wp_die($form_id->get_error_message());
    }
    else {
      $doc_gen_url = ARIA_API::aria_publish_form(DOC_GEN_FORM_NAME,
      	                                         $form_id, CHAIRMAN_PASS, true);
    }
  }

  /**
   * Returns an associative array for field mappings of doc. gen. form.
   *
   * This function returns an array that maps all of the names of the
   * fields in the doc. gen. form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function doc_gen_field_id_array() {
    return array(
      'active_competitions' => 1
    );
  }

  /**
   * This function will pre-populate the drop-down menu on the doc. gen.
   * page with all of the active competitions.
   *
   * Whenever the festival chairman visits the page that is used for generating,
   * comp. docs., that page needs to have the drop-down menu of active competitions
   * pre-populated. This function is responsible for accomplishing that goal.
   *
   * @param $form 	Form Object 	The current form object.
   * @param $is_ajax 	Bool 	Specifies if the form is submitted via AJAX
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function before_doc_gen_render($form, $is_ajax) {
    // Only perform prepopulation if it's the scheduler form
    if (!array_key_exists('isDocGenForm', $form)
        || !$form['isDocGenForm']) {
          return;
    }

    // Get all of the active competitions
    $all_active_competitions = ARIA_API::aria_get_all_active_comps();
    $competition_names = array();
    foreach ($all_active_competitions as $competition) {
      $single_competition = array(
        'text' => $competition['name'],
        'value' => $competition['name'],
        'isSelected' => false
      );
      $competition_names[] = $single_competition;
      unset($single_competition);
    }

    $doc_gen_field_mapping = self::doc_gen_field_id_array();
    $search_field = $doc_gen_field_mapping['active_competitions'];
    $name_field = ARIA_API::aria_find_field_by_id($form['fields'], $search_field);
    $form['fields'][$name_field]->choices = $competition_names;
  }
}
