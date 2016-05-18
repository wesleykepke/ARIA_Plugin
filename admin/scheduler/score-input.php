<?php

/**
 * The score input page.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

class Score_Input {

  /**
   * This function handles processing after the festival chairman has elected to
   * input scores for a given competition.
   *
   * This function will present the user with a score input form of whatever
   * competition they select (provided the scheduler has already ran for the
   * competition they select).
   *
   * @param 	Entry Object  $entry  The entry that was just submitted.
   * @param 	Form Object   $form   The form used to submit entries.
   * @param 	String/Array 	$confirmation 	The confirmation message to be filtered.
   * @param 	Bool 	$ajax 	Specifies if this form is configured to be submitted via AJAX.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function render_score_input_form($confirmation, $form, $entry, $ajax) {
    // only perform processing if it's the score input form
    if (!array_key_exists('isScoreInputForm', $form)
        || !$form['isScoreInputForm']) {
      return $confirmation;
    }

    // determine which competition to render the schedule for
    $field_mapping = self::score_input_field_id_array();
    $title = $entry[strval($field_mapping['active_competitions'])];
    $related_forms = ARIA_API::aria_find_related_forms_ids($title);

    // locate the serialized version of the scheduler object
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
    $confirmation .= '<h1 id="comp-name"><b id="comp-name-bold">' . $non_formatted_title . '</b></h1>';
    $confirmation .= "<h4>Congratulations! You have just successfully loaded a
    previously generated schedule of students.<br>After you input scores under each student,
    <b>you must click the 'Save Scores' button</b>, otherwise, the scores will be lost.
    Please note that you only need to select a song for students who receive either a
    'Superior with Distinction' or 'Superior' result.</h4>";
    $confirmation .= '<button id="saveScoresButton" type="button" onclick="sendScoresToServer()">Save Scores</button><br>';
    $confirmation .= '<button id="printTrophyListButton" type="button" onclick="printTrophyList()">Print Trophy List</button><br>';
    $confirmation .= $scheduler->get_score_input_string(false);
    return $confirmation;
  }

  /**
   * This function creates the score input page.
   *
   * This function is responsible for creating and initializing all of the fields
   * that are required in the page for saving scores.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_score_input_page() {
    // prevent form from being created twice
    if (ARIA_API::aria_get_score_input_form_id() !== -1) {
    	return;
    }

    $field_mapping = self::score_input_field_id_array();
    $form = new GF_Form(SCORE_INPUT_FORM_NAME, "");
    $form->description = "<h4>Please select from the drop-down menu the competition
    that you would like to input scores for. Once you click on 'Submit',
    the previously input scores will be shown (or will be empty if no scores have
    been added yet). If you have yet to run the scheduler for the competition you
    select, you must do so before using this page.</h4>";

    // drop-down menu of active competitions
    $active_competitions_field = new GF_Field_Select();
    $active_competitions_field->label = "Active Competitions";
    $active_competitions_field->id = $field_mapping['active_competitions'];
    $active_competitions_field->isRequired = false;
    $active_competitions_field->description = "Please select the name of the" .
    " competition that you would like to input scores for.";
    $active_competitions_field->descriptionPlacement = "above";
    $active_competitions_field->choices = array("Select from below");
    $form->fields[] = $active_competitions_field;

    // add a default submission message for the doc. gen. form
    $successful_submission_message = 'Congratulations! You have just successfully' .
    ' loaded the score input form.';
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

    // identify form as the scheduling page
    $form_arr = $form->createFormArray();
    $form_arr['isScoreInputForm'] = true;

    // add form to dashboard
    $form_id = GFAPI::add_form($form_arr);
    if (is_wp_error($form_id)) {
      wp_die($form_id->get_error_message());
    }
    else {
      $doc_gen_url = ARIA_API::aria_publish_form(SCORE_INPUT_FORM_NAME,
                                                 $form_id, CHAIRMAN_PASS, true);
    }
  }

  /**
   * Returns an associative array for field mappings of score input form.
   *
   * This function returns an array that maps all of the names of the
   * fields in the score input form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function score_input_field_id_array() {
    return array(
      'active_competitions' => 1
    );
  }

  /**
   * This function will pre-populate the drop-down menu on the score input
   * page with all of the active competitions.
   *
   * Whenever the festival chairman visits the page that is used for adding student
   * scores, that page needs to have the drop-down menu of active competitions
   * pre-populated. This function is responsible for accomplishing that goal.
   *
   * @param $form 	Form Object 	The current form object.
   * @param $is_ajax 	Bool 	Specifies if the form is submitted via AJAX
   *
   * @since 1.0.0
   * @author KREW
   */
   public static function before_score_input_render($form, $is_ajax) {
     // Only perform prepopulation if it's the modify schedule form
     if (!array_key_exists('isScoreInputForm', $form)
         || !$form['isScoreInputForm']) {
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

     $field_mapping = self::score_input_field_id_array();
     $search_field = $field_mapping['active_competitions'];
     $name_field = ARIA_API::aria_find_field_by_id($form['fields'], $search_field);
     $form['fields'][$name_field]->choices = $competition_names;
   }
}
