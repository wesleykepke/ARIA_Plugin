<?php

/**
 * The file that defines the functionality for creating the student and teacher
 * master forms. Master forms will serve as the sources of truth for each
 * competition.
 *
 * @link       http://wesleykepke.github.io/ARIA_Plugin/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

//require_once("class-aria-api.php");
require_once("class-gf-form.php");
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
   * a certain competition's students.
   *
   * This function is called in "class-aria-create-competition.php" and is
   * responsible for creating the student master form. This form is the absolute
   * source of truth for the students of any given competition. Entries in other
   * forms will update entries in this form.
	 *
	 * @param 	$competition_name 	String 	The competition name.
   * @param   $command_options_array  Array   The command performance options (from create competition).
   * @param   $has_master_class   String  Determines whether or not the student can register as master class.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_student_master_form($competition_name,
                                                         $command_options_array,
                                                         $has_master_class) {
    // create the form and obtain the field mapping for student master forms
    $form = new GF_Form($competition_name . " Student Master", "");
    $field_mapping = ARIA_API::aria_master_student_field_id_array();

    // parent name field
    $parent_name = new GF_Field_Name();
    $parent_name->label = "Parent Name";
    $parent_name->id = $field_mapping['parent_name'];
    $parent_name->isRequired = false;
    $parent_name = ARIA_Create_Competition::aria_add_default_name_inputs($parent_name);
    $form->fields[] = $parent_name;

    // parent email field
    $parent_email = new GF_Field_Email();
    $parent_email->label = "Parent Email";
    $parent_email->id = $field_mapping['parent_email'];
    $parent_email->isRequired = false;
    $form->fields[] = $parent_email;

    // student name field
    $student_name = new GF_Field_Name();
    $student_name->label = "Student Name";
    $student_name->id = $field_mapping['student_name'];
    $student_name->isRequired = false;
    $student_name = ARIA_Create_Competition::aria_add_default_name_inputs($student_name);
    $form->fields[] = $student_name;

    // student birthday field
    $student_birthday = new GF_Field_Date();
    $student_birthday->label = "Student Birthday";
    $student_birthday->id = $field_mapping['student_birthday'];
    $student_birthday->isRequired = false;
    $student_birthday->calendarIconType = 'calendar';
    $student_birthday->dateType = 'datedropdown';
    $form->fields[] = $student_birthday;

    // student level field
    $student_level = new GF_Field_Select();
    $student_level->label = "Student Level";
    $student_level->id = $field_mapping['student_level'];
    $student_level->isRequired = false;
    $student_level->choices = array(
      array('text' => '1', 'value' => '1', 'isSelected' => false),
      array('text' => '2', 'value' => '2', 'isSelected' => false),
      array('text' => '3', 'value' => '3', 'isSelected' => false),
      array('text' => '4', 'value' => '4', 'isSelected' => false),
      array('text' => '5', 'value' => '5', 'isSelected' => false),
      array('text' => '6', 'value' => '6', 'isSelected' => false),
      array('text' => '7', 'value' => '7', 'isSelected' => false),
      array('text' => '8', 'value' => '8', 'isSelected' => false),
      array('text' => '9', 'value' => '9', 'isSelected' => false),
      array('text' => '10', 'value' => '10', 'isSelected' => false),
      array('text' => '11', 'value' => '11', 'isSelected' => false)
    );
    $form->fields[] = $student_level;

    // teacher name field
    $teacher_name = new GF_Field_Text();
    $teacher_name->label = "Teacher Name";
    $teacher_name->id = $field_mapping['teacher_name'];
    $teacher_name->isRequired = false;
    $form->fields[] = $teacher_name;

    // field for student's available festival times
    $festival_availability = new GF_Field_Radio();
    $festival_availability->label = "Festival Availability";
    $festival_availability->id = $field_mapping['festival_availability'];
    $festival_availability->isRequired = false;
    $festival_availability->description = "There is no guarantee that scheduling
    requests will be honored.";
    $festival_availability->descriptionPlacement = 'above';
    $festival_availability->choices = array(
      array('text' => 'Saturday', 'value' => 'Saturday', 'isSelected' => false),
      array('text' => 'Sunday', 'value' => 'Sunday', 'isSelected' => false),
      array('text' => 'Either Saturday or Sunday', 'value' => 'Either Saturday or Sunday', 'isSelected' => false)
    );
    $form->fields[] = $festival_availability;

    // field for student's available command performance times
    $command_performance_availability = new GF_Field_Radio();
    $command_performance_availability->label = "Command Performance Availability (check all available times)";
    $command_performance_availability->id = $field_mapping['command_performance_availability'];
    $command_performance_availability->isRequired = false;
    $command_performance_availability->description = "Please select the Command
    Performance time that you prefer in the event that your child receives a
    Superior with Distinction or Superior rating.";
    $command_performance_availability->descriptionPlacement = 'above';
    $command_performance_availability->choices = array();
    $command_performance_availability->choices[] = array('text' => 'Any time',
                                                         'value' => 'Any time',
                                                         'isSelected' => false);

    // add the choices that were input during create competition
    if (is_array($command_options_array)) {
      foreach ($command_options_array as $command_time) {
        $command_performance_availability->choices[] = array('text' => $command_time,
                                                             'value' => $command_time,
                                                             'isSelected' => false);
      }
    }
    $form->fields[] = $command_performance_availability;

    // field for student's first song period
    $song_one_period = new GF_Field_Text();
    $song_one_period->label = "Song 1 Period";
    $song_one_period->id = $field_mapping['song_1_period'];
    $song_one_period->isRequired = false;
    $form->fields[] = $song_one_period;

    // field for student's first song composer
    $song_one_composer = new GF_Field_Text();
    $song_one_composer->label = "Song 1 Composer";
    $song_one_composer->id = $field_mapping['song_1_composer'];
    $song_one_composer->isRequired = false;
    $form->fields[] = $song_one_composer;

    // field for student's first song selection
    $song_one_selection = new GF_Field_Text();
    $song_one_selection->label = "Song 1 Selection";
    $song_one_selection->id = $field_mapping['song_1_selection'];
    $song_one_selection->isRequired = false;
    $form->fields[] = $song_one_selection;

    // field for student's second song period
    $song_two_period = new GF_Field_Text();
    $song_two_period->label = "Song 2 Period";
    $song_two_period->id = $field_mapping['song_2_period'];
    $song_two_period->isRequired = false;
    $form->fields[] = $song_two_period;

    // field for student's second song composer
    $song_two_composer = new GF_Field_Text();
    $song_two_composer->label = "Song 2 Composer";
    $song_two_composer->id = $field_mapping['song_2_composer'];
    $song_two_composer->isRequired = false;
    $form->fields[] = $song_two_composer;

    // field for student's second song selection
    $song_two_selection = new GF_Field_Text();
    $song_two_selection->label = "Song 2 Selection";
    $song_two_selection->id = $field_mapping['song_2_selection'];
    $song_two_selection->isRequired = false;
    $form->fields[] = $song_two_selection;

    // field for student's theory score
    $theory_score = new GF_Field_Number();
    $theory_score->label = "Theory Score (percentage)";
    $theory_score->id = $field_mapping['theory_score'];
    $theory_score->isRequired = false;
    $theory_score->numberFormat = "decimal_dot";
    $theory_score->rangeMin = 0;
    $theory_score->rangeMax = 100;
    $form->fields[] = $theory_score;

    // field for student's alternate theory
    $alternate_theory = new GF_Field_Checkbox();
    $alternate_theory->label = "Check if alternate theory exam was completed.";
    $alternate_theory->id = $field_mapping['alternate_theory'];
    $alternate_theory->isRequired = false;
    $alternate_theory->choices = array(
      array('text' => 'Alternate theory exam completed',
            'value' => 'Alternate theory exam completed',
            'isSelected' => false
      )
    );
    $alternate_theory->inputs = array();
    $alternate_theory = ARIA_Create_Competition::aria_add_checkbox_input($alternate_theory,
                                                                         'Alternate theory exam completed');
    $form->fields[] = $alternate_theory;

    // field for the type of competition format that the student registered as
    $competition_format = new GF_Field_Radio();
    $competition_format->label = "Format of Competition";
    $competition_format->id = $field_mapping['competition_format'];
    $competition_format->isRequired = false;
    $competition_format->choices = array(
      array('text' => 'Traditional', 'value' => 'Traditional', 'isSelected' => false),
      array('text' => 'Non-Competitive', 'value' => 'Non-Competitive', 'isSelected' => false)
    );
    if ($has_master_class == "Yes") {
        $competition_format->choices[] = array('text' => 'Master Class',
                                               'value' => 'Master Class',
                                               'isSelected' => false);
    }
    $form->fields[] = $competition_format;

    // field for timing of student's pieces
    $timing_of_pieces = new GF_Field_Number();
    $timing_of_pieces->label = "Timing of Pieces (minutes)";
    $timing_of_pieces->id = $field_mapping['timing_of_pieces'];
    $timing_of_pieces->isRequired = false;
    $timing_of_pieces->numberFormat = "decimal_dot";
    $form->fields[] = $timing_of_pieces;

    // field for student's hash
    $student_hash = new GF_Field_Text();
    $student_hash->label = "Student Hash";
    $student_hash->id = $field_mapping['student_hash'];
    $student_hash->isRequired = false;
    $form->fields[] = $student_hash;

    // create the form based on the previous field definitions
    $form_array = $form->createFormArray();
    $form_array['isStudentMasterForm'] = true;
    $result = GFAPI::add_form($form_array);
    if (is_wp_error($result)) {
      wp_die($result->get_error_message());
    }

    return $result;
  }

	/**
	 * This function will create the form that will be the source of truth for
	 * a certain competition's teachers.
	 *
	 * This function is called in "class-aria-create-competition.php" and is
	 * responsible for creating the teacher master form. This form is the absolute
	 * source of truth for the teachers of any given competition. Entries in other
	 * forms will update entries in this form.
	 *
	 * @param 	$competition_name 	String 	The competition name.
   * @param   $volunteer_options  Array   The available volunteer_options.
   * @param   $volunteer_time_options   Array   The available volunteer times.
	 *
	 * @since 1.0.0
	 * @author KREW
	 */
  public static function aria_create_teacher_master_form($competition_name,
                                                         $volunteer_options,
                                                         $volunteer_time_options) {
    // create the form and obtain it's field mapping
    $form = new GF_Form($competition_name . " Teacher Master", "");
    $field_mapping = ARIA_API::aria_master_teacher_field_id_array();

    // teacher name field
    $name = new GF_Field_Name();
    $name->label = "Name";
    $name->id = $field_mapping['name'];
    $name->isRequired = false;
    $name = ARIA_Create_Competition::aria_add_default_name_inputs($name);
    $form->fields[] = $name;

    // teacher email field
    $teacher_email = new GF_Field_Email();
    $teacher_email->label = "Email";
    $teacher_email->id = $field_mapping['email'];
    $teacher_email->isRequired = false;
    $form->fields[] = $teacher_email;

    // teacher phone field
    $teacher_phone = new GF_Field_Phone();
    $teacher_phone->label = "Phone";
    $teacher_phone->id = $field_mapping['phone'];
    $teacher_phone->isRequired = false;
    $form->fields[] = $teacher_phone;

    // teacher's hash
    $hash = new GF_Field_Text();
    $hash->label = 'Teacher Hash';
    $hash->id = $field_mapping['hash'];
    $hash->isRequired = false;
    $form->fields[] = $hash;

    // list of students field
    $students = new GF_Field_List();
    $students->label = "Students";
    $students->id = $field_mapping['students'];
    $students->isRequired = false;
    $form->fields[] = $students;

    // list of students hash field ?
    $student_hashes = new GF_Field_Text();
    $student_hashes->label = 'Student Hashes';
    $student_hashes->id = $field_mapping['student_hashes'];
    $student_hashes->isRequired = false;
    $form->fields[] = $student_hashes;

    // field for teacher to identify as judging
    $is_judging = new GF_Field_Radio();
    $is_judging->label = "Are you scheduled to judge for the festival?";
    $is_judging->id = $field_mapping['is_judging'];
    $is_judging->isRequired = false;
    $is_judging->choices = array(
    	array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
    	array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $form->fields[] = $is_judging;

    // teacher volunteer preference field
    $volunteer_preference = new GF_Field_Checkbox();
    $volunteer_preference->label = "Volunteer Preference";
    $volunteer_preference->id = $field_mapping['volunteer_preference'];
    $volunteer_preference->isRequired = false;

    // add the volunteer preferences that were input during competition creation
    $volunteer_preference->choices = array();
    if (is_array($volunteer_options)) {
      foreach ($volunteer_options as $volunteer_option) {
        $volunteer_preference->choices[] = array('text' => $volunteer_option,
                                                 'value' => $volunteer_option,
                                                 'isSelected' => false
        );
      }
    }

    // add the volunteer options as inputs to the checkbox
    $volunteer_preference = ARIA_Create_Competition::aria_add_checkbox_input($volunteer_preference,
                                                                             $volunteer_options);

    // finish adding the volunteer options field into the form
    $conditional_volunteer_preference_rules = array();
    $conditional_volunteer_preference_rules[] = array(
      'fieldId' => $field_mapping['volunteer_preference'],
      'operator' => 'is',
      'value' => 'No'
    );
    $volunteer_preference->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $conditional_volunteer_preference_rules
    );
    $form->fields[] = $volunteer_preference;

    /*
    $volunteer_preference->choices = array(
      array('text' => 'Proctor sessions', 'value' => 'Proctor sessions', 'isSelected' => false),
      array('text' => 'Monitor door during sessions', 'value' => 'Monitor door during sessions', 'isSelected' => false),
      array('text' => 'Greet students and parents', 'value' => 'Greet students and parents', 'isSelected' => false),
      array('text' => 'Prepare excellent ribbons', 'value' => 'Prepare excellent ribbons', 'isSelected' => false),
      array('text' => 'Put seals on certificates', 'value' => 'Put seals on certificates', 'isSelected' => false),
      array('text' => 'Early set up', 'value' => 'Early set up', 'isSelected' => false),
      array('text' => 'Clean up', 'value' => 'Clean up', 'isSelected' => false),
      array('text' => 'Help with food for judges and volunteers', 'value' => 'Help with food for judges and volunteers', 'isSelected' => false)
    );
    $volunteer_preference_field->inputs = array();
    $volunteer_preference_field
      = ARIA_Create_Competition::aria_add_checkbox_input($volunteer_preference_field,
        array(
          'Proctor sessions',
          'Monitor door during sessions',
          'Greet students and parents',
          'Prepare excellent ribbons',
          'Put seals on certificates',
          'Early set up',
          'Clean up',
          'Help with food for judges and volunteers',
        ));
    */

    // volunteer time field
    $volunteer_time = new GF_Field_Checkbox();
    $volunteer_time->label = "Times Available for Volunteering";
    $volunteer_time->id = $field_mapping['volunteer_time'];
    $volunteer_time->isRequired = false;
    $volunteer_time->description = "Please check at least two times you are
    available to volunteer during Festival weekend.";

    // add the volunteer times that were input during competition creation
    $volunteer_time->choices = array();
    if (is_array($volunteer_time_options)) {
      foreach ($volunteer_time_options as $single_volunteer_time_option) {
        $volunteer_time->choices[] = array('text' => $single_volunteer_time_option,
                                           'value' => $single_volunteer_time_option,
                                           'isSelected' => false);
      }
    }

    // add the volunteer options as inputs to the checkbox
    $volunteer_time->inputs = array();
    $volunteer_time = ARIA_Create_Competition::aria_add_checkbox_input($volunteer_time,
                                                                       $volunteer_time_options);

    // finish adding the volunteer options field into the form
    $conditional_volunteer_time_rules = array();
    $conditional_volunteer_time_rules[] = array(
      'fieldId' => $field_mapping['volunteer_time'],
      'operator' => 'is',
      'value' => 'No'
    );
    $volunteer_time->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $conditional_volunteer_time_rules
    );
    $form->fields[] = $volunteer_time;

    // option for being assigned to section with students field
    $schedule_with_students = new GF_Field_Radio();
    $schedule_with_students->label = "Volunteer with Students";
    $schedule_with_students->description = "Do you wish to be scheduled as a
    proctor or door monitor for a session in which one of your own students is
    playing?";
    $schedule_with_students->descriptionPlacement = 'above';
    $schedule_with_students->id = $field_mapping['schedule_with_students'];
    $schedule_with_students->isRequired = false;
    $schedule_with_students->choices = array(
      array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
      array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $conditional_schedule_with_students_rules = array();
    $conditional_schedule_with_students_rules[] = array(
      'fieldId' => $field_mapping['schedule_with_students'],
      'operator' => 'is',
      'value' => 'No'
    );
    $schedule_with_students->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $conditional_schedule_with_students_rules
    );
    $form->fields[] = $schedule_with_students;

    // create form based on the previous field definitions
    $form_array = $form->createFormArray();
    $form_array['isTeacherMasterForm'] = true;
    $result = GFAPI::add_form($form_array);
    if (is_wp_error($result)) {
      wp_die($result->get_error_message());
    }

    return $result;
  }
}
