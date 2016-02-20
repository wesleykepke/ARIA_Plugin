<?php

/**
 * The file that defines create master forms functionality. Master forms will
 * serve as the systems source of truth for each competition.
 *
 * @link       http://wesleykepke.github.io/ARIA/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

// Require the ARIA API
//require_once("class-aria-api.php");

/**
 * The create master forms class.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
 */
class ARIA_Create_Master_Forms {

  /**
   * This function will create the form that will be the source of truth for
   * a certain competitions students.
   *
   * This function is called in "class-aria-create-competition.php" and is
   * responsible for creating the student master form. This form is the absolute
   * source of truth for the students of any given competition. Entries in other
   * forms will update entries in this form.
	 *
	 * @param 	$competition_name 	String 	The competition name
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_student_master_form($competition_name) {
    $student_master_form
        = new GF_Form($competition_name . " Student Master", "");
    $field_id_array = self::aria_master_student_field_id_array();

    // parent name
    $parent_name_field = new GF_Field_Name();
    $parent_name_field->label = "Parent Name";
    $parent_name_field->id = $field_id_array['parent_name'];
    $parent_name_field->isRequired = false;
    $parent_name_field = ARIA_Create_Competition::aria_add_default_name_inputs($parent_name_field);
    $student_master_form->fields[] = $parent_name_field;

    // parent email
    $parent_email_field = new GF_Field_Email();
    $parent_email_field->label = "Parent's Email";
    $parent_email_field->id = $field_id_array['parent_email'];
    $parent_email_field->isRequired = false;
    $student_master_form->fields[] = $parent_email_field;

    // student name
    $student_name_field = new GF_Field_Name();
    $student_name_field->label = "Student Name";
    $student_name_field->id = $field_id_array['student_name'];
    $student_name_field->isRequired = false;
    $student_name_field = ARIA_Create_Competition::aria_add_default_name_inputs($student_name_field);
    $student_master_form->fields[] = $student_name_field;

    // student birthday
    $student_birthday_date_field = new GF_Field_Date();
    $student_birthday_date_field->label = "Student Birthday";
    $student_birthday_date_field->id = $field_id_array['student_birthday'];
    $student_birthday_date_field->isRequired = false;
    $student_birthday_date_field->calendarIconType = 'calendar';
    $student_birthday_date_field->dateType = 'datepicker';
    $student_master_form->fields[] = $student_birthday_date_field;

    // student's piano teacher
    $piano_teachers_field = new GF_Field_Select();
    $piano_teachers_field->label = "Piano Teacher's Name";
    $piano_teachers_field->id = $field_id_array['teacher_name'];
    $piano_teachers_field->isRequired = false;
    $piano_teachers_field->description = "";
    $student_master_form->fields[] = $piano_teachers_field;

    // student's piano teacher does not exist
    $teacher_missing_field = new GF_Field_Text();
    $teacher_missing_field->label = "If your teacher's name is not listed, ".
    "enter name below.";
    $teacher_missing_field->id = $field_id_array['not_listed_teacher_name'];
    $teacher_missing_field->isRequired = false;
    $student_master_form->fields[] = $teacher_missing_field;

    // student's available times to compete
    $available_times = new GF_Field_Checkbox();
    $available_times->label = "Available Festival Days (check all available times)";
    $available_times->id = $field_id_array['available_festival_days'];
    $available_times->isRequired = false;
    $available_times->description = "There is no guarantee that scheduling ".
    "requests will be honored.";
    $available_times->choices = array(
      array('text' => 'Saturday', 'value' => 'Saturday', 'isSelected' => false),
      array('text' => 'Sunday', 'value' => 'Sunday', 'isSelected' => false)
    );
    $student_master_form->fields[] = $available_times;

    // student's available times to compete for command performance
    $command_times = new GF_Field_Checkbox();
    $command_times->label = "Preferred Command Performance Time (check all available times)";
    $command_times->id = $field_id_array['preferred_command_performance'];
    $command_times->isRequired = false;
    $command_times->description = "Please check the Command Performance time ".
    "that you prefer in the event that your child receives a superior rating.";
    $command_times->choices = array(
      array('text' => 'Thursday 5:30', 'value' => 'Saturday', 'isSelected' => false),
      array('text' => 'Thursday 7:30', 'value' => 'Sunday', 'isSelected' => false)
    );
    $student_master_form->fields[] = $available_times;

    // student's first song period
    $song_one_period_field = new GF_Field_Select();
    $song_one_period_field->label = "Song 1 Period";
    $song_one_period_field->id = $field_id_array['song_1_period'];
    $song_one_period_field->isRequired = false;
    $student_master_form->fields[] = $song_one_period_field;

    // student's first song composer
    $song_one_composer_field = new GF_Field_Select();
    $song_one_composer_field->label = "Song 1 Composer";
    $song_one_composer_field->id = $field_id_array['song_1_composer'];
    $song_one_composer_field->isRequired = false;
    $student_master_form->fields[] = $song_one_composer_field;

    // student's first song selection
    $song_one_selection_field = new GF_Field_Select();
    $song_one_selection_field->label = "Song 1 Selection";
    $song_one_selection_field->id = $field_id_array['song_1_selection'];
    $song_one_selection_field->isRequired = false;
    $student_master_form->fields[] = $song_one_selection_field;

    // student's second song period
    $song_two_period_field = new GF_Field_Select();
    $song_two_period_field->label = "Song 2 Period";
    $song_two_period_field->id = $field_id_array['song_2_period'];
    $song_two_period_field->isRequired = false;
    $student_master_form->fields[] = $song_two_period_field;

    // student's second song composer
    $song_two_composer_field = new GF_Field_Select();
    $song_two_composer_field->label = "Song 2 Composer";
    $song_two_composer_field->id = $field_id_array['song_2_composer'];
    $song_two_composer_field->isRequired = false;
    $student_master_form->fields[] = $song_two_composer_field;

    // student's second song selection
    $song_two_selection_field = new GF_Field_Select();
    $song_two_selection_field->label = "Song 2 Selection";
    $song_two_selection_field->id = $field_id_array['song_2_selection'];
    $song_two_selection_field->isRequired = false;
    $student_master_form->fields[] = $song_two_selection_field;

    // student's theory score
    $student_theory_score = new GF_Field_Number();
    $student_theory_score->label = "Theory Score (percentage)";
    $student_theory_score->id = $field_id_array['theory_score'];
    $student_theory_score->isRequired = false;
    $student_theory_score->numberFormat = "decimal_dot";
    $student_theory_score->rangeMin = 0;
    $student_theory_score->rangeMax = 100;
    $student_master_form->fields[] = $student_theory_score;

    // student's alternate theory
    $alternate_theory_field = new GF_Field_Checkbox();
    $alternate_theory_field->label = "Check if alternate theory exam was completed.";
    $alternate_theory_field->id = $field_id_array['alternate_theory'];
    $alternate_theory_field->isRequired = false;
    $alternate_theory_field->choices = array(
      array('text' => 'Alternate theory exam completed',
      'value' => 'Alternate theory exam completed',
      'isSelected' => false)
    );
    $student_master_form->fields[] = $alternate_theory_field;

    // competition format
    $competition_format_field = new GF_Field_Radio();
    $competition_format_field->label = "Format of Competition";
    $competition_format_field->id = $field_id_array['competition_format'];
    $competition_format_field->isRequired = false;
    $competition_format_field->choices = array(
      array('text' => 'Traditional', 'value' => 'Traditional', 'isSelected' => false),
      array('text' => 'Competitive', 'value' => 'Competitive', 'isSelected' => false),
      array('text' => 'Master Class (if upper level)', 'value' => 'Master Class', 'isSelected' => false)
    );
    $student_master_form->fields[] = $competition_format_field;

    // timing field
    $timing_of_pieces_field = new GF_Field_Number();
    $timing_of_pieces_field->label = "Timing of pieces (minutes)";
    $timing_of_pieces_field->id = $field_id_array['timing_of_pieces'];
    $timing_of_pieces_field->isRequired = false;
    $timing_of_pieces_field->numberFormat = "decimal_dot";
    $student_master_form->fields[] = $timing_of_pieces_field;

    // student's hash
    $student_hash_field = new GF_Field_Text();
    $student_hash_field->id = $field_id_array['hash'];
    $student_hash_field->isRequired = false;
    $student_master_form->fields[] = $student_hash_field;

    return GFAPI::add_form($student_master_form->createFormArray());
  }

	/**
	 * This function defines an associative array used in the student form.
	 *
	 * This function returns an array that maps all of the names of the fields in the
	 * student form to a unique integer so that they can be referenced. Moreover, this
	 * array helps prevent the case where the names of these fields are modified from
	 * the dashboard.
	 *
	 * @since 1.0.0
	 * @author KREW
	 */
	public static function aria_master_student_field_id_array() {
	  // CAUTION, This array is used as a source of truth. Changing these values may
	  // result in catastrophic failure. If you do not want to feel the bern,
	  // consult an aria developer before making changes to this portion of code.
	  return array(
	    'parent_name' => 1,
			'parent_first_name' => 1.1,
			'parent_last_name' => 1.2,
	    'parent_email' => 2,
	    'student_name' => 3,
			'student_first_name' => 3.1,
			'student_last_name' => 3.2,
	    'student_birthday' => 4,
	    'teacher_name' => 5,
	    'not_listed_teacher_name' => 6,
	    'available_festival_days' => 7,
	    'preferred_command_performance' => 8,
	    'song_1_period' => 9,
	    'song_1_composer' => 10,
	    'song_1_selection' => 11,
	    'song_2_period' => 12,
	    'song_2_composer' => 13,
	    'song_2_selection' => 14,
	    'theory_score' => 15,
	    'alternate_theory' => 16,
	    'competition_format' => 17,
	    'timing_of_pieces' => 18,
      'hash' => 19
	  );
	}

