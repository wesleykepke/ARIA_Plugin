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

    // obtain the attributes from the submitted form
    $scheduling_field_mapping = self::scheduling_page_field_id_array();
    $title = $entry[$scheduling_field_mapping['active_competitions']];
    $time_block_duration = $entry[$scheduling_field_mapping['time_block_duration']];
    $num_time_blocks_sat = $entry[$scheduling_field_mapping['num_time_blocks_sat']];
    $num_time_blocks_sun = $entry[$scheduling_field_mapping['num_time_blocks_sun']];
    $sat_start_times = unserialize($entry[$scheduling_field_mapping['sat_start_times']]);
    $sun_start_times = unserialize($entry[$scheduling_field_mapping['sun_start_times']]);
    $both_start_times = array_merge($sat_start_times, $sun_start_times);    
    $num_concurrent_sections_sat = $entry[$scheduling_field_mapping['num_concurrent_sections_sat']];
    $num_concurrent_sections_sun = $entry[$scheduling_field_mapping['num_concurrent_sections_sun']];
    $num_master_sections_sat = $entry[$scheduling_field_mapping['num_master_sections_sat']];
    $num_master_sections_sun = $entry[$scheduling_field_mapping['num_master_sections_sun']];
    $song_threshold = $entry[$scheduling_field_mapping['song_threshold']];
    $group_by_level = $entry[$scheduling_field_mapping['group_by_level']];
    $master_class_instructor_duration = $entry[$scheduling_field_mapping['master_class_instructor_duration']];
    $num_judges_per_section = $entry[$scheduling_field_mapping['num_judges_per_section']];
    $saturday_rooms = unserialize($entry[$scheduling_field_mapping['saturday_rooms']]);
    $sunday_rooms = unserialize($entry[$scheduling_field_mapping['sunday_rooms']]);
    $both_days_rooms = array_merge($saturday_rooms, $sunday_rooms); 

    // find the related forms of the competition that the user chose
    $student_master_field_mapping = ARIA_API::aria_master_student_field_id_array();
    $related_form_ids = ARIA_API::aria_find_related_forms_ids($title);
    $student_master_form_id = $related_form_ids['student_master_form_id'];

    //print_r($sat_start_times);
    //print_r($sun_start_times);

    /*
    // successfully takes input from form
    echo 'time_block_duration: ' . $time_block_duration . "<br>";
    echo 'num_time_blocks_sat: ' . $num_time_blocks_sat . "<br>";
    echo 'num_time_blocks_sun: ' . $num_time_blocks_sun . "<br>";
    echo 'sat_start_times: ' . $sat_start_times . "<br>";
    echo 'sun_start_times: ' . $sun_start_times . "<br>";
    echo 'num_concurrent_sections_sat: ' . $num_concurrent_sections_sat . "<br>";
    echo 'num_concurrent_sections_sun: ' . $num_concurrent_sections_sun . "<br>";
    echo 'num_master_sections_sat: ' . $num_master_sections_sat . "<br>";
    echo 'num_master_sections_sun: ' . $num_master_sections_sun . "<br>";
    echo 'song_threshold: ' . $song_threshold . "<br>";
    echo 'group_by_level: ' . $group_by_level . "<br>";
    echo 'master_class_instructor_duration: ' . $master_class_instructor_duration . "<br>";
    wp_die();
    */

    // check to see if a scheduler can be created that can accomodate all students
    // that have registered for the current competition
    self::can_scheduler_be_created($student_master_form_id,
                                   $num_time_blocks_sat,
                                   $num_time_blocks_sun,
                                   $sat_start_times,
                                   $sun_start_times,
                                   $time_block_duration,
                                   $num_concurrent_sections_sat,
                                   $num_concurrent_sections_sun,
                                   $num_master_sections_sat,
                                   $num_master_sections_sun,
                                   $master_class_instructor_duration);

    // create scheduler object using input parameters from festival chairman
    $scheduler = new Scheduler(REGULAR_COMP);
    if (strcmp($group_by_level, 'Yes') == 0) {
      $group_by_level = true;
    }
    else {
      $group_by_level = false;
    }
    $scheduler->create_normal_competition($num_time_blocks_sat,
                                          $num_time_blocks_sun,
                                          $time_block_duration,
                                          $both_start_times,
                                          $num_concurrent_sections_sat,
                                          $num_concurrent_sections_sun,
                                          $num_master_sections_sat,
                                          $num_master_sections_sun,
                                          $song_threshold,
                                          $group_by_level,
                                          $master_class_instructor_duration,
                                          $saturday_rooms,
                                          $sunday_rooms);

    // schedule all students that are registered for the current competition
    $playing_times = self::calculate_playing_times($student_master_form_id);
    $current_either_saturday_total = 0;
    $current_either_sunday_total = 0;
    for ($i = LOW_LEVEL; $i <= HIGH_LEVEL; $i++) {
      $all_students_per_level = self::get_all_students_per_level($student_master_form_id, $i);
      foreach ($all_students_per_level as $student) {
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
        $day_preference = $student[strval($student_master_field_mapping['available_festival_days'])];
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
          if (($playing_times[SAT] + $current_either_saturday_total) < $playing_times[SUN]) {
            $day_preference = SAT;
            $current_either_saturday_total += intval($total_play_time);
          }
          else {
            $day_preference = SUN;
            $current_either_sunday_total += intval($total_play_time);
          }
        }

        // determine the student's skill level
        $skill_level = $student[strval($student_master_field_mapping['student_level'])];

        // determine the email address of the student's parent
        $parent_email = $student[strval($student_master_field_mapping['parent_email'])];

        // determine the email address of the student's teacher
        $teacher_email = ARIA_API::get_teacher_email($student[strval($student_master_field_mapping['teacher_name'])],
                                                     $related_form_ids['teacher_master_form_id']);

        // determine the student's teacher's name
        $teacher_name = $student[strval($student_master_field_mapping['teacher_name'])];

        // create a student object based on previously obtained information
        $modified_student = new Student($first_name, $last_name, $type,
                                        $day_preference, $skill_level,
                                        $total_play_time, $teacher_email, 
                                        $parent_email, $teacher_name);

        // add student's first song
        $modified_student->add_song($student[strval($student_master_field_mapping['song_1_selection'])]);

        // add composer of student's first song
        $modified_student->add_composer($student[strval($student_master_field_mapping['song_1_composer'])]);

        // add student's second song
        $modified_student->add_song($student[strval($student_master_field_mapping['song_2_selection'])]);

        // add composer of student's second song
        $modified_student->add_composer($student[strval($student_master_field_mapping['song_2_composer'])]);


        // schedule the student
        if (!$scheduler->schedule_student($modified_student)) {
          wp_die('ERROR: Student was unable to be added. Please readjust your
                 input parameters for scheduling and try again.');
        }
      }
    }

    // assign the judges for the competition
    $judges = self::determine_judges($related_form_ids['teacher_master_form_id']);
    $proctors = self::determine_proctors($related_form_ids['teacher_master_form_id']);
    //wp_die(print_r($proctors));
    $scheduler->assign_judges($judges, $num_judges_per_section);
    $scheduler->assign_proctors($proctors);

    // automatically write the scheduler object to a file
    self::save_scheduler_to_file($title, $scheduler);

    // print the schedule to the festival chairman
    $confirmation = "<h4>Don't worry about saving your schedule. ARIA will automatically save the most"
    . " recently generated schedule so you can return to it later for document generation.</h4><br>";
    $confirmation .= $scheduler->get_schedule_string();
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
    $time_block_duration->description = "NOTE: This should include time for students"
    . " to perform, for judges to take notes after each student performs, for proctors"
    . " to introduce the judge(s), and for any other section-related events to take place."
    . "<b> 80% of the time that you enter will be reserved for students to perform.</b>"
    . " For example, if you enter 60 minutes, then there will be 48 minutes reserved"
    . " for students to perform per section. The remaining 12 minutes will be for"
    . " judging, having the proctor switch students, etc.";
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

    // start times of timeblocks on Saturday
    $sat_start_times = new GF_Field_List();
    $sat_start_times->label = "Saturday Timeblock Starting Times";
    $sat_start_times->id = $field_mapping['sat_start_times'];
    $sat_start_times->isRequired = true;
    $sat_start_times->description = "NOTE: Please enter the starting times" .
    " of each timeblock on Saturday (9:00 AM, 9:30 AM, 1:30 PM, etc.).";
    $sat_start_times->descriptionPlacement = "above";
    $form->fields[] = $sat_start_times;


    // start times of timeblocks on Sunday
    $sun_start_times = new GF_Field_List(); 
    $sun_start_times->label = "Sunday Timeblock Starting Times";
    $sun_start_times->id = $field_mapping['sun_start_times'];
    $sun_start_times->isRequired = true;
    $sun_start_times->description = "NOTE: Please enter the starting times" .
    " of each timeblock on Sunday (9:00 AM, 9:30 AM, 1:30 PM, etc.).";
    $sun_start_times->descriptionPlacement = "above";
    $form->fields[] = $sun_start_times;

    // number of concurrent sections on saturday
    $num_concurrent_sections_sat = new GF_Field_Number();
    $num_concurrent_sections_sat->label = "Number of Concurrent Sections on Saturday";
    $num_concurrent_sections_sat->id = $field_mapping['num_concurrent_sections_sat'];
    $num_concurrent_sections_sat->isRequired = true;
    $num_concurrent_sections_sat->description = "NOTE: This value will correspond"
    . " to the number of concurrent sections per timeblock on Saturday.";
    $num_concurrent_sections_sat->descriptionPlacement = "above";
    $form->fields[] = $num_concurrent_sections_sat;

    // number of concurrent sections on sunday
    $num_concurrent_sections_sun = new GF_Field_Number();
    $num_concurrent_sections_sun->label = "Number of Concurrent Sections on Sunday";
    $num_concurrent_sections_sun->id = $field_mapping['num_concurrent_sections_sun'];
    $num_concurrent_sections_sun->isRequired = true;
    $num_concurrent_sections_sun->description = "NOTE: This value will correspond"
    . " to the number of concurrent sections per timeblock on Sunday.";
    $num_concurrent_sections_sun->descriptionPlacement = "above";
    $form->fields[] = $num_concurrent_sections_sun;

    // number of master-class sections on saturday
    $num_master_sections_sat = new GF_Field_Number();
    $num_master_sections_sat->label = "Total Number of Masterclass Sections on Saturday";
    $num_master_sections_sat->id = $field_mapping['num_master_sections_sat'];
    $num_master_sections_sat->isRequired = true;
    $num_master_sections_sat->description = "NOTE: This value will determine"
    . " how many sections on Saturday are reserved for masterclass students.";
    $num_master_sections_sat->descriptionPlacement = "above";
    $form->fields[] = $num_master_sections_sat;

    // number of master-class sections on sunday
    $num_master_sections_sun = new GF_Field_Number();
    $num_master_sections_sun->label = "Total Number of Masterclass Sections on Sunday";
    $num_master_sections_sun->id = $field_mapping['num_master_sections_sun'];
    $num_master_sections_sun->isRequired = true;
    $num_master_sections_sun->description = "NOTE: This value will determine"
    . " how many sections on Sunday are reserved for masterclass students.";
    $num_master_sections_sun->descriptionPlacement = "above";
    $form->fields[] = $num_master_sections_sun;

    // threshold for number of times a song appears per section
    $song_threshold = new GF_Field_Number();
    $song_threshold->label = "How many times would you like a song to appear per section?";
    $song_threshold->id = $field_mapping['song_threshold'];
    $song_threshold->isRequired = true;
    $song_threshold->description = "Often times, repeatedly hearing the same song"
    . " in a single section can be overwhelming. The value entered here will be"
    . " the maximum number of times a song is allowed to be played in a single section."
    . " If you don't mind repeated songs per section, please enter 0.";
    $song_threshold->descriptionPlacement = "above";
    $form->fields[] = $song_threshold;

    // seperate by level option
    $group_by_level = new GF_Field_Radio();
    $group_by_level->label = "Would you like to group students by level within a section?";
    $group_by_level->id = $field_mapping['group_by_level'];
    $group_by_level->description = "If you select yes, then sections will only"
    . " contain students from a single level. This may mean that some sections will"
    . " only have a small number of students playing in them and therefore"
    . " will not be filled to capacity. However, if you select"
    . " no, then sections will have students from multiple levels (usually will only"
    . " vary by one level; so, for example, a section with level 6 students could"
    . " have a few level 7 students and most likely no level 8 students).";
    $group_by_level->descriptionPlacement = "above";
    $group_by_level->choices = array(
      array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
      array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $form->fields[] = $group_by_level;

    // master class section - determine how much time judge works with student
    $master_class_instructor_duration = new GF_Field_Number();
    $master_class_instructor_duration->label = "Masterclass Adjudicator Instruction Time";
    $master_class_instructor_duration->id = $field_mapping['master_class_instructor_duration'];
    $master_class_instructor_duration->description = "How much time will the adjudicator work"
    . " with students during the master class sections?";
    $master_class_instructor_duration->descriptionPlacement = "above";
    $form->fields[] = $master_class_instructor_duration;

    // number of judges per section
    $num_judges_per_section = new GF_Field_Number();
    $num_judges_per_section->label = "Number of Judges per Section";
    $num_judges_per_section->id = $field_mapping['num_judges_per_section'];
    $num_judges_per_section->description = "How many judges should be assigned to a section?";
    $num_judges_per_section->descriptionPlacement = "above";
    $form->fields[] = $num_judges_per_section;

    // custom room names for saturday
    $saturday_rooms = new GF_Field_List();
    $saturday_rooms->label = "Saturday Room Names/Numbers";
    $saturday_rooms->id = $field_mapping['saturday_rooms'];
    $saturday_rooms->description = "If you know the specific room names/numbers for your" .
    " competition venue on Saturday, feel free to enter the names/numbers here. If you do" .
    " not know the names, ARIA will provide default room names for the schedule.";
    $saturday_rooms->descriptionPlacement = "above";
    $form->fields[] = $saturday_rooms;

    // custom room names for sunday
    $sunday_rooms = new GF_Field_List();
    $sunday_rooms->label = "Sunday Room Names/Numbers";
    $sunday_rooms->id = $field_mapping['sunday_rooms'];
    $sunday_rooms->description = "If you know the specific room names/numbers for your" .
    " competition venue on Sunday, feel free to enter the names/numbers here. If you do" .
    " not know the names, ARIA will provide default room names for the schedule.";
    $sunday_rooms->descriptionPlacement = "above";
    $form->fields[] = $sunday_rooms;

    // add a default submission message for the schedule competition form
    $successful_submission_message = 'Congratulations! You have just';
    $successful_submission_message .= ' successfully scheduled a competition.';
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

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
      'sat_start_times' => 5,
      'sun_start_times' => 6,
      'num_concurrent_sections_sat' => 7,
      'num_concurrent_sections_sun' => 8,
      'num_master_sections_sat' => 9,
      'num_master_sections_sun' => 10,
      'song_threshold' => 11,
      'group_by_level' => 12,
      'master_class_instructor_duration' => 13,
      'num_judges_per_section' => 14,
      'saturday_rooms' => 15,
      'sunday_rooms' => 16
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
  public static function before_schedule_render($form, $is_ajax) {
    // Only perform prepopulation if it's the scheduler form
    if (!array_key_exists('isScheduleForm', $form)
        || !$form['isScheduleForm']) {
          return;
    }

    // Get all of the active competitions
    $all_active_competitions = ARIA_API::aria_get_all_active_comps();
    $competition_names = array();
    foreach ($all_active_competitions as $competition) {
      $single_competition = array(
        'text' => $competition,
        'value' => $competition,
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
   * @param Array   $sat_start_times    The array of Saturday timeblock starting times.
   * @param Array   $sun_start_times    The array of Sunday timeblock starting times.
   * @param	int	$time_block_duration	The amount of time allocated to each timeblock.
   * @param	int	$num_concurrent_sections_sat	The number of sections/timeblock on saturday.
   * @param	int	$num_concurrent_sections_sun	The number of sections/timeblock on sunday.
   * @param	int	$num_master_sections_sat	The number of master-class sections on saturday.
   * @param	int	$num_master_sections_sun	The number of master-class sections on sunday.
   * @param	int 	$master_class_instructor_duration 	The time that each judge has to spend with students.
   *
   * @return	void 	Will emit a wp_die error message if competition is unable to be created
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function can_scheduler_be_created($student_master_form_id,
                                                   $num_time_blocks_sat,
                                                   $num_time_blocks_sun,
                                                   $sat_start_times,
                                                   $sun_start_times,
                                                   $time_block_duration,
                                                   $num_concurrent_sections_sat,
                                                   $num_concurrent_sections_sun,
                                                   $num_master_sections_sat,
                                                   $num_master_sections_sun,
                                                   $master_class_instructor_duration) {
    // check to see if the festival chairman entered the same amount of timeblock 
    // starting times as the number of timeblocks
    if ($num_time_blocks_sat != count($sat_start_times)) {
      wp_die("<h1>ERROR: The number of timeblock start times for Saturday must match the value
              entered for 'Number of Timeblocks on Saturday'. You requested " . $num_time_blocks_sat .
              " timeblocks for Saturday but specified " . count($sat_start_times) . " start time(s). </h1>"); 
    }
    else if ($num_time_blocks_sun != count($sun_start_times)) {
      wp_die("<h1>ERROR: The number of timeblock start times for Sunday must match the value
              entered for 'Number of Timeblocks on Sunday'. You requested " . $num_time_blocks_sun .
              " timeblocks for Sunday but specified " . count($sun_start_times) . " start time(s). </h1>"); 
    }

    // determine the total amount of play time for all students in the current competition
    $student_master_field_mapping = ARIA_API::aria_master_student_field_id_array();
    $total_play_time_students = 0;
    $total_play_time_masterclass_students = 0;
    for ($i = LOW_LEVEL; $i <= HIGH_LEVEL; $i++) {
      $all_students_per_level = self::get_all_students_per_level($student_master_form_id, $i);
      foreach ($all_students_per_level as $student) {
        $total_play_time_students += $student[strval($student_master_field_mapping['timing_of_pieces'])];

        // check if student is competing in masterclass division
        $type = $student[strval($student_master_field_mapping['competition_format'])];
        if ($type == "Master Class") {
          $total_play_time_masterclass_students +=
            ($student[$student_master_field_mapping['timing_of_pieces']] + $master_class_instructor_duration);
        }
      }
    }

    // calculate total time for competition based on festival chairman input
    $music_time_limit = ceil($time_block_duration * PLAY_TIME_FACTOR); // judging requires 20% of section time
    $total_time_saturday = $num_time_blocks_sat * $num_concurrent_sections_sat * $music_time_limit;
    $total_time_sunday = $num_time_blocks_sun * $num_concurrent_sections_sun * $music_time_limit;

    // check if the student play time is greater than the time available based
    // on the festival chairman's input
    if ($total_play_time_students > ($total_time_saturday + $total_time_sunday)) {
      wp_die('<h1>ERROR: The input parameters entered on the previous page are unable
              to support all students that have registered for this competition.
              Please use the back button to return to the previous page and readjust
              your input parameters for scheduling.</h1>');
    }

    // check if the total playtime for masterclass students is greater than the
    // time available based on the festival chairman's input
    $total_time_masterclass_saturday = $num_master_sections_sat * $music_time_limit;
    $total_time_masterclass_sunday = $num_master_sections_sun * $music_time_limit;
    if ($total_play_time_masterclass_students > ($total_time_masterclass_saturday + $total_time_masterclass_sunday)) {
      wp_die('<h1>ERROR: The input parameters entered on the previous page
              regarding the masterclass sections are unable to support all students
              that have registered in the masterclass division for this competition.
              Please use the back button to return to the previous page and readjust
              your input parameters for the masterclass section (try adding more
              masterclass sections or increasing the timeblock length).</h1>');
    }
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
    $sorting = array();
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
        $day_preference = $student[strval($student_master_field_mapping['available_festival_days'])];
        $total_play_time = $student[strval($student_master_field_mapping['timing_of_pieces'])];

        if ($day_preference == "Saturday") {
          $playing_times[SAT] += intval($total_play_time);
          //echo 'sat: ' . $student[strval($student_master_field_mapping['student_first_name'])] . ' ' . $student[strval($student_master_field_mapping['student_last_name'])] . ' with play time: ' . $total_play_time . '<br>';
        }
        else if ($day_preference == "Sunday") {
          $playing_times[SUN] += intval($total_play_time);
          //echo 'sun: ' . $student[strval($student_master_field_mapping['student_first_name'])] . ' ' . $student[strval($student_master_field_mapping['student_last_name'])] . ' with play time: ' . $total_play_time . '<br>';
        }
        else {
          $playing_times[EITHER] += intval($total_play_time);
          //echo 'either: ' . $student[strval($student_master_field_mapping['student_first_name'])] . ' ' . $student[strval($student_master_field_mapping['student_last_name'])] . ' with play time: ' . $total_play_time . '<br>';
        }
      }
    }

    //wp_die('end of function');
    //echo "<h1>Displaying playing times</h1>";
    //wp_die(print_r($playing_times));
    return $playing_times;
  }

  /**
   * This function will send an email to each of the teachers in the competition.
   *
   * Once registration is complete, the teachers in the competition need to be
   * emailed information regarding when each of their students is competing and
   * about their volunteer duties. This function is responsible for generating
   * and sending that email.
   *
   * @param		int	$teacher_master_form_id	The teacher master form of the given competition.
   * @param 	Scheduler	$scheduler	The scheduler object for a given competition.
   * @param 	String 	$comp_name 	The name of the competition.  
   *
   * @return	void
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function send_teachers_competition_info($teacher_master_form_id, $scheduler,
  	                                                    $comp_name) {
    // get all entries in the associated teacher master
    $search_criteria = array();
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $entries = GFAPI::get_entries($teacher_master_form_id, $search_criteria,
                                  $sorting, $paging, $total_count);

    // get the associated entry in the create competition form
    $create_comp_form_id = ARIA_API::aria_get_create_competition_form_id();
    $comp_field_id_array = ARIA_API::aria_competition_field_id_array(); 
    $comp_entries = GFAPI::get_entries($create_comp_form_id, $search_criteria,
                                       $sorting, $paging, $total_count);

    //cho '<h1>send teachers comp info</h1>';
    //wp_die(print_r($comp_entries));
    $first_location = null;
    $second_location = null;  
    foreach ($comp_entries as $entry) {
      if ($entry[strval($comp_field_id_array['competition_name'])] == $comp_name) {
        echo '<h1>About to get location data</h1>'; 
        wp_die();  
      }
    }

    // store all of the teacher emails in an associative array
    $field_mapping = ARIA_API::aria_master_teacher_field_id_array();
    $teacher_emails_to_students = array(); 
    foreach ($entries as $teacher) {
      $teacher_email = $teacher[strval($field_mapping['email'])];
      if(!array_key_exists($teacher_email, $teacher_emails_to_students)) {
        $teacher_emails_to_students[] = $teacher_email;
        $teacher_emails_to_students[$teacher_email] = array(); 
      }
    }
    
    // for each of the emails that were found, find all students that registered under that teacher
    foreach ($teacher_emails_to_students as $key => $value) {
      if (strpos($key, '@') !== false) {
        $email_message = null;
        $scheduler->group_all_students_by_teacher_email($key, $teacher_emails_to_students[$key]);
        foreach ($teacher_emails_to_students[$key] as $student) {
          $email_message .= $student->get_info_for_email();
        }

        // once the message has been generated, send the email to the teachers
        if (!is_null($email_message)) {
          $subject = "Student Assignments for " . $comp_name;
          if (!wp_mail($key, $subject, $email_message)) {
            wp_die("<h1>Emails to teachers about competition info failed to send.
          	  Please try again.</h1>"); 
          }
        }
      }
    }
  }

  /**
   * This function will write the contents of a given scheduler object to a file. 
   *
   * Once the schedule has been generated, ARIA will automatically write that schedule
   * to a file so that it can be referenced later (for sending teachers/parents emails
   * and for document generation). 
   *
   * NOTE: This code will place the generated file inside of 
   *
   * @param     String  $title  The title for a given competition. 
   * @param     Scheduler   $scheduler  The scheduler object for a given competition. 
   *
   * @return    void
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function save_scheduler_to_file($title, $scheduler) {
    $title = str_replace(' ', '_', $title); 
    $file_path = ARIA_FILE_UPLOAD_LOC . $title . ".txt";
    $scheduler_data = serialize($scheduler);
    $fp = fopen($file_path, 'w+');
    if ($fp) {
      fwrite($fp, $scheduler_data);
      fclose($fp);
    } 
  }

  /**
   * This function will find all of the teachers in a given competition that are
   * registered to be judges. 
   *
   * This function will iterate through all of the teachers in the teacher master
   * form of a given competition and check to see which teachers are registered 
   * to judge. The info (first and last names of judges) will be consolidated into
   * an array and returned to the caller. 
   *
   * @param   Integer   $teacher_master_form_id   The form id of the teacher master form.
   *
   * @return  An array of teacher names that are judges. 
   * 
   * @since 1.0.0
   * @author KREW
   */
  private static function determine_judges($teacher_master_form_id) {
    // get all entries in the associated teacher master
    $search_criteria = array();
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $entries = GFAPI::get_entries($teacher_master_form_id, $search_criteria,
                                  $sorting, $paging, $total_count);

    // check to see which of the teachers are scheduled to judge for the competition
    $field_mapping = ARIA_API::aria_master_teacher_field_id_array();
    $judges = array(); 
    foreach ($entries as $entry) {
      if (array_key_exists($field_mapping[strval('is_judging')], $entry)) {
        if ($entry[strval($field_mapping['is_judging'])] == 'Yes') {
          $first_name = $entry[strval($field_mapping['first_name'])];
          $last_name = $entry[strval($field_mapping['last_name'])];
          $name = $first_name . ' ' . $last_name;
          $judges[] = $name;
        }
      }
    }

    return $judges;  
  }

  /**
   * This function will find all of the teachers in a given competition that have
   * volunteered to be proctors. 
   *
   * This function will iterate through all of the teachers in the teacher master
   * form of a given competition and check to see which teachers have volunteered 
   * to be a proctor. The info (first and last names of judges) will be consolidated 
   * into an array and returned to the caller. 
   *
   * @param   Integer   $teacher_master_form_id   The form id of the teacher master form.
   *
   * @return  An array of teacher names that are proctors. 
   * 
   * @since 1.0.0
   * @author KREW
   */
  private static function determine_proctors($teacher_master_form_id) {
    // get all entries in the associated teacher master
    $search_criteria = array();
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $entries = GFAPI::get_entries($teacher_master_form_id, $search_criteria,
                                  $sorting, $paging, $total_count);

    // check to see which of the teachers are scheduled to judge for the competition
    $field_mapping = ARIA_API::aria_master_teacher_field_id_array();
    $proctors = array();
    $volunteer_index = 1; 
    foreach ($entries as $entry) {
      $volunteer_index = 1; 
      $search_key = $field_mapping[strval('volunteer_preference')] . '.' . strval($volunteer_index);
      while (array_key_exists($search_key, $entry)) {
        if ($entry[$search_key] == 'Proctor sessions') {
          $first_name = $entry[strval($field_mapping['first_name'])];
          $last_name = $entry[strval($field_mapping['last_name'])];
          $name = $first_name . ' ' . $last_name;
          $proctors[] = $name;
        }

        $volunteer_index++;
        $search_key = $field_mapping[strval('volunteer_preference')] . '.' . strval($volunteer_index);
      }
    }

    return $proctors;  
  }
}
