<?php

/**
 * The modify schedule page.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

class Modify_Schedule {

  /**
   * This function handles processing after the festival chairman has elected to
   * modify the schedule for a given competition.
   *
   * This function will present the user with the schedule of whatever competition
   * they select (provided the scheduler has already ran for the competition they
   * select).
   *
   * @param 	Entry Object  $entry  The entry that was just submitted.
   * @param 	Form Object   $form   The form used to submit entries.
   * @param 	String/Array 	$confirmation 	The confirmation message to be filtered.
   * @param 	Bool 	$ajax 	Specifies if this form is configured to be submitted via AJAX.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function render_schedule($confirmation, $form, $entry, $ajax) {
    // only perform processing if it's the modify schedule form
    if (!array_key_exists('isModifyScheduleForm', $form)
        || !$form['isModifyScheduleForm']) {
      return $confirmation;
    }

    // determine which competition to render the schedule for
    $field_mapping = self::modify_schedule_field_id_array();
    $title = $entry[strval($field_mapping['active_competitions'])];
    $related_forms = ARIA_API::aria_find_related_forms_ids($title);

    // locate that serialized version of the scheduler object
    $non_formatted_title = $title;
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

    // print the schedule to the festival chairman
    $confirmation .= '<h1 id="comp-name"><b id="comp-name-bold">' . $title . '</b></h1>';
    $confirmation .= "<h4>Congratulations! You have just successfully loaded a
    previously generated schedule.<br>After you make modifications to the schedule
    (adding judges, proctors, etc.), <b>you must click the 'Save Schedule' button</b>,
    otherwise, your changes will be lost. The information you supply here will be used for
    document generation.<br>For each section below, you can modify the start time,
    the room, the judge(s), the proctor(s), and the door guard.</h4>";
    $confirmation .= '<button id="genDocumentsButton" type="button" onclick="generateDocuments()">Generate Documents</button><br>';
    $confirmation .= '<button id="emailParentsAndTeachersButton" type="button" onclick="emailParentsAndTeachers()">Email Parents and Teachers</button><br>';
    $confirmation .= '<button id="saveScheduleButton" type="button" onclick="sendScheduleToServer()">Save Schedule</button><br>';
    $confirmation .= $scheduler->get_schedule_string(false);
    return $confirmation;
  }

  /**
   * This function creates the modify schedule page.
   *
   * This function is responsible for creating and initializing all of the fields
   * that are required in the page for modifying the schedule.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_modify_schedule_page() {
    // prevent form from being created twice
    if (ARIA_API::aria_get_modify_schedule_form_id() !== -1) {
    	return;
    }

    $field_mapping = self::modify_schedule_field_id_array();
    $form = new GF_Form(MOD_SCHEDULE_FORM_NAME, "");
    $form->description = "<h4>Please select from the drop-down menu the competition
    that you would like to modify the schedule for. Once you click on 'Submit',
    the previously saved schedule will be shown. If you have yet to run the scheduler
    for the competition you select, you must do so before using this page.</h4>";

    // drop-down menu of active competitions
    $active_competitions_field = new GF_Field_Select();
    $active_competitions_field->label = "Active Competitions";
    $active_competitions_field->id = $field_mapping['active_competitions'];
    $active_competitions_field->isRequired = false;
    $active_competitions_field->description = "Please select the name of the" .
    " competition that you would like to view the schedule for.";
    $active_competitions_field->descriptionPlacement = "above";
    $active_competitions_field->choices = array("Select from below");
    $form->fields[] = $active_competitions_field;

    // add a default submission message for the doc. gen. form
    $successful_submission_message = 'Congratulations! You have just successfully' .
    ' loaded a previously saved competition schedule.';
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

    // identify form as the scheduling page
    $form_arr = $form->createFormArray();
    $form_arr['isModifyScheduleForm'] = true;

    // add form to dashboard
    $form_id = GFAPI::add_form($form_arr);
    if (is_wp_error($form_id)) {
      wp_die($form_id->get_error_message());
    }
    else {
      $doc_gen_url = ARIA_API::aria_publish_form(MOD_SCHEDULE_FORM_NAME,
                                                 $form_id, CHAIRMAN_PASS, true);
    }
  }

  /**
   * Returns an associative array for field mappings of modify schedule form.
   *
   * This function returns an array that maps all of the names of the
   * fields in the modify schedule form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function modify_schedule_field_id_array() {
    return array(
      'active_competitions' => 1
    );
  }

  /**
   * This function will pre-populate the drop-down menu on the scheduling
   * page with all of the active competitions.
   *
   * Whenever the festival chairman visits the page that is used for adding a
   * teacher, that page needs to have the drop-down menu of active competitions
   * pre-populated. This function is responsible for accomplishing that goal.
   *
   * @param $form 	Form Object 	The current form object.
   * @param $is_ajax 	Bool 	Specifies if the form is submitted via AJAX
   *
   * @since 1.0.0
   * @author KREW
   */
   public static function before_modify_schedule_render($form, $is_ajax) {
     // Only perform prepopulation if it's the modify schedule form
     if (!array_key_exists('isModifyScheduleForm', $form)
         || !$form['isModifyScheduleForm']) {
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

     $field_mapping = self::modify_schedule_field_id_array();
     $search_field = $field_mapping['active_competitions'];
     $name_field = ARIA_API::aria_find_field_by_id($form['fields'], $search_field);
     $form['fields'][$name_field]->choices = $competition_names;
   }
}
