<?php

/**
 * The file that defines create competition functionality.
 *
 * A class definition that includes attributes and functions that allow the
 * festival chairman to create new music competitions for NNMTA.
 *
 * @link       http://wesleykepke.github.io/ARIA/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

require_once("class-aria-api.php");
require_once("class-aria-create-master-forms.php");

/**
 * The create competition class.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
 */
class ARIA_Create_Competition {

  /**
   * This function will create the form that can create new competitions.
   *
   * This function is called in "class-aria-activator.php" and is responsible for
   * creating the form that allows the festival chairman to create new music
   * competitions (if this form does not already exist). If no such form exists,
   * this function will create a new form designed specifically for creating new
   * music competitions.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_competition_activation() {
    // if the form for creating music competitions doesn't exist, create a new form
    $form_id = ARIA_API::aria_get_create_competition_form_id();
    if ($form_id === -1) {
      $form_id = self::aria_create_competition_form();
    }

    // add functionality to create new student and teacher forms once a new
    // competition is created
    $hook = ('gform_confirmation_' . strval($form_id));
    $function = 'aria_create_teacher_and_student_forms';
    if (!has_action($hook)) { // 1 is the priority on this hook
      $loader = new ARIA_Loader();
      $loader->add_action($hook,
	      'ARIA_Create_Competition', $function, 10, 4);
      $loader->run();

      /*
      add_action($hook,
	      array(&$this, 'aria_create_teacher_and_student_forms'), 10, 4); */
      //wp_die('added function named: ' . $function . " to " . $hook);
    }
    else {
      wp_die('hook has already been added');
    }
  }

  /**
   * This function will create new registration forms for students and parents.
   *
   * This function is responsible for creating new registration forms for both
   * students and parents. This function will only create new registration forms
   * for students and parents if it is used ONLY in conjunction with the form
   * used to create new music competitions.
   *
   * @param Entry Object  $entry  The entry that was just submitted
   * @param Form Object   $form   The form used to submit entries
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_teacher_and_student_forms($confirmation, $form, $entry, $ajax) {
    wp_die("I'm getting called.");

    // make sure the create competition form is calling this function
    $competition_creation_form_id = ARIA_API::aria_get_create_competition_form_id();
    if ($form['id'] === $competition_creation_form_id) {
			/*
			Calls wp_die and returns a value of 86?
      self::aria_update_page_ids();

			*/

			$field_mapping = self::aria_get_competition_entry_meta();
			$competition_name = $entry[$field_mapping['Name of Competition']];

			// create the student and teacher forms
      $student_form_id = self::aria_create_student_form($entry);
      $teacher_form_id = self::aria_create_teacher_form($entry, unserialize($entry[(string) $field_mapping['Volunteer Times']]));
      $student_form_url = self::aria_publish_form("{$competition_name} Student Registration", $student_form_id);
      $teacher_form_url = self::aria_publish_form("{$competition_name} Teacher Registration", $teacher_form_id);

			// create the sutdent and teacher (master) forms
			ARIA_Create_Master_Forms::aria_create_student_master_form($competition_name);
			ARIA_Create_Master_Forms::aria_create_teacher_master_form($competition_name);

      $confirmation = 'Congratulations! A new music competition has been ';
      $confirmation .= 'created. The following forms are now available for ';
      $confirmation .= ' students and teachers to use for registration: </br>';
      $confirmation .= "<a href={$student_form_url}>{$competition_name} Student Registration</a>";
      $confirmation .= " was published. </br>";
      $confirmation .= "<a href={$teacher_form_url}> {$competition_name} Teacher Registration </a>";
      $confirmation .= " was published.";

      return $confirmation;
    }
    else {
      wp_die("ERROR: No form currently exists that allows the festival chairman
      to create a new music competition \n FormID: {$form[id]} \n func_call {$competition_creation_form_id}");
    }
  }

	/**
   * This function will return an associative array with entry meta data for the
   * competition form.
   *
   * Every time an entry is submitted using the form for creating a competition, the
   * submission is an Entry object, which is an associative array that has a plethora
   * of information. Also included inside the Entry object is the infomation that was
   * input by the user. This function simply returns an associative array that can be
   * used by other functions to offset into the Entry object's user data, because
   * otherwise, the offset all involves magic integers that are otherwise not very
   * descriptive.
   *
   * @since 1.0.5
   * @author KREW
   */
  public static function aria_get_competition_entry_meta() {
    return array(
      'Name of Competition' => 1,
      'Date of Competition' => 2,
      'Location of Competition' => 3,
      'Street Address' => 3.1,
      'Address Line 2' => 3.2,
      'City' => 3.3,
      'State / Province / Region' => 3.4,
      'Zip / Postal Code' => 3.5,
      'Country' => 3.6,
      'Student Registration Start Date' => 4,
      'Student Registration End Date' => 5,
      'Teacher Registration Start Date' => 6,
      'Teacher Registration Start Date' => 7,
      'Volunteer Times' => 8
    );
  }

  /**
   * This function will create a new form for creating music competitions.
   *
   * This function is responsible for creating and adding all of the associated
   * fields that are necessary for the festival chairman to create new music
   * competitions.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_create_competition_form() {
    $competition_creation_form = new GF_Form("ARIA: Create a Competition", "");

    // name
    $competition_name_field = new GF_Field_Text();
    $competition_name_field->label = "Name of Competition";
    $competition_name_field->id = 1;
    $competition_name_field->isRequired = false;

    // date of the competition
    $competition_date_field = new GF_Field_Date();
    $competition_date_field->label = "Date of Competition";
    $competition_date_field->id = 2;
    $competition_date_field->isRequired = false;
    $competition_date_field->calendarIconType = 'calendar';
    $competition_date_field->dateType = 'datepicker';

    // location
    $competition_location_field = new GF_Field_Address();
    $competition_location_field->label = "Location of Competition";
    $competition_location_field->id = 3;
    $competition_location_field->isRequired = false;
    $competition_location_field = self::aria_add_default_address_inputs($competition_location_field);

    // student registration start date
    $student_registration_start_date_field = new GF_Field_Date();
    $student_registration_start_date_field->label = "Student Registration Start Date";
    $student_registration_start_date_field->id = 4;
    $student_registration_start_date_field->isRequired = false;
    $student_registration_start_date_field->calendarIconType = 'calendar';
    $student_registration_start_date_field->dateType = 'datepicker';

    // student registration deadline
    $student_registration_end_date_field = new GF_Field_Date();
    $student_registration_end_date_field->label = "Student Registration End Date";
    $student_registration_end_date_field->id = 5;
    $student_registration_end_date_field->isRequired = false;
    $student_registration_end_date_field->calendarIconType = 'calendar';
    $student_registration_end_date_field->dateType = 'datepicker';

    // teacher registration start date
    $teacher_registration_start_date_field = new GF_Field_Date();
    $teacher_registration_start_date_field->label = "Teacher Registration Start Date";
    $teacher_registration_start_date_field->id = 6;
    $teacher_registration_start_date_field->isRequired = false;
    $teacher_registration_start_date_field->calendarIconType = 'calendar';
    $teacher_registration_start_date_field->dateType = 'datepicker';

    // teacher registration deadline
    $teacher_registration_end_date_field = new GF_Field_Date();
    $teacher_registration_end_date_field->label = "Teacher Registration Start Date";
    $teacher_registration_end_date_field->id = 7;
    $teacher_registration_end_date_field->isRequired = false;
    $teacher_registration_end_date_field->calendarIconType = 'calendar';
    $teacher_registration_end_date_field->dateType = 'datepicker';

    // teacher volunteer options
    $teacher_volunteer_times_field = new GF_Field_List();
    $teacher_volunteer_times_field->label = "Volunteer Time Options for Teachers";
    $teacher_volunteer_times_field->id = 8;
    $teacher_volunteer_times_field->isRequired = false;
    $teacher_volunteer_times_field->description = "E.g. Saturday (10am-4pm), Either Saturday or Sunday, etc.";

    // assign all of the previous attributes to our newly created form
    $competition_creation_form->fields[] = $competition_name_field;
    $competition_creation_form->fields[] = $competition_date_field;
    $competition_creation_form->fields[] = $competition_location_field;
    $competition_creation_form->fields[] = $student_registration_start_date_field;
    $competition_creation_form->fields[] = $student_registration_end_date_field;
    $competition_creation_form->fields[] = $teacher_registration_start_date_field;
    $competition_creation_form->fields[] = $teacher_registration_end_date_field;
    $competition_creation_form->fields[] = $teacher_volunteer_times_field;

    // custom submission message to let the festival chairman know the creation was
    // a success
    $successful_submission_message = 'Congratulations! A new music competition has been created.';
    $successful_submission_message .= ' There are now two new forms for students and teacher to use';
    $successful_submission_message .= ' for registration. The name for each new form is prepended with';
    $successful_submission_message .= ' the name of the new music competition previously created.';
    $competition_creation_form->confirmation['type'] = 'message';
    $competition_creation_form->confirmation['message'] = $successful_submission_message;

    // add the new form to the festival chairman's dashboard
    $new_form_id = GFAPI::add_form($competition_creation_form->createFormArray());

    // make sure the new form was added without error
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }
    else {
      return $new_form_id;
    }
  }

  /**
  * This function is responsible for adding some default address field values.
  *
  * This function is used to pre-populate the address fields of a gravity form
  * with some generic, default values.
  *
  * @param Field Object $field  The name of field used for addressing
  *
  * @since 1.0.0
  * @author KREW
  */
  private static function aria_add_default_address_inputs($field) {
    $field->inputs = array(
      array("id" => "{$field->id}.1",
      			"label" => "Street Address",
      			"name" => ""),
      array("id" => "{$field->id}.2",
      			"label" => "Address Line 2",
      			"name" => ""),
      array("id" => "{$field->id}.3",
      			"label" => "City",
      			"name" => ""),
      array("id" => "{$field->id}.4",
      			"label" => "State \/ Province",
      			"name" => ""),
      array("id" => "{$field->id}.5",
      			"label" => "ZIP \/ Postal Code",
      			"name" => ""),
      array("id" => "{$field->id}.6",
      			"label" => "Country",
      			"name" => ""),
    );

    return $field;
  }

  public static function aria_add_default_name_inputs($field) {
    $field->nameFormat = 'advanced';
    $field->inputs = array(
      array(
        "id"=>"{$field->id}.2",
        "label"=>"Prefix",
        "name"=>"",
        "choices"=> array(
          array(
            "text"=>"Mr.",
            "value"=>"Mr.",
            "isSelected"=>false,
            "price"=>""
          ),
          array(
            "text"=>"Mrs.",
            "value"=>"Mrs.",
            "isSelected"=>false,
            "price"=>""
          ),
          array(
            "text"=>"Miss",
            "value"=>"Miss",
            "isSelected"=>false,
            "price"=>""
          ),
          array(
            "text"=>"Ms.",
            "value"=>"Ms.",
            "isSelected"=>false,
            "price"=>""
          ),
          array(
            "text"=>"Dr.",
            "value"=>"Dr.",
            "isSelected"=>false,
            "price"=>""
          ),
          array(
            "text"=>"Prof.",
            "value"=>"Prof.",
            "isSelected"=>false,
            "price"=>""
          ),
          array(
            "text"=>"Rev.",
            "value"=>"Rev.",
            "isSelected"=>false,
            "price"=>""
          )
        ),
        "isHidden"=>true,
        "inputType"=>"radio"
      ),
      array(
        "id"=>"{$field->id}.3",
        "label"=>"First",
        "name"=>""
      ),
      array(
        "id"=>"{$field->id}.4",
        "label"=>"Middle",
        "name"=>"",
        "isHidden"=>true
      ),
      array(
        "id"=>"{$field->id}.6",
        "label"=>"Last",
        "name"=>""
      ),
      array(
        "id"=>"{$field->id}.8",
        "label"=>"Suffix",
        "name"=>"",
        "isHidden"=>true
      )
    );
    return $field;
  }


  /**

  */
  private static function aria_add_checkbox_input($field, $new_input) {
    $next_input = count( $field->inputs ) + 1;

    $field->inputs[] = array(
          "id" => "{$field->id}.{$next_input}",
          "label" => $new_input,
          "name" => ""
        );
    return $field;
  }

  /**
   * This function defines an associative array used in the teacher form.
   *
   * This function returns an array that maps all of the names of the fields in the
   * teacher form to a unique integer so that they can be referenced. Moreover, this
   * array helps prevent the case where the names of these fields are modified from
   * the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_teacher_field_id_array() {
    // CAUTION, This array is used as a source of truth. Changing these values may
    // result in catastrophic failure. If you do not want to feel the bern,
    // consult an aria developer before making changes to this portion of code.
    return array(
      'name' => 1,
			'first_name' => 1.1,
			'last_name' => 1.2,
      'email' => 2,
      'phone' => 3,
      'volunteer_preference' => 4,
      'volunteer_time' => 5,
      'student_name' => 6,
			'student_first_name' => 6.1,
			'student_last_name' => 6.2,
      'song_1_period' => 7,
      'song_1_composer' => 8,
      'song_1_selection' => 9,
      'song_2_period' => 10,
      'song_2_composer' => 11,
      'song_2_selection' => 12,
      'theory_score' => 13,
      'alternate_theory' => 14,
      'competition_format' => 15,
      'timing_of_pieces' => 16,
      'is_judging' => 17, // !!!DO WE WANT TO CHANGE THIS NUMBER
      'student_level' => 18,
      'alt_song_2_period' => 19,
      'alt_song_2_composer' => 20,
      'alt_song_2_selection' => 21
    );
  }

  /**
   * This function will create a new form for the teachers to use to register student
   * information.
   *
   * This function is responsible for creating and adding all of the associated fields
   * that are necessary for music teachers to enter data about their students that are
   * competing.
   *
   * @param   Entry  $competition_entry The entry of the newly created music competition
   *
   * @since 1.0.0
   * @author KREW
   */
   private static function aria_create_teacher_form($competition_entry, $volunteer_time_options_array) {
    $field_mapping = self::aria_get_competition_entry_meta();

    $competition_name = $competition_entry[$field_mapping['Name of Competition']];
    $teacher_form = new GF_Form("{$competition_name} Teacher Registration", "");
    $field_id_arr = self::aria_teacher_field_id_array();

    // teacher name
    $teacher_name_field = new GF_Field_Name();
    $teacher_name_field->label = "Name";
    $teacher_name_field->id = $field_id_arr['name'];
    $teacher_name_field->isRequired = false;
    $teacher_name_field = self::aria_add_default_name_inputs($teacher_name_field);
    $teacher_form->fields[] = $teacher_name_field;

    // teacher email
    $teacher_email_field = new GF_Field_Email();
    $teacher_email_field->label = "Email";
    $teacher_email_field->id = $field_id_arr['email'];
    $teacher_email_field->isRequired = false;
    $teacher_form->fields[] = $teacher_email_field;

    // teacher phone
    $teacher_phone_field = new GF_Field_Phone();
    $teacher_phone_field->label = "Phone";
    $teacher_phone_field->id = $field_id_arr['phone'];
    $teacher_phone_field->isRequired = false;
    $teacher_form->fields[] = $teacher_phone_field;

    // !!!new field
    // teacher is judging
    $teacher_judging_field = new GF_Field_Radio();
    $teacher_judging_field->label = "Are you scheduled to judge for the festival?";
    $teacher_judging_field->id = $field_id_arr['is_judging'];
    $teacher_judging_field->isRequired = false;
    $teacher_judging_field->choices = array(
    	array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
    	array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $conditionalRules = array();
    $conditionalRules[] = array(
    	'fieldId' => $field_id_arr['is_judging'],
    	'operator' => 'is',
    	'value' => 'No'
    );
    $teacher_form->fields[] = $teacher_judging_field;

    // teacher volunteer preference
    $volunteer_preference_field = new GF_Field_Checkbox();
    $volunteer_preference_field->label = "Volunteer Preference";
    $volunteer_preference_field->id = $field_id_arr['volunteer_preference'];
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
    //self::aria_add_checkbox_input( $volunteer_preference_field, 'Proctor sessions' );
    $volunteer_preference_field->description = "Please check at least two volunteer job"
    ." preferences for this year's Festival. You will be notified by email of your"
    ." volunteer assignments as Festival approaches.";
    $volunteer_preference_field->descriptionPlacement = 'above';
    $volunteer_preference_field->conditionalLogic = array(
	'actionType' => 'show',
	'logicType' => 'all',
	'rules' => $conditionalRules
    );
    $teacher_form->fields[] = $volunteer_preference_field;

    // volunteer time
    $volunteer_time_field = new GF_Field_Checkbox();
    $volunteer_time_field->label = "Times Available for Volunteering";
    $volunteer_time_field->id = $field_id_arr['volunteer_time'];
    $volunteer_time_field->isRequired = false;
    $volunteer_time_field->description = "Please check at least two times you are"
    ." available to volunteer during Festival weekend.";
    $volunteer_time_field->descriptionPlacement = 'above';
    $volunteer_time_field->choices = array();
    if (is_array($volunteer_time_options_array)) {
      foreach( $volunteer_time_options_array as $volunteer_time ) {
        $volunteer_time_field->choices[]
          = array('text' => $volunteer_time, 'value' => $volunteer_time, 'isSelected' => false);
      }
    }
    //foreach( $competition_entry[ $field_mapping['Volunteer Times']]
    //$volunteer_time_field->choices = $volunteer_time_options['choices'];
    $volunteer_time_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $conditionalRules
    );
    $teacher_form->fields[] = $volunteer_time_field;

    // student name
    $student_name_field = new GF_Field_Name();
    $student_name_field->label = "Student Name";
    $student_name_field->id = $field_id_arr['student_name'];
    $student_name_field->isRequired = false;
    $student_name_field = self::aria_add_default_name_inputs($student_name_field);
    $teacher_form->fields[] = $student_name_field;

    // !!!student level
    $student_level_field = new GF_Field_Select();
    $student_level_field->label = "Student Level";
    $student_level_field->id = $field_id_arr['student_level'];
    $student_level_field->isRequired = false;
    // !!! replace
    $student_level_field->choices = array(

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
    $teacher_form->fields[] = $student_level_field;

    // student's first song period
    $song_one_period_field = new GF_Field_Select();
    $song_one_period_field->label = "Song 1 Period";
    $song_one_period_field->id = $field_id_arr['song_1_period'];
    $song_one_period_field->choices = array(
      array('text' => 'Baroque', 'value' => '1', 'isSelected' => false),
      array('text' => 'Classical', 'value' => '2', 'isSelected' => false),
      array('text' => 'Romantic', 'value' => '3', 'isSelected' => false),
      array('text' => 'Contemporary', 'value' => '4', 'isSelected' => false),
   );
    $song_one_period_field->isRequired = false;
    $teacher_form->fields[] = $song_one_period_field;

    // student's first song composer
    $song_one_composer_field = new GF_Field_Select();
    $song_one_composer_field->label = "Song 1 Composer";
    $song_one_composer_field->id = $field_id_arr['song_1_composer'];
    $song_one_composer_field->isRequired = false;
    $teacher_form->fields[] = $song_one_composer_field;

    // student's first song selection
    $song_one_selection_field = new GF_Field_Select();
    $song_one_selection_field->label = "Song 1 Selection";
    $song_one_selection_field->id = $field_id_arr['song_1_selection'];
    $song_one_selection_field->isRequired = false;
    $teacher_form->fields[] = $song_one_selection_field;

    // !!! need to add column E (conflict resolution)

    // !!! if level is not 11
     $is_11_rule = array();
    $is_11_rule[] = array(
    	'fieldId' => $field_id_arr['student_level'],
    	'operator' => 'is',
    	'value' => '11'
    );
    $is_not_11_rule = array();
    $is_not_11_rule[] = array(
    	'fieldId' => $field_id_arr['student_level'],
    	'operator' => 'isnot',
    	'value' => '11'
    );
    // student's second song period
    $song_two_period_field = new GF_Field_Select();
    $song_two_period_field->label = "Song 2 Period";
    $song_two_period_field->id = $field_id_arr['song_2_period'];
    $song_two_period_field->isRequired = false;
    $song_two_period_field->choices = array(
      array('text' => 'Baroque', 'value' => '1', 'isSelected' => false),
      array('text' => 'Classical', 'value' => '2', 'isSelected' => false),
      array('text' => 'Romantic', 'value' => '3', 'isSelected' => false),
      array('text' => 'Contemporary', 'value' => '4', 'isSelected' => false),
    );
    $song_two_period_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );

    $teacher_form->fields[] = $song_two_period_field;

    // student's second song composer
    $song_two_composer_field = new GF_Field_Select();
    $song_two_composer_field->label = "Song 2 Composer";
    $song_two_composer_field->id = $field_id_arr['song_2_composer'];
    $song_two_composer_field->isRequired = false;
    $song_two_composer_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );

    $teacher_form->fields[] = $song_two_composer_field;

    // student's second song selection
    $song_two_selection_field = new GF_Field_Select();
    $song_two_selection_field->label = "Song 2 Selection";
    $song_two_selection_field->id = $field_id_arr['song_2_selection'];
    $song_two_selection_field->isRequired = false;
    $song_two_selection_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );

    $teacher_form->fields[] = $song_two_selection_field;

    // !!! need to add column E (conflict resolution)

    // if level is 11
    // Composer
    $alt_song_two_composer_field = new GF_Field_Text();
    $alt_song_two_composer_field->label = "Song 2 Composer";
    $alt_song_two_composer_field->id = $field_id_arr['alt_song_2_composer'];
    $alt_song_two_composer_field->isRequired = false;
    $alt_song_two_composer_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_11_rule
    );

    $teacher_form->fields[] = $alt_song_two_composer_field;
    // Piece Title
    // Key (e.g. D Major, F Minor)
    // Movement number, if applicable (e.g. 1st, 2nd, 3rd, 4th)
    // Movement description, if applicable (e.g. Adante, Presto, Rondo Allegro Comodo)
    // Identifying number, if applicable (e.g. BWV, Opus, HOB, etc)

    // student's theory score
    $student_theory_score = new GF_Field_Number();
    $student_theory_score->label = "Theory Score (percentage)";
    $student_theory_score->id = $field_id_arr['theory_score'];
    $student_theory_score->isRequired = false;
    $student_theory_score->numberFormat = "decimal_dot";
    $student_theory_score->rangeMin = 0;
    $student_theory_score->rangeMax = 100;
    $teacher_form->fields[] = $student_theory_score;

    // student's alternate theory
    $alternate_theory_field = new GF_Field_Checkbox();
    $alternate_theory_field->label = "Check if alternate theory exam was completed.";
    $alternate_theory_field->id = $field_id_arr['alternate_theory'];
    $alternate_theory_field->isRequired = false;
    $alternate_theory_field->choices = array(
      array('text' => 'Alternate theory exam completed',
      'value' => 'Alternate theory exam completed',
      'isSelected' => false)
    );
    $teacher_form->fields[] = $alternate_theory_field;

    // competition format
    $competition_format_field = new GF_Field_Radio();
    $competition_format_field->label = "Format of Competition";
    $competition_format_field->id = $field_id_arr['competition_format'];
    $competition_format_field->isRequired = false;
    $competition_format_field->choices = array(
      array('text' => 'Traditional', 'value' => 'Traditional', 'isSelected' => false),
      array('text' => 'Competitive', 'value' => 'Competitive', 'isSelected' => false),
      array('text' => 'Master Class (if upper level)', 'value' => 'Master Class', 'isSelected' => false)
    );
    $teacher_form->fields[] = $competition_format_field;

    // timing field
    $timing_of_pieces_field = new GF_Field_Number();
    $timing_of_pieces_field->label = "Timing of pieces (minutes)";
    $timing_of_pieces_field->id = $field_id_arr['timing_of_pieces'];
    $timing_of_pieces_field->isRequired = false;
    $timing_of_pieces_field->numberFormat = "decimal_dot";
    $teacher_form->fields[] = $timing_of_pieces_field;

    // custom submission message to let the festival chairman know the creation was
    // a success
    $successful_submission_message = 'Congratulations! You have just successfully registered';
    $successful_submission_message .= ' one your students.';
    $competition_creation_form->confirmation['type'] = 'message';
    $competition_creation_form->confirmation['message'] = $successful_submission_message;

    // add the new form to the festival chairman's dashboard
    $new_form_id = GFAPI::add_form($teacher_form->createFormArray());

    // make sure the new form was added without error
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }

    return $new_form_id;
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
  public static function aria_student_field_id_array() {
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
      'compliance_statement' => 9
    );
  }

  /**
   * This function will create a new form for student registration.
   *
   * This function is responsible for creating and adding all of the associated
   * fields that are necessary for students to enter data about their upcoming
   * music competition.
   *
   * @param Entry  $competition_entry The entry of the newly created music competition
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_create_student_form( $competition_entry ) {
    $field_mapping = self::aria_get_competition_entry_meta();

    $competition_name = $competition_entry[$field_mapping['Name of Competition']];
    $student_form = new GF_Form("{$competition_name} Student Registration", "");
    $field_id_array = self::aria_student_field_id_array();

    // parent name
    $parent_name_field = new GF_Field_Name();
    $parent_name_field->label = "Parent Name";
    $parent_name_field->id = $field_id_array['parent_name'];
    $parent_name_field->isRequired = false;
    $parent_name_field = self::aria_add_default_name_inputs($parent_name_field);
    $student_form->fields[] = $parent_name_field;

    // parent email
    $parent_email_field = new GF_Field_Email();
    $parent_email_field->label = "Parent's Email";
    $parent_email_field->id = $field_id_array['parent_email'];
    $parent_email_field->isRequired = false;
    $student_form->fields[] = $parent_email_field;

    // student name
    $student_name_field = new GF_Field_Name();
    $student_name_field->label = "Student Name";
    $student_name_field->description = "Please capitalize your child's first ".
    "and last names and double check the spelling.  The way you type the name ".
    "here is the way it will appear on all awards and in the Command ".
    "Performance program.";
    $student_name_field->descriptionPlacement = 'above';
    $student_name_field->id = $field_id_array['student_name'];
    $student_name_field->isRequired = false;
    $student_name_field = self::aria_add_default_name_inputs($student_name_field);
    $student_form->fields[] = $student_name_field;

    // student birthday
    $student_birthday_date_field = new GF_Field_Date();
    $student_birthday_date_field->label = "Student Birthday";
    $student_birthday_date_field->id = $field_id_array['student_birthday'];
    $student_birthday_date_field->isRequired = false;
    $student_birthday_date_field->calendarIconType = 'calendar';
    $student_birthday_date_field->dateType = 'datepicker';
    $student_form->fields[] = $student_birthday_date_field;

    // student's piano teacher
    $piano_teachers_field = new GF_Field_Select();
    $piano_teachers_field->label = "Piano Teacher's Name";
    $piano_teachers_field->id = $field_id_array['teacher_name'];
    $piano_teachers_field->isRequired = false;
    $piano_teachers_field->description = "TBD";
    $student_form->fields[] = $piano_teachers_field;

    // student's piano teacher does not exist
    $teacher_missing_field = new GF_Field_Text();
    $teacher_missing_field->label = "If your teacher's name is not listed, ".
    "enter name below.";
    $teacher_missing_field->id = $field_id_array['not_listed_teacher_name'];
    $teacher_missing_field->isRequired = false;
    $student_form->fields[] = $teacher_missing_field;

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
    $available_times->inputs = array();
    $available_times = self::aria_add_checkbox_input( $available_times, 'Saturday' );
    $available_times = self::aria_add_checkbox_input( $available_times, 'Sunday' );
    $student_form->fields[] = $available_times;

    // student's available times to compete for command performance
    $command_times = new GF_Field_Checkbox();
    $command_times->label = "Preferred Command Performance Time (check all available times)";
    $command_times->id = $field_id_array['preferred_command_performance'];
    $command_times->isRequired = false;
    $command_times->description = "Please check the Command Performance time ".
    "that you prefer in the event that your child receives a superior rating.";
    $command_times->choices = array(
      array('text' => 'Thursday 5:30', 'value' => 'Thursday 5:30', 'isSelected' => false),
      array('text' => 'Thursday 7:30', 'value' => 'Sunday', 'isSelected' => false)
    );
    $command_times->inputs = array();
    $command_times = self::aria_add_checkbox_input( $command_times, 'Thursday 5:30' );
    $command_times = self::aria_add_checkbox_input( $command_times, 'Thursday 7:30' );
    $student_form->fields[] = $command_times;

    // the compliance field for parents
    $compliance_field = new GF_Field_checkbox();
    $compliance_field->label = "Compliance Statement";
    $compliance_field->id = $field_id_array['compliance_statement'];
    $compliance_field->isRequired = true;
    $compliance_field->description = "As a parent, I understand and agree to ".
    "comply with all rules, regulations, and amendments as stated in the ".
    "Festival syllabus. I am in full compliance with the laws regarding ".
    "photocopies and can provide verification of authentication of any legally ".
    "printed music. I understand that adjudicator decisions are final and ".
    "will not be contested. I know that small children may not remain in the ".
    "room during performances of non-family members. I understand that ".
    "requests for specific days/times will be scheduled if possible but cannot".
    " be guaranteed.";
    $compliance_field->choices = array(
      array('text' => 'I have read and agree with the following statement:',
      'value' => 'Agree',
      'isSelected' => false),
    );
    $compliance_field->inputs = array();
    $compliance_field = self::aria_add_checkbox_input( $compliance_field, 'I have read and agree with the following statement:' );
    $student_form->fields[] = $compliance_field;

    // custom submission message to let the festival chairman know the creation was
    // a success
    $successful_submission_message = 'Congratulations! You have just';
    $successful_submission_message .= ' successfully registered your child.';
    $student_form->confirmation['type'] = 'message';
    $student_form->confirmation['message'] = $successful_submission_message;

    // add the new form to the festival chairman's dashboard
    $new_form_id = GFAPI::add_form($student_form->createFormArray());

    // make sure the new form was added without error
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }

    return $new_form_id;
  }

	/**
	 * Function that tries to retrieve page IDs.
	 */
  public static function aria_update_page_ids() {
    $student_form = get_page_by_title("Wes CC");
		wp_die("DB id: " . $student_form->ID);
	}

  public static function aria_publish_form($form_title, $form_id){
    // Set Parameters for the form
    $postarr = array(
      'post_title' => $form_title,
      'post_content' => "[gravityform id=\"{$form_id}\" title=\"true\" description=\"true\"]",
      'post_status' => 'publish',
      'post_type' => 'page'
    );

    // Force a wp_error to be returned on failure
    $return_wp_error_on_failure = true;

    // Create a wp_post
    $post_id = wp_insert_post($postarr, $return_wp_error_on_failure);

    // If not a wp_error, get the url from the post and return.
    if(!is_wp_error($post_id)) {
      return esc_url(get_permalink($post_id));
    }
    return $post_id;
  }
}