	/**
	 * This function will create the form that will be the source of truth for
	 * a certain competitions teachers.
	 *
	 * This function is called in "class-aria-create-competition.php" and is
	 * responsible for creating the teacher master form. This form is the absolute
	 * source of truth for the teachers of any given competition. Entries in other
	 * forms will update entries in this form.
	 *
	 * @param 	$competition_name 	String 	The competition name
	 *
	 * @since 1.0.0
	 * @author KREW
	 */
  public static function aria_create_teacher_master_form($competition_name) {
    $teacher_master_form
        = new GF_Form($competition_name . " Teacher Master", "");

    $field_id_array = self::aria_master_teacher_field_id_array();

    // Students
    $parent_name_field = new GF_Field_List();
    $parent_name_field->label = "Students";
    $parent_name_field->id = $field_id_array['students'];
    $teacher_master_form->fields[] = $parent_name_field;

    // teacher name
    $teacher_name_field = new GF_Field_Name();
    $teacher_name_field->label = "Name";
    $teacher_name_field->id = $field_id_array['name'];
    $teacher_name_field->isRequired = false;
    $teacher_name_field = ARIA_Create_Competition::aria_add_default_name_inputs($teacher_name_field);
    $teacher_master_form->fields[] = $teacher_name_field;

    // teacher email
    $teacher_email_field = new GF_Field_Email();
    $teacher_email_field->label = "Email";
    $teacher_email_field->id = $field_id_array['email'];
    $teacher_email_field->isRequired = false;
    $teacher_master_form->fields[] = $teacher_email_field;

    // teacher phone
    $teacher_phone_field = new GF_Field_Phone();
    $teacher_phone_field->label = "Phone";
    $teacher_phone_field->id = $field_id_array['phone'];
    $teacher_phone_field->isRequired = false;
    $teacher_master_form->fields[] = $teacher_phone_field;

    // !!!new field
    // teacher is judging
    $teacher_judging_field = new GF_Field_Radio();
    $teacher_judging_field->label = "Are you scheduled to judge for the festival?";
    $teacher_judging_field->id = $field_id_array['is_judging'];
    $teacher_judging_field->isRequired = false;
    $teacher_judging_field->choices = array(
    	array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
    	array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $conditionalRules = array();
    $conditionalRules[] = array(
    	'fieldId' => $field_id_array['is_judging'],
    	'operator' => 'is',
    	'value' => 'No'
    );
    $teacher_master_form->fields[] = $teacher_judging_field;

    // teacher volunteer preference
    $volunteer_preference_field = new GF_Field_Checkbox();
    $volunteer_preference_field->label = "Volunteer Preference";
    $volunteer_preference_field->id = $field_id_array['volunteer_preference'];
    $volunteer_preference_field->isRequired = false;
    /*!!! $volunteer_preference_field->choices = array(
      array('text' => 'Section Proctor', 'value' => 'Section Proctor', 'isSelected' => false),
      array('text' => 'Posting Results', 'value' => 'Posting Results', 'isSelected' => false),
      array('text' => 'Information Table', 'value' => 'Information Table', 'isSelected' => false),
      array('text' => 'Greeting and Assisting with Locating Rooms', 'value' => 'Greeting', 'isSelected' => false),
      array('text' => 'Hospitality (managing food in judges rooms)', 'value' => 'Hospitality', 'isSelected' => false)
    );
    $volunteer_preference_field->description = "Please check 1 time slot if you"
    ." have 1-3 students competing, 2 time slots if you have 4-6 students"
    ." competing, and 3 time slots if you have more than 6 students competing.";
    */
    $volunteer_preference_field->choices = array(
      array('text' => 'Proctor sessions', 'value' => 'Proctor sessions', 'isSelected' => false),
      array('text' => 'Monitor door during sessions', 'value' => 'Monitor door during sessions', 'isSelected' => false),
      array('text' => 'Greet students and parents', 'value' => 'Greet students and parents', 'isSelected' => false),
      array('text' => 'Prepare excellent ribbons', 'value' => 'Prepare excellent ribbons', 'isSelected' => false),
      array('text' => 'Put seals on certificates', 'value' => 'Put seals on certificates', 'isSelected' => false),
      array('text' => 'Early set up', 'value' => 'Early set up', 'isSelected' => false),
      array('text' => 'Clean up', 'value' => 'Clean up', 'isSelected' => false),
      array('text' => 'Help with food for judges and volunteers', 'value' => 'Help with food for judges and volunteers', 'isSelected' => false)
    );
    $volunteer_preference_field->description = "Please check at least two volunteer job"
    ."preferences for this year's Festival. You will be notified by email of your"
    ."volunteer assignments as Festival approaches.";
   $volunteer_preference_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $conditionalRules
    );
    $teacher_master_form->fields[] = $volunteer_preference_field;

    // volunteer time
    $volunteer_time_field = new GF_Field_Checkbox();
    $volunteer_time_field->label = "Times Available for Volunteering";
    $volunteer_time_field->id = $field_id_array['volunteer_time'];
    $volunteer_time_field->isRequired = false;
    $volunteer_time_field->description = "Please check at least two times you are"
    ."available to volunteer during Festival weekend.";
    $volunteer_time_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $conditionalRules
    );
    $teacher_master_form->fields[] = $volunteer_time_field;

    // student's hash
    $teacher_hash_field = new GF_Field_Text();
    $teacher_hash_field->id = $field_id_array['hash'];
    $teacher_hash_field->isRequired = false;
    $teacher_master_form->fields[] = $teacher_hash_field;

    return GFAPI::add_form($teacher_master_form->createFormArray());
  }

	/**
	 * This function defines an associative array used in the student form.
	 *
	 * This function returns an array that maps all of the names of the fields in the
	 * student form to a unique integer so that they can be referenced. Moreover, this
	 * array helps prevent the case where the names of these fields are modified from
	 * the dashboard.
	 *
	 * @since 1.0.0
	 * @author KREW
	 *
	 */
  public static function aria_master_teacher_field_id_array() {
    // CAUTION, This array is used as a source of truth. Changing these values may
    // result in catastrophic failure. If you do not want to feel the bern,
    // consult an aria developer before making changes to this portion of code.


    /*

    this needs to be checked, i don't think it's right

		*/

    return array(
      'name' => 1,
      'email' => 2,
      'phone' => 3,
      'volunteer_preference' => 4,
      'volunteer_time' => 5,
      'students' => 6,
      'is_judging' => 7,
      'hash' => 8
    );
	}
}
