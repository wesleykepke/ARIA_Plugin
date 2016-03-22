<?php

/**
 * The scheduling algorithm.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ARIA_ROOT . "/includes/class-aria-api.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-scheduler.php");

class Scheduling_Algorithm {

  /**
   * This function encapsulates the scheduling algorithm.
   *
   * This function implements the scheduling algorithm that is used to
   * schedule students for a given competition.
   *
   * More details soon!
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_scheduling_algorithm($confirmation, $form, $entry, $ajax) {
    // Only perform processing if it's the scheduler form
    if (!array_key_exists('isScheduleForm', $form)
        || !$form['isScheduleForm']) {
          return;
    }

    $student_master_field_mapping = ARIA_API::aria_master_student_field_id_array();
    $scheduling_field_mapping = self::scheduling_page_field_id_array();
    $title = $entry[$scheduling_field_mapping['active_competitions']];
    $related_form_ids = ARIA_API::aria_find_related_forms_ids($title);
    $student_master_form_id = $related_form_ids['student_master_form_id'];

    // create scheduler object based on number of participants
    //$scheduler = self::aria_create_scheduler_object($student_master_form_id);
    //wp_die(print_r($scheduler));

    $scheduler = new Scheduler(2, 6, 2);

    // iterate through all levels (1-11)
    for ($i = 1; $i <= 11; $i++) {
      $search_criteria = array(
        'field_filters' => array(
          'mode' => 'any',
          array(
            'key' => $student_master_field_mapping['student_level'],
            'value' => $i
          )
        )
      );

      // schedule all students in a given level
$sorting         = array();
$paging          = array( 'offset' => 0, 'page_size' => 200 );
$total_count     = 0;
      $all_students_per_level = GFAPI::get_entries($student_master_form_id, $search_criteria, $sorting, $paging, $total_count);

/*
if ($i === 11) {
  wp_die(count($all_students_per_level));
}
*/

      foreach ($all_students_per_level as $student) {

        //print_r($student);

        // obtain student's first and last names
        $first_name = $student[strval($student_master_field_mapping['student_first_name'])];
        $last_name = $student[strval($student_master_field_mapping['student_last_name'])];

        // determine type of student
        $type = $student[strval($student_master_field_mapping['competition_format'])];

        // determine the student's day preference
        $day_preference = $student[strval($student_master_field_mapping['available_festival_days_saturday'])];
        if (empty($student[strval($student_master_field_mapping['available_festival_days_saturday'])])) {
          $day_preference = $student[strval($student_master_field_mapping['available_festival_days_sunday'])];
        }

        //$day_preference = mt_rand(0, 1);

        // determine the student's skill level
        $skill_level = $student[strval($student_master_field_mapping['student_level'])];

        // create a student object based on previously obtained information
        $modified_student = new Student($first_name, $last_name, $type, $day_preference, $skill_level);

        // add student's first song
/*
        $modified_student->add_song($student[strval($student_master_field_mapping['song_1_selection'])],
          $student[strval($student_master_field_mapping['timing_of_pieces'])]);
*/

        $modified_student->add_song('Wesley Song One', mt_rand(6, 15));
        $modified_student->add_song('Wesley Song Two', 0);

        // add student's second song
        //$modified_student->add_song($student[strval($student_master_field_mapping['song_1_selection'])], 0);

        //wp_die(print_r($modified_student));

        // schedule the student
        if ($scheduler->schedule_student($modified_student)) {
          //wp_die('student added!?');
        }
        else {
          //wp_die('student not added');
        }
      }
    }

    //wp_die();
    $scheduler->print_schedule();
  }

  /**
	 * This function defines and creates the scheduling page (front-end).
	 *
	 * @link       http://wesleykepke.github.io/ARIA/
	 * @since      1.0.0
	 *
	 * @package    ARIA
	 * @subpackage ARIA/includes
	 */
  public static function aria_create_scheduling_page() {
    // prevent form from being created/published twice
    if (ARIA_API::aria_get_scheduler_form_id() !== -1) {
      return;
    }

    $field_mapping = self::scheduling_page_field_id_array();
    $form = new GF_Form(SCHEDULER_FORM_NAME, "");

    // drop-down menu of active competitions
    $active_competitions_field = new GF_Field_Select();
    $active_competitions_field->label = "Active Competitions";
    $active_competitions_field->id = $field_mapping['active_competitions'];
    $active_competitions_field->isRequired = false;
    $active_competitions_field->description = "Please select the name of the";
    $active_competitions_field->description .= " competition that you would";
    $active_competitions_field->description .= " like to schedule.";
    $active_competitions_field->descriptionPlacement = "above";
    $active_competitions_field->choices = array("Select from below");
    $form->fields[] = $active_competitions_field;

    // identify form as the scheduling page
    $form_arr = $form->createFormArray();
    $form_arr['isScheduleForm'] = true;

    // add form to dashboard
    $form_id = GFAPI::add_form($form_arr);
    if (is_wp_error($form_id)) {
      wp_die($form_id->get_error_message());
    }
    else {
      $scheduler_url = ARIA_API::aria_publish_form(SCHEDULER_FORM_NAME, $form_id, CHAIRMAN_PASS);
    }
  }

  /**
   * Returns an associative array for field mappings of scheduler form.
   */
  private static function scheduling_page_field_id_array() {
    return array(
      'active_competitions' => 1
    );
  }

  /**
   * This function will pre-populate the drop-down menu on the teacher upload
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
  public static function before_schedule_render($form, $is_ajax) {
    // Only perform prepopulation if it's the scheduler form
    if (!array_key_exists('isScheduleForm', $form)
        || !$form['isScheduleForm']) {
          return;
    }

    // Get all of the active competitions
    $competition_field_mapping = ARIA_API::aria_competition_field_id_array();
    $competition_form_id = ARIA_API::aria_get_create_competition_form_id();
    $entries = GFAPI::get_entries($competition_form_id);
    $competition_names = array();
    foreach ($entries as $entry) {
      $single_competition = array(
        'text' => $entry[$competition_field_mapping['competition_name']],
        'value' => $entry[$competition_field_mapping['competition_name']],
        'isSelected' => false
      );
      $competition_names[] = $single_competition;
      unset($single_competition);
    }

    $scheduling_field_mapping = self::scheduling_page_field_id_array();
    $search_field = $scheduling_field_mapping['active_competitions'];
    $name_field = self::aria_find_field_by_id($form['fields'], $search_field);
    $form['fields'][$name_field]->choices = $competition_names;
  }

  /**
   * This function will find the field number with the specified ID.
   *
   * The function will search through the given array of fields and
   * locate the field with the given ID number. The ID of the field
   * is then returned.
   * @param $fields   Array   The array of fields to search through
   * @param $id       Float     The id of the array to search for
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_find_field_by_id( $fields, $id ){
    $field_num = 0;
    foreach($fields as $key){
      if($fields[$field_num]['id'] == $id){
        return $field_num;
      }
      $field_num++;
    }
    return null;
  }

  /**
   * This function will create the scheduler object used for scheduling.
   *
   * This function will calculate the number of concurrent sections that are
   * required to schedule all students that have registered for a music
   * competition. Using this calculating, a custom scheduler object will be
   * created and returned to the callee.
   *
   * @param $student_master_form_id 	Integer 	The form ID of the student master
   *
   * @return $scheduler 	Scheduler Object	The newly created scheduler object.
   *
   * @since 1.0.0
   * @KREW
   */
  private static function aria_create_scheduler_object($student_master_form_id) {
    $field_mapping = ARIA_API::aria_master_student_field_id_array();
    $total_play_time = 0;
    $total_play_time_per_level = 0;

    // iterate through all registered students and calulate the total playing duration
    for ($i = 1; $i <= 11; $i++) {
      $search_criteria = array(
        'field_filters' => array(
          'mode' => 'any',
          array(
            'key' => $field_mapping['student_level'],
            'value' => $i
          )
        )
      );

      $all_students_per_level = GFAPI::get_entries($student_master_form_id, $search_criteria);
      foreach ($all_students_per_level as $student ) {
        $total_play_time_per_level += $student[strval($field_mapping['timing_of_pieces'])];
      }

      $total_play_time += $total_play_time_per_level;
    }

/*
    echo "Total play time: " . $total_play_time . "<br>";
    wp_die();
*/

    // Calculate the number of sections required
    $num_sections = ceil(floatval($total_play_time / SECTION_TIME_LEN));
    $num_sections_per_timeblock = floatval($num_sections / NUM_SECTIONS_PER_DAY);
    $num_concurrent_sections = ceil($num_sections_per_timeblock);

    $scheduler = new Scheduler(NUM_FESTIVAL_DAYS, NUM_SECTIONS_PER_DAY, $num_concurrent_sections);
    return $scheduler;
  }
}
