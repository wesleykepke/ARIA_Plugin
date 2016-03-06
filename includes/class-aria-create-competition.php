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
require_once("aria-constants.php");
require_once("class-aria-teacher-upload.php");

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
   * This function will create the form that can create new music competitions.
   *
   * This function is called in "class-aria-activator.php" and is responsible
   * for creating the form that allows the festival chairman to create new music
   * competitions (if this form does not already exist). If no such form exists,
   * this function will create a new form designed specifically for creating new
   * music competitions.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_competition_activation() {
    // create the new competition form if it doesn't exist
    $form_id = ARIA_API::aria_get_create_competition_form_id();
    if ($form_id === -1) {
      $form_id = self::aria_create_competition_form();
    }

    // publish the new form
    ARIA_API::aria_publish_form(CREATE_COMPETITION_FORM_NAME, $form_id);
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
    // make sure the create competition form is calling this function
    $competition_creation_form_id = ARIA_API::aria_get_create_competition_form_id();
    if ($form['id'] === $competition_creation_form_id) {
			$field_mapping = ARIA_API::aria_competition_field_id_array();
			$competition_name = $entry[$field_mapping['competition_name']];

			// create the student and teacher forms
      $student_form_id = self::aria_create_student_form($entry);
      $teacher_form_id = self::aria_create_teacher_form($entry, unserialize($entry[(string) $field_mapping['competition_volunteer_times']]));
      $student_form_url = ARIA_API::aria_publish_form("{$competition_name} Student Registration", $student_form_id);
      $teacher_form_url = ARIA_API::aria_publish_form("{$competition_name} Teacher Registration", $teacher_form_id);

			// create the sutdent and teacher (master) forms
			$student_master_form_id =
      ARIA_Create_Master_Forms::aria_create_student_master_form($competition_name);
			$teacher_master_form_id =
      ARIA_Create_Master_Forms::aria_create_teacher_master_form($competition_name);

      // associate all of the related forms
      $related_forms = array(
        'student_public_form_id' => $student_form_id,
        'teacher_public_form_id' => $teacher_form_id,
        'student_master_form_id' => $student_master_form_id,
        'teacher_master_form_id' => $teacher_master_form_id,
        'student_public_form_url' => $student_form_url,
        'teacher_public_form_url' => $teacher_form_url
      );

      //
      $teacher_csv_file_path = ARIA_API::aria_get_teacher_csv_file_path($entry, $form);
      //wp_die($teacher_csv_file_path);
      //wp_die('aria_create_teacher_and_student_forms: ' . json_encode($entry));
      ARIA_Teacher::aria_upload_from_csv($teacher_csv_file_path, $teacher_master_form_id);

      // obtain form objects for each of the four forms
      $student_public_form = GFAPI::get_form($student_form_id);
      $teacher_public_form = GFAPI::get_form($teacher_form_id);
      $student_master_form = GFAPI::get_form($student_master_form_id);
      $teacher_master_form = GFAPI::get_form($teacher_master_form_id);

      // assign the related form objects
      $student_public_form['aria_relations'] = $related_forms;
      $teacher_public_form['aria_relations'] = $related_forms;
      $student_master_form['aria_relations'] = $related_forms;
      $teacher_master_form['aria_relations'] = $related_forms;

      // update the related forms
      GFAPI::update_form($student_public_form);
      GFAPI::update_form($teacher_public_form);
      GFAPI::update_form($student_master_form);
      GFAPI::update_form($teacher_master_form);

      $teacher_public_form = GFAPI::get_form($teacher_form_id);

      // change the confirmation message that the festival chairman sees
      // after competition creation
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
    $competition_creation_form = new GF_Form(CREATE_COMPETITION_FORM_NAME, "");

    // TODO: replace all the id's with their associative array offsets
    $field_mapping = ARIA_API::aria_competition_field_id_array();

    // name
    $competition_name_field = new GF_Field_Text();
    // !!! maybe this should be admin label and label should be like
    // $competition_name_field->label = "Competition Name";
    $competition_name_field->label = "competition_name";
    $competition_name_field->id = 1;
    $competition_name_field->isRequired = false;

    // date of the competition
    $competition_date_field = new GF_Field_Date();
    $competition_date_field->label = "competition_date";
    $competition_date_field->id = 2;
    $competition_date_field->isRequired = false;
    $competition_date_field->calendarIconType = 'calendar';
    $competition_date_field->dateType = 'datepicker';

    // location
    $competition_location_field = new GF_Field_Address();
    $competition_location_field->label = "competition_location";
    $competition_location_field->id = 3;
    $competition_location_field->isRequired = false;
    $competition_location_field = self::aria_add_default_address_inputs($competition_location_field);

    // competition_student_reg_start
    $student_registration_start_date_field = new GF_Field_Date();
    $student_registration_start_date_field->label = "competition_student_reg_start";
    $student_registration_start_date_field->id = 4;
    $student_registration_start_date_field->isRequired = false;
    $student_registration_start_date_field->calendarIconType = 'calendar';
    $student_registration_start_date_field->dateType = 'datepicker';

    // student registration deadline
    $student_registration_end_date_field = new GF_Field_Date();
    $student_registration_end_date_field->label = "competition_student_reg_end";
    $student_registration_end_date_field->id = 5;
    $student_registration_end_date_field->isRequired = false;
    $student_registration_end_date_field->calendarIconType = 'calendar';
    $student_registration_end_date_field->dateType = 'datepicker';

    // competition_teacher_reg_start
    $teacher_registration_start_date_field = new GF_Field_Date();
    $teacher_registration_start_date_field->label = "competition_teacher_reg_start";
    $teacher_registration_start_date_field->id = 6;
    $teacher_registration_start_date_field->isRequired = false;
    $teacher_registration_start_date_field->calendarIconType = 'calendar';
    $teacher_registration_start_date_field->dateType = 'datepicker';

    // teacher registration deadline
    $teacher_registration_end_date_field = new GF_Field_Date();
    $teacher_registration_end_date_field->label = "competition_teacher_reg_start";
    $teacher_registration_end_date_field->id = 7;
    $teacher_registration_end_date_field->isRequired = false;
    $teacher_registration_end_date_field->calendarIconType = 'calendar';
    $teacher_registration_end_date_field->dateType = 'datepicker';

    // teacher volunteer options
    $teacher_volunteer_times_field = new GF_Field_List();
    $teacher_volunteer_times_field->label = "Volunteer Time Options for Teachers";
    $teacher_volunteer_times_field->id = 8;
    $teacher_volunteer_times_field->isRequired = false;
    $teacher_volunteer_times_field->description = "e.g. Saturday (10am-4pm), Either Saturday or Sunday, etc.";
    $teacher_volunteer_times_field->descriptionPlacement = 'above';

    // teacher csv file upload

    $teacher_csv_file_upload = new GF_Field_FileUpload();
    $teacher_csv_file_upload->label = CSV_TEACHER_FIELD_NAME;
    $teacher_csv_file_upload->id = $field_mapping['competition_teacher_csv_upload'];
    $teacher_csv_file_upload->isRequired = false;

    // assign all of the previous attributes to our newly created form
    $competition_creation_form->fields[] = $competition_name_field;
    $competition_creation_form->fields[] = $competition_date_field;
    $competition_creation_form->fields[] = $competition_location_field;
    $competition_creation_form->fields[] = $student_registration_start_date_field;
    $competition_creation_form->fields[] = $student_registration_end_date_field;
    $competition_creation_form->fields[] = $teacher_registration_start_date_field;
    $competition_creation_form->fields[] = $teacher_registration_end_date_field;
    $competition_creation_form->fields[] = $teacher_volunteer_times_field;
    $competition_creation_form->fields[] = $teacher_csv_file_upload;

    // Identify form as a teacher uploading form
    $form_array = $competition_creation_form->createFormArray();
    $form_array['isTeacherUploadForm'] = true;

    // Add form to dashboard
    $new_form_id = GFAPI::add_form($form_array);
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
      			"label" => "competition_address_first",
      			"name" => ""),
      array("id" => "{$field->id}.2",
      			"label" => "competition_address_second",
      			"name" => ""),
      array("id" => "{$field->id}.3",
      			"label" => "competition_city",
      			"name" => ""),
      array("id" => "{$field->id}.4",
      			"label" => "State \/ Province",
      			"name" => ""),
      array("id" => "{$field->id}.5",
      			"label" => "ZIP \/ Postal Code",
      			"name" => ""),
      array("id" => "{$field->id}.6",
      			"label" => "competition_country",
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
  public static function aria_add_checkbox_input($field, $new_input) {
    $next_input = sizeof( $field->inputs ) + 1;

    if( is_array($new_input) ){
      foreach( $new_input as $input ){
        $field->inputs[] = array(
          "id" => "{$field->id}.{$next_input}",
          "label" => $input,
          "name" => ""
        );
        $next_input = $next_input + 1;
      }
    }
    else{
      $field->inputs[] = array(
        "id" => "{$field->id}.{$next_input}",
        "label" => $new_input,
        "name" => ""
      );
    }

    return $field;
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
    $field_mapping = ARIA_API::aria_competition_field_id_array();

    $competition_name = $competition_entry[$field_mapping['competition_name']];
    $teacher_form = new GF_Form("{$competition_name} Teacher Registration", "");
    $field_id_arr = ARIA_API::aria_teacher_field_id_array();

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
    $volunteer_inputs = array(
        'Proctor sessions',
        'Monitor door during sessions',
        'Greet students and parents',
        'Prepare excellent ribbons',
        'Put seals on certificates',
        'Early set up',
        'Clean up',
        'Help with food for judges and volunteers'
      );
    $volunteer_preference_field = self::aria_add_checkbox_input( $volunteer_preference_field, $volunteer_inputs );
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
        $volunteer_time_field = self::aria_add_checkbox_input( $volunteer_time_field, $volunteer_time );
      }
    }
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
    $alt_song_two_composer_field->isRequired = true;
    $alt_song_two_composer_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_11_rule
    );

    $teacher_form->fields[] = $alt_song_two_composer_field;

    // Piece Title
    $alt_song_two_selection_field = new GF_Field_Text();
    $alt_song_two_selection_field->label = "Song 2 Piece Title";
    $alt_song_two_selection_field->id = $field_id_arr['alt_song_2_selection'];
    $alt_song_two_selection_field->isRequired = true;
    $alt_song_two_selection_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $is_11_rule
    );
    $teacher_form->fields[] = $alt_song_two_selection_field;


    // Key (e.g. D Major, F Minor)
    $alt_song_two_key_field = new GF_Field_Text();
    $alt_song_two_key_field->label = "Song 2 Key";
    $alt_song_two_key_field->id = $field_id_arr['alt_song_2_key'];
    $alt_song_two_key_field->isRequired = false;
    $alt_song_two_key_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $is_11_rule
    );
    $alt_song_two_key_field->description = "e.g. D Major, F Minor";
    $alt_song_two_key_field->descriptionPlacement = 'above';
    $teacher_form->fields[] = $alt_song_two_key_field;

    // Movement number, if applicable (e.g. 1st, 2nd, 3rd, 4th)
    $alt_song_two_movement_num_field = new GF_Field_Text();
    $alt_song_two_movement_num_field->label = "Song 2 Movement Number, if applicable";
    $alt_song_two_movement_num_field->id = $field_id_arr['alt_song_2_movement_number'];
    $alt_song_two_movement_num_field->isRequired = false;
    $alt_song_two_movement_num_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $is_11_rule
    );
    $alt_song_two_movement_num_field->description = "e.g. 1st, 2nd, 3rd, etc.";
    $alt_song_two_movement_num_field->descriptionPlacement = 'above';
    $teacher_form->fields[] = $alt_song_two_movement_num_field;

    // Movement description, if applicable (e.g. Adante, Presto, Rondo Allegro Comodo)
    $alt_song_two_movement_desc_field = new GF_Field_Text();
    $alt_song_two_movement_desc_field->label = "Song 2 Movement Description, if applicable";
    $alt_song_two_movement_desc_field->id = $field_id_arr['alt_song_2_movement_description'];
    $alt_song_two_movement_desc_field->isRequired = false;
    $alt_song_two_movement_desc_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $is_11_rule
    );
    $alt_song_two_movement_desc_field->description = "e.g. Andante, Presto, Rondo Allegro Comodo, etc.";
    $alt_song_two_movement_desc_field->descriptionPlacement = 'above';
    $teacher_form->fields[] = $alt_song_two_movement_desc_field;

    // Identifying number, if applicable (e.g. BWV, Opus, HOB, etc)
    $alt_song_two_identifying_num_field = new GF_Field_Text();
    $alt_song_two_identifying_num_field->label = "Song 2 Identifying Number, if applicable";
    $alt_song_two_identifying_num_field->id = $field_id_arr['alt_song_2_identifying_number'];
    $alt_song_two_identifying_num_field->isRequired = false;
    $alt_song_two_identifying_num_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $is_11_rule
    );
    $alt_song_two_identifying_num_field->description = "e.g. BWV, Opus, HOB, etc.";
    $alt_song_two_identifying_num_field->descriptionPlacement = 'above';
    $teacher_form->fields[] = $alt_song_two_identifying_num_field;

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
    $alternate_theory_field = self::aria_add_checkbox_input( $alternate_theory_field, 'Alternate theory exam completed' );
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

    $successful_submission_message = 'Congratulations! You have just';
    $successful_submission_message .= ' successfully registered your student.';
    $teacher_form->confirmation['type'] = 'message';
    $teacher_form->confirmation['message'] = $successful_submission_message;

    $teacher_form_array = $teacher_form->createFormArray();
    $teacher_form_array['isTeacherPublicForm'] = true;

    // add the new form to the festival chairman's dashboard
    $new_form_id = GFAPI::add_form($teacher_form_array);

    // make sure the new form was added without error
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }

    return $new_form_id;
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
    $field_mapping = ARIA_API::aria_competition_field_id_array();

    $competition_name = $competition_entry[$field_mapping['competition_name']];
    $student_form = new GF_Form("{$competition_name} Student Registration", "");
    $field_id_array = ARIA_API::aria_student_field_id_array();

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
    $student_name_field->isRequired = true;
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
    $piano_teachers_field->isRequired = true;
    $piano_teachers_field->description = "TBD";
    $piano_teachers_field->choices = array(
      array('text' => 'Test 1', 'value' => 'Tim', 'isSelected' => false),
      array('text' => 'Test 2', 'value' => 'Jim', 'isSelected' => false)
    );
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
    $available_times = self::aria_add_checkbox_input( $available_times, array( 'Saturday', 'Sunday' ));
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
      array('text' => 'Thursday 7:30', 'value' => 'Thursday 7:30', 'isSelected' => false)
    );
    $command_times->inputs = array();
    $command_times = self::aria_add_checkbox_input( $command_times, array('Thursday 5:30', 'Thursday 7:30') );
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

    $student_form_arr = $student_form->createFormArray();
    $student_form_arr['isStudentPublicForm'] = true;

    // add the new form to the festival chairman's dashboard
    $new_form_id = GFAPI::add_form($student_form_arr);

    // make sure the new form was added without error
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }

    return $new_form_id;
  }
}
