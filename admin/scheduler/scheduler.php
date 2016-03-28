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
    // only perform processing if it's the scheduler form
    if (!array_key_exists('isScheduleForm', $form)
        || !$form['isScheduleForm']) {
          return $confirmation;
    }

    /*
    // successfully reaches this point
    print_r($form);
    wp_die('<h4>Made it to Scheduling Algorithm!</h4>');
    */

    // obtain the attributes from the submitted form
    $scheduling_field_mapping = self::scheduling_page_field_id_array();
    $title = $entry[$scheduling_field_mapping['active_competitions']];
    $time_block_duration = $entry[$scheduling_field_mapping['time_block_duration']];
    $num_time_blocks_sat = $entry[$scheduling_field_mapping['num_time_blocks_sat']];
    $num_time_blocks_sun = $entry[$scheduling_field_mapping['num_time_blocks_sun']];
    $num_concurrent_sections_sat = $entry[$scheduling_field_mapping['num_concurrent_sections_sat']];
    $num_concurrent_sections_sun = $entry[$scheduling_field_mapping['num_concurrent_sections_sun']];
    $num_master_sections_sat = $entry[$scheduling_field_mapping['num_master_sections_sat']];
    $num_master_sections_sun = $entry[$scheduling_field_mapping['num_master_sections_sun']];

    // find the related forms of the competition that the user chose
    $student_master_field_mapping = ARIA_API::aria_master_student_field_id_array();
    $related_form_ids = ARIA_API::aria_find_related_forms_ids($title);
    $student_master_form_id = $related_form_ids['student_master_form_id'];

    /*
    // successfully takes input from form
    echo 'time_block_duration: ' . $time_block_duration . "<br>";
    echo 'num_time_blocks_sat: ' . $num_time_blocks_sat . "<br>";
    echo 'num_time_blocks_sun: ' . $num_time_blocks_sun . "<br>";
    echo 'num_concurrent_sections_sat: ' . $num_concurrent_sections_sat . "<br>";
    echo 'num_concurrent_sections_sun: ' . $num_concurrent_sections_sun . "<br>";
    echo 'num_master_sections_sat: ' . $num_master_sections_sat . "<br>";
    echo 'num_master_sections_sun: ' . $num_master_sections_sun . "<br>";
    wp_die();
    */

    // check to see if a scheduler can be created that can accomodate all students
    // that have registered for the current competition
    if (!can_scheduler_be_created($student_master_form_id,
	                                $num_time_blocks_sat,
																	$num_time_blocks_sun,
																	$time_block_duration,
																	$num_concurrent_sections_sat,
																	$num_concurrent_sections_sun)) {
      wp_die('ERROR: The input parameters entered on the previous page are unable
              to support all students that have registered for this competition.
              Please readjust your input parameters for scheduling and try again.');
    }

    // create scheduler object using input parameters from festival chairman
    $scheduler = new Scheduler(REGULAR_COMP, REGULAR_COMP_NUM_DAYS);
    $scheduler->create_normal_competition($num_time_blocks_sat,
	                                        $num_time_blocks_sun,
	                                        $time_block_duration,
	                                        $num_concurrent_sections_sat,
	                                        $num_concurrent_sections_sun,
	                                        $num_master_sections_sat,
	                                        $num_master_sections_sun);

    // schedule students by age/level
    $playing_times = calculate_playing_times($student_master_form_id);
    $current_either_saturday_total = 0;
    $current_either_sunday_total = 0;
    for ($i = LOW_LEVEL; $i <= HIGH_LEVEL; $i++) {
      $all_students_per_level = self::get_all_students_per_level($student_master_form_id, $i);
      foreach ($all_students_per_level as $student) {
        print_r($student);
        wp_die('<h4>^^^displaying format of student master entry^^^</h4>');

        // obtain student's first and last names
        $first_name = $student[strval($student_master_field_mapping['student_first_name'])];
        $last_name = $student[strval($student_master_field_mapping['student_last_name'])];

        // determine type of student
        $type = $student[strval($student_master_field_mapping['competition_format'])];
        if ($type == "Master Class") {
          $type = SECTION_MASTER;
        }
        else {
          $type = SECTION_OTHER;
        }

        // determine the student's total play time for both songs
        $total_play_time = $student[strval($student_master_field_mapping['timing_of_pieces'])];

        // determine student's day preference
        $day_preference = $student[strval($student_master_field_mapping['day_preference'])];
        if ($day_preference == "Saturday") {
          $day_preference = SAT;
        }
        else if ($day_preference == "Sunday") {
          $day_preference = SUN;
        }
        else {
          $day_preference = EITHER;
        }

        // if the student registered as either, determine which day to add them to
        if ($day_preference === EITHER) {
          if (($playing_times[SAT] + $current_saturday_either_total) < $playing_times[SUN]) {
            $day_preference = SAT;
            $current_either_saturday_total += $total_play_time;
          }
          else {
            $day_preference = SUN;
            $current_either_sunday_total += $total_play_time;
          }
        }

        // determine the student's skill level
        $skill_level = $student[strval($student_master_field_mapping['student_level'])];

        // create a student object based on previously obtained information
        $modified_student = new Student($first_name, $last_name, $type, $day_preference, $skill_level, $total_play_time);

        // add student's first song
        $modified_student->add_song($student[strval($student_master_field_mapping['song_1_selection'])]);

        // add student's second song
        $modified_student->add_song($student[strval($student_master_field_mapping['song_2_selection'])]);

        wp_die(print_r($modified_student));

        // schedule the student
        if (!$scheduler->schedule_student($modified_student)) {
          wp_die('ERROR: Student was unable to be added. Please readjust your
                 input parameters for scheduling and try again.');
        }
      }
    }

    // print the schedule
    $scheduler->print_schedule();
    return $confirmation;

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

    // length of the sections
    $time_block_duration = new GF_Field_Number();
    $time_block_duration->label = "Length of Timeblocks (minutes)";
    $time_block_duration->id = $field_mapping['time_block_duration'];
    $time_block_duration->isRequired = true;
    $time_block_duration->description = "NOTE: This should include time for students";
    $time_block_duration->description .= " to perform, for judges to take notes after";
    $time_block_duration->description .= " each student performs, for proctors to introduce the judge(s),";
    $time_block_duration->description .= " and for any other section-related events to take place.";
    $time_block_duration->descriptionPlacement = "above";
    $form->fields[] = $time_block_duration;

    // number of timeblocks on Saturday
    $num_time_blocks_sat = new GF_Field_Number();
    $num_time_blocks_sat->label = "Number of Timeblocks on Saturday";
    $num_time_blocks_sat->id = $field_mapping['num_time_blocks_sat'];
    $num_time_blocks_sat->isRequired = true;
    $num_time_blocks_sat->description = "NOTE: Each timeblock will be assigned";
    $num_time_blocks_sat->description .= " the amount of minutes entered in the";
    $num_time_blocks_sat->description .= " 'Length of Timeblocks (minutes)'";
    $num_time_blocks_sat->description .= " section above.";
    $num_time_blocks_sat->descriptionPlacement = "above";
    $form->fields[] = $num_time_blocks_sat;

    // number of timeblocks on Sunday
    $num_time_blocks_sun = new GF_Field_Number();
    $num_time_blocks_sun->label = "Number of Timeblocks on Sunday";
    $num_time_blocks_sun->id = $field_mapping['num_time_blocks_sun'];
    $num_time_blocks_sun->isRequired = true;
    $num_time_blocks_sun->description = "NOTE: Each timeblock will be assigned";
    $num_time_blocks_sun->description .= " the amount of minutes entered in the";
    $num_time_blocks_sun->description .= " 'Length of Timeblocks (minutes)'";
    $num_time_blocks_sun->description .= " section above.";
    $num_time_blocks_sun->descriptionPlacement = "above";
    $form->fields[] = $num_time_blocks_sun;

    // number of concurrent sections on saturday
    $num_concurrent_sections_sat = new GF_Field_Number();
    $num_concurrent_sections_sat->label = "Number of Concurrent Sections on Saturday";
    $num_concurrent_sections_sat->id = $field_mapping['num_concurrent_sections_sat'];
    $num_concurrent_sections_sat->isRequired = true;
    $form->fields[] = $num_concurrent_sections_sat;

    // number of concurrent sections on sunday
    $num_concurrent_sections_sun = new GF_Field_Number();
    $num_concurrent_sections_sun->label = "Number of Concurrent Sections on Sunday";
    $num_concurrent_sections_sun->id = $field_mapping['num_concurrent_sections_sun'];
    $num_concurrent_sections_sun->isRequired = true;
    $form->fields[] = $num_concurrent_sections_sun;

    // number of master-class sections on saturday
    $num_master_sections_sat = new GF_Field_Number();
    $num_master_sections_sat->label = "Number of Masterclass Sections on Saturday";
    $num_master_sections_sat->id = $field_mapping['num_master_sections_sat'];
    $num_master_sections_sat->isRequired = true;
    $form->fields[] = $num_master_sections_sat;

    // number of master-class sections on sunday
    $num_master_sections_sun = new GF_Field_Number();
    $num_master_sections_sun->label = "Number of Masterclass Sections on Sunday";
    $num_master_sections_sun->id = $field_mapping['num_master_sections_sun'];
    $num_master_sections_sun->isRequired = true;
    $form->fields[] = $num_master_sections_sun;

    // number of judges per section
      // not done yet

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
   *
   * This function returns an array that maps all of the names of the
   * fields in the scheduler form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function scheduling_page_field_id_array() {
    return array(
      'active_competitions' => 1,
      'time_block_duration' => 2,
      'num_time_blocks_sat' => 3,
      'num_time_blocks_sun' => 4,
      'num_concurrent_sections_sat' => 5,
      'num_concurrent_sections_sun' => 6,
      'num_master_sections_sat' => 7,
      'num_master_sections_sun' => 8
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
    $name_field = ARIA_API::aria_find_field_by_id($form['fields'], $search_field);
    $form['fields'][$name_field]->choices = $competition_names;
  }

  /**
   * This function will check to see if a scheduler object can be created that
   * successfully accomodates all students in this competition given the
   * parameters that the festival chairman entered on the scheduler page.
   *
   * Often times, the festival chairman will enter parameters into the
   * scheduler page that are unable to accomodate the amount of students in a
   * given competition. This function is responsible for ensuring that these
   * parameters will facilitate the registered students in the competition.
   *
   * @param	$student_master_form_id	int	The student master form of the given competition.
   * @param	int	$num_time_blocks_sat	The number of time blocks on saturday.
   * @param	int	$num_time_blocks_sun	The number of time blocks on sunday.
   * @param	int	$time_block_duration	The amount of time allocated to each timeblock.
   * @param	int	$num_concurrent_sections_sat	The number of sections/timeblock on saturday.
   * @param	int	$num_concurrent_sections_sun	The number of sections/timeblock on sunday.
   *
   * @return	bool 	true if the scheduler object can be created, false otherwise
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function can_scheduler_be_created($student_master_form_id,
                                                   $num_time_blocks_sat,
																								   $num_time_blocks_sun,
																								   $time_block_duration,
																								   $num_concurrent_sections_sat,
																								   $num_concurrent_sections_sun) {
    // determine the total amount of play time for all students in the current competition
    $student_master_field_mapping = ARIA_API::aria_master_student_field_id_array();
    $total_play_time_students = 0;
    for ($i = LOW_LEVEL; $i <= HIGH_LEVEL; $i++) {
      $all_students_per_level = self::get_all_students_per_level($student_master_form_id, $i);
      foreach ($all_students_per_level as $student) {
        $total_play_time_students += $student[$student_master_field_mapping['timing_of_pieces']];
      }
    }

    // calculate total time for competition based on festival chairman input
    $music_time_limit = ceil($time_block_duration * 0.8); // judging requires 20% of section time
    $total_time_saturday = $num_time_blocks_sat * $num_concurrent_sections_sat * $music_time_limit;
    $total_time_sunday = $num_time_blocks_sun * $num_concurrent_sections_sun * $music_time_limit;

    // check if the student play time is greater than the time available based
    // on the festival chairman's input
    if ($total_play_time_students > ($total_time_saturday + $total_time_sunday)) {
      return false;
    }

    return true;
  }

  /**
   * This function will return an associative array with all the students in a
   * given level.
   *
   * The associative array will return sorted by age.
   *
   * @param	$student_master_form_id	int	The student master form of the given competition.
   * @param	$level_num	int	The level to acquire all students from.
   *
   * @return	array 	An associative array of all students in the level.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function get_all_students_per_level($student_master_form_id, $level_num) {
    $student_master_field_mapping = ARIA_API::aria_master_student_field_id_array();

    // define the search criteria
    $sorting = array(
      'key' => $student_master_field_mapping['student_birthday'],
      'direction' => 'ASC',
      'is_numeric' => true
    );
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $search_criteria = array(
      'field_filters' => array(
        'mode' => 'any',
        array(
          'key' => $student_master_field_mapping['student_level'],
          'value' => $level_num
        )
      )
    );

    $all_students_per_level = GFAPI::get_entries($student_master_form_id,
                                                 $search_criteria, $sorting,
                                                 $paging, $total_count);

    if (is_wp_error($all_students_per_level)) {
      wp_die($all_students_per_level->get_error_message());
    }
    else {
      return $all_students_per_level;
    }
  }

  /**
   * This function will return an associative array with the total playing
   * times for students that requested saturday, sunday, and either.
   *
   * @param	$student_master_form_id	int	The student master form of the given competition.
   *
   * @return	array 	An associative array with playing times of all students in the competition.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function calculate_playing_times($student_master_form_id) {
    $student_master_field_mapping = ARIA_API::aria_master_student_field_id_array();
    $playing_times = array(
      SAT => 0,
      SUN => 0,
      EITHER => 0
    );

    // iterate through all levels of the competition and calculate playing time based on day
    for ($i = LOW_LEVEL; $i <= HIGH_LEVEL; $i++) {
      $all_students_per_level = self::get_all_students_per_level($student_master_form_id, $i);
      foreach ($all_students_per_level as $student) {
        $day_preference = $student[strval($student_master_field_mapping['day_preference'])];
        $total_play_time = $student[strval($student_master_field_mapping['timing_of_pieces'])];
        if ($day_preference == "Saturday") {
          $all_students_per_level[SAT] += $total_play_time;
        }
        else if ($day_preference == "Saturday") {
          $all_students_per_level[SUN] += $total_play_time;
        }
        else {
          $all_students_per_level[EITHER] += $total_play_time;
        }
      }
    }

    return $playing_times;
  }
}
