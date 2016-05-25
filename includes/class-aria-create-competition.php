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

//require_once("class-aria-api.php");
//require_once("class-aria-create-master-forms.php");
//require_once("aria-constants.php");
//require_once("class-aria-teacher-upload.php");

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
      ARIA_API::aria_publish_form(CREATE_COMPETITION_FORM_NAME, $form_id, CHAIRMAN_PASS, true);
    }
  }

  /**
   * This function will save the content of a competition entry object to a file.
   *
   * Using the entry object that is returned from the create competition form,
   * this function will serialize the entry object and save it to a file location
   * on the server so that it can be referenced in the event that the user removes
   * the create competition form on the WordPress dashboard.
   *
   * @param   Entry Object  $entry  The entry object that is about to be serialized to file.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_save_comp_to_file($entry) {
    $field_mapping = ARIA_API::aria_competition_field_id_array();
    $title = $entry[strval($field_mapping['competition_name'])];
    $title = str_replace(' ', '_', $title);
    $file_path = ARIA_FILE_UPLOAD_LOC . $title . "_Entry.txt";
    $entry_data = serialize($entry);
    $fp = fopen($file_path, 'w+');
    if ($fp) {
      fwrite($fp, $entry_data);
      fclose($fp);
    }
  }

  /**
   * This function will read the content of a competition entry object from a file.
   *
   * Using the entry object that is returned from the create competition form,
   * this function will serialize the entry object and save it to a file location
   * on the server so that it can be referenced in the event that the user removes
   * the create competition form on the WordPress dashboard.
   *
   * @param   Entry Object  $entry  The entry object that is about to be serialized to file.
   *
   * @since 1.0.0
   * @author KREW
   */
  /*
  public static function aria_save_comp_to_file($entry) {
    $field_mapping = ARIA_API::aria_competition_field_id_array();
    $title = $entry[strval($field_mapping['competition_name'])];
    $title = str_replace(' ', '_', $title);
    $file_path = ARIA_FILE_UPLOAD_LOC . $title . "_Entry.txt";
    $entry_data = serialize($entry);
    $fp = fopen($file_path, 'w+');
    if ($fp) {
      fwrite($fp, $entry_data);
      fclose($fp);
    }
  }
  */

  /**
   * This function will create new registration forms for students and parents.
   *
   * This function is responsible for creating new registration forms for both
   * students and parents. This function will only create new registration forms
   * for students and parents if it is used ONLY in conjunction with the form
   * used to create new music competitions.
   *
   * @param     Entry Object  $entry  The entry that was just submitted.
   * @param     Form Object   $form   The form used to submit entries.
   * @param     String/Array    $confirmation   The confirmation message to be filtered.
   * @param     Bool    $ajax   Specifies if this form is configured to be submitted via AJAX.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_teacher_and_student_forms($confirmation, $form, $entry, $ajax) {
    // Only perform processing if it's the create competition form
    if (!array_key_exists('isCompetitionCreationForm', $form)
        || !$form['isCompetitionCreationForm']) {
          return $confirmation;
    }

    // check to see if the given competition name has already been used. if it has,
    // throw an error and make the festival chairman remove the old competition or rename
    // the current competition.
    $field_mapping = ARIA_API::aria_competition_field_id_array();
    $competition_name = $entry[$field_mapping['competition_name']];
    $all_forms = GFAPI::get_forms(true, false);
    foreach ($all_forms as $single_form) {
      if (strpos($single_form['title'], $competition_name) !== false) {
        wp_die("<h1>ERROR: A competition with the name '$competition_name' already
            exists. Please remove all of the forms and pages for '$competition_name'
            and try creating the competition again or change the name of
            the competition you're trying to create.</h1>");
      }
    }

    // create the student and teacher (master) forms
    $student_master_form_id = ARIA_Create_Master_Forms::aria_create_student_master_form($competition_name, unserialize($entry[(string) $field_mapping['competition_command_performance_opt']]), $entry[(string) $field_mapping['competition_has_master_class']]);
    $teacher_master_form_id = ARIA_Create_Master_Forms::aria_create_teacher_master_form($competition_name, unserialize($entry[(string) $field_mapping['competition_volunteer_times']]));

    // upload content of the teacher csv file into the teacher master form
    $teacher_csv_file_path = ARIA_API::aria_get_teacher_csv_file_path($entry, $form);
    $teacher_names_and_hashes = ARIA_Teacher::aria_upload_from_csv($teacher_csv_file_path, $teacher_master_form_id);

    // create the student and teacher forms
    $student_form_id = self::aria_create_student_form($entry, $teacher_names_and_hashes, unserialize($entry[(string) $field_mapping['competition_command_performance_opt']]), $entry[(string) $field_mapping['competition_festival_chairman_email']], $entry[(string) $field_mapping['paypal_email']]);
    $teacher_form_id = self::aria_create_teacher_form($entry, unserialize($entry[(string) $field_mapping['competition_volunteer_times']]), $entry[(string) $field_mapping['competition_has_master_class']]);
    $student_form_url = ARIA_API::aria_publish_form("{$competition_name} Student Registration", $student_form_id);
    $teacher_form_url = ARIA_API::aria_publish_form("{$competition_name} Teacher Registration", $teacher_form_id);

    // associate all of the related forms
    $related_forms = array(
      'student_public_form_id' => $student_form_id,
      'teacher_public_form_id' => $teacher_form_id,
      'student_master_form_id' => $student_master_form_id,
      'teacher_master_form_id' => $teacher_master_form_id,
      'student_public_form_url' => $student_form_url,
      'teacher_public_form_url' => $teacher_form_url,
      'festival_chairman_email' => $entry[strval($field_mapping['competition_festival_chairman_email'])]
    );
    if($entry[strval($field_mapping['notification_enabled'])] == 'Yes')
    {
      $related_forms['notification_email'] = $entry[strval($field_mapping['notification_email'])];
    }

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

    // add time limits
    $student_public_form['scheduleForm'] = true;
    $student_public_form['scheduleStart'] = $entry[(string) $field_mapping['competition_student_reg_start']];
    $student_public_form['scheduleStartHour'] = 12;
    $student_public_form['scheduleStartMinute'] = 0;
    $student_public_form['scheduleStartAmpm'] = 'am';

    $student_public_form['scheduleEnd'] = $entry[(string) $field_mapping['competition_student_reg_end']];
    $student_public_form['scheduleEndHour'] = 11;
    $student_public_form['scheduleEndMinute'] = 59;
    $student_public_form['scheduleEndAmpm'] = 'pm';

    $student_public_form['scheduleMessage'] = 'Please be patient as we wait for Festival Registration to open.  The deadline will be extended if necessary to allow every student an opportunity to register.';
    $student_public_form['scheduleMessage'] = 'Please be patient as we wait for Festival Registration to open.  The deadline will be extended if necessary to allow every student an opportunity to register.';

    $teacher_public_form['scheduleForm'] = true;
    $teacher_public_form['scheduleStart'] = $entry[(string) $field_mapping['competition_teacher_reg_start']];
    $teacher_public_form['scheduleStartHour'] = 12;
    $teacher_public_form['scheduleStartMinute'] = 0;
    $teacher_public_form['scheduleStartAmpm'] = 'am';

    $teacher_public_form['scheduleEnd'] = $entry[(string) $field_mapping['competition_teacher_reg_end']];
    $teacher_public_form['scheduleEndHour'] = 11;
    $teacher_public_form['scheduleEndMinute'] = 59;
    $teacher_public_form['scheduleEndAmpm'] = 'pm';

    $teacher_public_form['scheduleMessage'] = 'Please be patient as we wait for Festival Registration to open.  The deadline will be extended if necessary to allow every student an opportunity to register.';
    $teacher_public_form['schedulePendingMessage'] = 'Please be patient as we wait for Festival Registration to open.  The deadline will be extended if necessary to allow every student an opportunity to register.';

    // update the related forms
    GFAPI::update_form($student_public_form);
    GFAPI::update_form($teacher_public_form);
    GFAPI::update_form($student_master_form);
    GFAPI::update_form($teacher_master_form);

    // save the entry object to a file on the server in case it is deleted
    self::aria_save_comp_to_file($entry);

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
    // create the new competition form and generate the field mapping
    $form = new GF_Form(CREATE_COMPETITION_FORM_NAME, "");
    $field_mapping = ARIA_API::aria_competition_field_id_array();

    // description of create competition form
    $form->description = "Welcome! Please submit information for all of the
    fields in the form below in order to create a new NNMTA music festival.";

    // festival chairman email field
    $chairman_email = new GF_Field_Email();
    $chairman_email->label = "Festival Chairman Email";
    $chairman_email->id = $field_mapping['chairman_email'];
    $chairman_email->description = "Please enter your email address. This email
    address will be used in the event where you need to be contacted.";
    $chairman_email->descriptionPlacement = "above";
    $chairman_email->isRequired = true;
    $form->fields[] = $chairman_email;

    // email confirmation field here
    $chairman_email_confirmation = new GF_Field_Email();
    $chairman_email_confirmation->label = "Festival Chairman Email (confirmation)";
    $chairman_email_confirmation->id = $field_mapping['chairman_email_confirmation'];
    $chairman_email_confirmation->description = "This email address must match
    the email address entered in the previous field (Festival Chairman Email).";
    $chairman_email_confirmation->descriptionPlacement = "above";
    $chairman_email_confirmation->isRequired = true;
    $form->fields[] = $chairman_email_confirmation;

    // competition name field
    $name = new GF_Field_Text();
    $name->label = "Competition Name";
    $name->id = $field_mapping['name'];
    $name->isRequired = true;
    $form->fields[] = $name;

    // field for start date of the competition
    $start_date = new GF_Field_Date();
    $start_date->label = "Competition Start Date";
    $start_date->id = $field_mapping['start_date'];
    $start_date->isRequired = true;
    $start_date->calendarIconType = 'calendar';
    $start_date->dateType = 'datedropdown';
    $form->fields[] = $start_date;

    // field for end date of the competition
    $end_date = new GF_Field_Date();
    $end_date->label = "Competition End Date";
    $end_date->id = $field_mapping['end_date'];
    $end_date->isRequired = true;
    $end_date->calendarIconType = 'calendar';
    $end_date->dateType = 'datedropdown';
    $form->fields[] = $end_date;

    // main location field
    $main_location = new GF_Field_Address();
    $main_location->label = "Competition Location";
    $main_location->id = $field_mapping['main_location'];
    $main_location->isRequired = true;
    $main_location = self::aria_add_default_address_inputs($main_location);
    $form->fields[] = $main_location;

    // second location field
    $second_location = new GF_Field_Address();
    $second_location->label = "Secondary Competition Location";
    $second_location->id = $field_mapping['second_location'];
    $second_location->isRequired = false;
    $second_location->description = "Use this field to enter a secondary
    competition location (if the venue for the competition changes from day
    to day).";
    $second_location->descriptionPlacement = 'above';
    $second_location = self::aria_add_default_address_inputs($second_location);
    $form->fields[] = $second_location;

    // field for student registration begin date
    $student_registration_start = new GF_Field_Date();
    $student_registration_start->label = "Student Registration Start Date";
    $student_registration_start->id = $field_mapping['student_registration_start'];
    $student_registration_start->isRequired = true;
    $student_registration_start->description = "The date entered here will be
    when the student registration form becomes available to the public.";
    $student_registration_start->descriptionPlacement = "above";
    $student_registration_start->calendarIconType = 'calendar';
    $student_registration_start->dateType = 'datedropdown';
    $form->fields[] = $student_registration_start;

    // field for student registration deadline
    $student_registration_end = new GF_Field_Date();
    $student_registration_end->label = "Student Registration End Date";
    $student_registration_end->id = $field_mapping['student_registration_end'];
    $student_registration_end->isRequired = true;
    $student_registration_end->description = "The date entered here will be
    when the student registration form becomes unavailable to the public.";
    $student_registration_end->descriptionPlacement = "above";
    $student_registration_end->calendarIconType = 'calendar';
    $student_registration_end->dateType = 'datedropdown';
    $form->fields[] = $student_registration_end;

    // field for teacher registration begin date
    $teacher_registration_start = new GF_Field_Date();
    $teacher_registration_start->label = "Teacher Registration Start Date";
    $teacher_registration_start->id = $field_mapping['teacher_registration_start'];
    $teacher_registration_start->isRequired = true;
    $teacher_registration_start->description = "The date entered here will be
    when the teacher registration form becomes available to the teachers.";
    $teacher_registration_start->descriptionPlacement = "above";
    $teacher_registration_start->calendarIconType = 'calendar';
    $teacher_registration_start->dateType = 'datedropdown';
    $form->fields[] = $teacher_registration_start;

    // field for teacher registration deadline
    $teacher_registration_end = new GF_Field_Date();
    $teacher_registration_end->label = "Teacher Registration End Date";
    $teacher_registration_end->id = $field_mapping['teacher_registration_end'];
    $teacher_registration_end->isRequired = true;
    $teacher_registration_end->description = "The date entered here will be
    when the teacher registration form becomes unavailable to the teachers.";
    $teacher_registration_end->descriptionPlacement = "above";
    $teacher_registration_end->calendarIconType = 'calendar';
    $teacher_registration_end->dateType = 'datedropdown';
    $form->fields[] = $teacher_registration_end;

    // teacher volunteer options field

    // teacher volunteer time options field
    $teacher_volunteer_times_field = new GF_Field_List();
    $teacher_volunteer_times_field->label = "Volunteer Time Options for Teachers";
    $teacher_volunteer_times_field->id = $field_mapping['competition_volunteer_times'];
    $teacher_volunteer_times_field->isRequired = true;
    $teacher_volunteer_times_field->description = "Enter at least two times for teachers to volunteer ";
    $teacher_volunteer_times_field->description .= "e.g. Saturday (10am-4pm), Sunday night, etc.";
    $teacher_volunteer_times_field->descriptionPlacement = 'above';

    // teacher csv file upload field
    $teacher_csv_file_upload_field = new GF_Field_FileUpload();
    $teacher_csv_file_upload_field->label = CSV_TEACHER_FIELD_NAME;
    $teacher_csv_file_upload_field->id = $field_mapping['competition_teacher_csv_upload'];
    $teacher_csv_file_upload_field->isRequired = true;
    $teacher_csv_file_upload_field->description = "Browse your computer for a CSV
    file of teachers that will be participating in this music competition. If
    a teacher decides that he/she wants to participate in this competition later,
    you will have the opportunity to add more teachers using the 'ARIA: Add Teacher'
    page (located in the 'Pages' section of the WordPress dashboard). <b>Please
    note that the CSV file should be in the following format: First Name, Last Name,
    Phone, Email</b>";
    $teacher_csv_file_upload_field->descriptionPlacement = 'above';

    // command performance options field
    $command_performance_option_field = new GF_Field_List();
    $command_performance_option_field->label = "Command Performance Time Options For Students";
    $command_performance_option_field->id = $field_mapping['competition_command_performance_opt'];
    $command_performance_option_field->isRequired = true;
    $command_performance_option_field->description = "These are the options that will
    be shown to the students when registering (e.g. Thursday at 5:30pm, 7PM on Jan 1, etc.).";
    $command_performance_option_field->descriptionPlacement = 'above';

    // master class registration option field
    $has_master_class = new GF_Field_Radio();
    $has_master_class->label = "Master Class Sections?";
    $has_master_class->id = $field_mapping['competition_has_master_class'];
    $has_master_class->isRequired = true;
    $has_master_class->description = "Should students be allowed to register
    for a master class section in this competition?";
    $has_master_class->choices = array(
        array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
        array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );

    // field for enabling email notifications to festival chairman
    $notification_field = new GF_Field_Radio();
    $notification_field->label = "Would you like to be notified when students register?";
    $notification_field->id = $field_mapping['notification_enabled'];
    $notification_field->isRequired = true;
    $notification_field->choices = array(
        array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
        array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );

    // notification email field
    $notification_email_field = new GF_Field_Email();
    $notification_email_field->label = "Notification Email";
    $notification_email_field->id = $field_mapping['notification_email'];
    $notification_email_field->description = "Please enter the email address you
    would like notificiation emails to be sent to.";
    $notification_email_field->descriptionPlacement = "above";
    $notification_email_field->isRequired = true;
    $conditionalRules = array();
    $conditionalRules[] = array(
      'fieldId' => $field_mapping['notification_enabled'],
      'operator' => 'is',
      'value' => 'Yes'
    );
    $notification_email_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $conditionalRules
    );

    // add a section break and begin pricing
    $section_break = new GF_Field_Section();
    $section_break->label = "Pricing";
    $section_break->description = "Enter prices only for levels eligible to
    participate in this competition.";

    // PayPal email field
    $paypal_email_field = new GF_Field_Email();
    $paypal_email_field->label = "Paypal Account Email";
    $paypal_email_field->id = $field_mapping['paypal_email'];
    $paypal_email_field->description = "Please enter the email address associated
    with your PayPal account. Please make sure this PayPal is setup according to
    the Gravity Forms PayPal Add-On directions.";
    $paypal_email_field->descriptionPlacement = "above";
    $paypal_email_field->isRequired = true;


    // level pricing field
    $pricing = array();
    for ($i = 1; $i <= 11; $i++) {
      $level_price = new GF_Field_Number();
      $level_price->label = "Price for Level " . $i . " Student";
      $level_price->id = $field_mapping['level_' . $i . '_price'];
      $level_price->defaultValue = '0.00';
      $level_price->size = 'small';
      $level_price->isRequired = false;
      $level_price->numberFormat = 'currency';
      $pricing[] = $level_price;
      unset($level_price);
    }

    // assign all of the previous attributes to our newly created form
    $form->fields[] = $fc_email_field;
    $form->fields[] = $name_field;
    $form->fields[] = $start_date_field;
    $form->fields[] = $end_date_field;
    $form->fields[] = $location_field;
    $form->fields[] = $location_field_2;
    $form->fields[] = $student_registration_start_date_field;
    $form->fields[] = $student_registration_end_date_field;
    $form->fields[] = $teacher_registration_start_date_field;
    $form->fields[] = $teacher_registration_end_date_field;
    $form->fields[] = $teacher_volunteer_times_field;
    $form->fields[] = $teacher_csv_file_upload_field;
    $form->fields[] = $command_performance_option_field;
    $form->fields[] = $theory_score_field;
    $form->fields[] = $has_master_class;
    $form->fields[] = $notification_field;
    $form->fields[] = $notification_email_field;
    $form->fields[] = $section_break;
    $form->fields[] = $paypal_email_field;
    $form->fields = array_merge($form->fields, $pricing);
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = 'Successful';

    // identify form as necessary
    $form_array = $form->createFormArray();
    $form_array['isTeacherUploadForm'] = true;
    $form_array['isCompetitionCreationForm'] = true;

    // add form to dashboard
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
  * @param  $field  Field Object  The name of field used for addressing.
  *
  * @since 1.0.0
  * @author KREW
  */
  private static function aria_add_default_address_inputs($field) {
    $field->inputs = array(
      array("id" => "{$field->id}.1",
      			"label" => "street_address",
      			"name" => ""),
      array("id" => "{$field->id}.2",
      			"label" => "address_line_2",
      			"name" => ""),
      array("id" => "{$field->id}.3",
      			"label" => "city",
      			"name" => ""),
      array("id" => "{$field->id}.4",
      			"label" => "state_province_region",
      			"name" => ""),
      array("id" => "{$field->id}.5",
      			"label" => "zip_postal_code",
      			"name" => ""),
      array("id" => "{$field->id}.6",
      			"label" => "country",
      			"name" => ""),
    );

    return $field;
  }

  /**
   * This function will take a name field and add default (initialized) attributes
   * to it.
   *
   * In order for a name field to be properly displayed on a form, it needs to be
   * initialized with some default values. This function is responsible for taking
   * a name field as input and providing initialized values for that name field.
   *
   * @param   Field Object  $field  The field used for name input.
   *
   * @since 1.0.0
   * @author KREW
   */
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
   * This function will take a checkbox field and initialize it.
   *
   * In order for a checkbox field to be properly displayed on a form, it needs to be
   * initialized. This function is responsible for taking a checkbox field as input
   * and providing initialized state for that checkbox field.
   *
   * @param   Field Object  $field  The field used for checkbox input.
   *
   * @since 1.0.0
   * @author KREW
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
   private static function aria_create_teacher_form($competition_entry, $volunteer_time_options_array, $has_master_class) {
    $field_mapping = ARIA_API::aria_competition_field_id_array();

    $competition_name = $competition_entry[$field_mapping['competition_name']];
    $teacher_form = new GF_Form("{$competition_name} Teacher Registration", "");
    $field_id_arr = ARIA_API::aria_teacher_field_id_array();
    $ariaFieldIds = array();

    // teacher name
    $teacher_name_field = new GF_Field_Name();
    $teacher_name_field->label = "Name";
    $teacher_name_field->id = $field_id_arr['name'];
    $teacher_name_field->isRequired = true;
    $teacher_name_field = self::aria_add_default_name_inputs($teacher_name_field);
    $teacher_form->fields[] = $teacher_name_field;
    $ariaFieldIds['name'] = $teacher_name_field->id;

    // teacher email
    $teacher_email_field = new GF_Field_Email();
    $teacher_email_field->label = "Email";
    $teacher_email_field->id = $field_id_arr['email'];
    $teacher_email_field->isRequired = true;
    $teacher_form->fields[] = $teacher_email_field;
    $ariaFieldIds['email'] = $teacher_email_field->id;

    // teacher phone
    $teacher_phone_field = new GF_Field_Phone();
    $teacher_phone_field->label = "Phone";
    $teacher_phone_field->id = $field_id_arr['phone'];
    $teacher_phone_field->isRequired = true;
    $teacher_form->fields[] = $teacher_phone_field;
    $ariaFieldIds['phone'] = $teacher_phone_field->id;

    // teacher is judging
    $teacher_judging_field = new GF_Field_Radio();
    $teacher_judging_field->label = "Are you scheduled to judge for the festival?";
    $teacher_judging_field->id = $field_id_arr['is_judging'];
    $teacher_judging_field->isRequired = true;
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
    $ariaFieldIds['is_judging'] = $teacher_judging_field->id;

    // teacher volunteer preference
    $volunteer_preference_field = new GF_Field_Checkbox();
    $volunteer_preference_field->label = "Volunteer Preference";
    $volunteer_preference_field->id = $field_id_arr['volunteer_preference'];
    $volunteer_preference_field->isRequired = true;
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
    $volunteer_preference_field->inputs = array();
    $volunteer_preference_field = self::aria_add_checkbox_input( $volunteer_preference_field, $volunteer_inputs );
    $volunteer_preference_field->description = "Please check at least two volunteer job"
    ." preferences for this year's event. You will be notified by email of your"
    ." volunteer assignments as the event approaches.";
    $volunteer_preference_field->descriptionPlacement = 'above';
    $volunteer_preference_field->conditionalLogic = array(
	'actionType' => 'show',
	'logicType' => 'all',
	'rules' => $conditionalRules
    );
    $teacher_form->fields[] = $volunteer_preference_field;
    $ariaFieldIds['volunteer_preference'] = $volunteer_preference_field->id;
    for ($i=1; $i <= count($volunteer_preference_field->inputs); $i++) {
      $ariaFieldIds["volunteer_preference_option_{$i}"] = "{$volunteer_preference_field->id}.{$i}";
    }

    // volunteer time
    $volunteer_time_field = new GF_Field_Checkbox();
    $volunteer_time_field->label = "Times Available for Volunteering";
    $volunteer_time_field->id = $field_id_arr['volunteer_time'];
    $volunteer_time_field->isRequired = true;
    $volunteer_time_field->description = "Please check at least two times you are"
    ." available to volunteer during Festival weekend.";
    $volunteer_time_field->descriptionPlacement = 'above';
    $volunteer_time_field->choices = array();
    $volunteer_time_field->inputs = array();
    if (is_array($volunteer_time_options_array)) {
      $index = 1;
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
    $ariaFieldIds['volunteer_time'] = $volunteer_time_field->id;
    for ($i=1; $i <= count($volunteer_preference_field->inputs); $i++) {
      $ariaFieldIds["volunteer_time_option_{$i}"] = "{$volunteer_time_field->id}.{$i}";
    }

    // teacher is judging
    $volunteer_with_students = new GF_Field_Radio();
    $volunteer_with_students->label = "Volunteer in student's section";
    $volunteer_with_students->description = "Do you wish to be scheduled as a proctor or door";
    $volunteer_with_students->description .= " monitor for a session in which one of your";
    $volunteer_with_students->description .= " own students is playing?";
    $volunteer_with_students->descriptionPlacement = 'above';
    $volunteer_with_students->id = $field_id_arr['schedule_with_students'];
    $volunteer_with_students->isRequired = true;
    $volunteer_with_students->choices = array(
      array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
      array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $volunteer_with_students->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $conditionalRules
    );
    $teacher_form->fields[] = $volunteer_with_students;
    $ariaFieldIds['schedule_with_students'] = $volunteer_with_students->id;


    // student name
    $student_name_field = new GF_Field_Name();
    $student_name_field->label = "Student Name";
    $student_name_field->id = $field_id_arr['student_name'];
    $student_name_field->isRequired = false;
    $student_name_field = self::aria_add_default_name_inputs($student_name_field);
    $teacher_form->fields[] = $student_name_field;
    $ariaFieldIds['student_name'] = $student_name_field->id;
    $ariaFieldIds['student_first_name'] = "{$student_name_field->id}.3";
    $ariaFieldIds['student_last_name'] = "{$student_name_field->id}.6";


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
    $ariaFieldIds['student_level'] = $student_level_field->id;


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
    $song_one_period_field->isRequired = true;
    $song_one_period_field->placeholder = "Select Period...";
    $teacher_form->fields[] = $song_one_period_field;
    $ariaFieldIds['song_one_period'] = $song_one_period_field->id;

    // student's first song composer
    $song_one_composer_field = new GF_Field_Select();
    $song_one_composer_field->label = "Song 1 Composer";
    $song_one_composer_field->id = $field_id_arr['song_1_composer'];
    $song_one_composer_field->isRequired = true;
    $teacher_form->fields[] = $song_one_composer_field;
    $ariaFieldIds['song_one_composer'] = $song_one_composer_field->id;

    // student's first song selection
    $song_one_selection_field = new GF_Field_Select();
    $song_one_selection_field->label = "Song 1 Selection";
    $song_one_selection_field->id = $field_id_arr['song_1_selection'];
    $song_one_selection_field->isRequired = true;
    $teacher_form->fields[] = $song_one_selection_field;
    $ariaFieldIds['song_one_selection'] = $song_one_selection_field->id;

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
    $song_two_period_field->isRequired = true;
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
    $song_two_period_field->placeholder = "Select Period...";
    $teacher_form->fields[] = $song_two_period_field;
    $ariaFieldIds['song_two_period'] = $song_two_period_field->id;

    // student's second song composer
    $song_two_composer_field = new GF_Field_Select();
    $song_two_composer_field->label = "Song 2 Composer";
    $song_two_composer_field->id = $field_id_arr['song_2_composer'];
    $song_two_composer_field->isRequired = true;
    $song_two_composer_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );
    $teacher_form->fields[] = $song_two_composer_field;
    $ariaFieldIds['song_two_composer'] = $song_two_composer_field->id;

    // student's second song selection
    $song_two_selection_field = new GF_Field_Select();
    $song_two_selection_field->label = "Song 2 Selection";
    $song_two_selection_field->id = $field_id_arr['song_2_selection'];
    $song_two_selection_field->isRequired = true;
    $song_two_selection_field->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );
    $teacher_form->fields[] = $song_two_selection_field;
    $ariaFieldIds['song_two_selection'] = $song_two_selection_field->id;

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
    $ariaFieldIds['alt_song_two_composer'] = $alt_song_two_composer_field->id;

    // Piece Title
    $alt_song_two_selection_field = new GF_Field_Text();
    $alt_song_two_selection_field->label = "Song 2 Piece Title";
    $alt_song_two_selection_field->id = $field_id_arr['alt_song_2_selection'];
    $alt_song_two_selection_field->isRequired = true;
    $alt_song_two_selection_field->description = "Please be as descriptive as possible.";
    $alt_song_two_selection_field->description .= "If applicable, include key (D Major, F Minor, etc.), ";
    $alt_song_two_selection_field->description .= "movement number (1st, 2nd, etc.), ";
    $alt_song_two_selection_field->description .= "movement description (Adante, Rondo Allegro Comodo, etc.), ";
    $alt_song_two_selection_field->description .= "identifying number (BWV, Opus, etc.).";
    $alt_song_two_selection_field->descriptionPlacement = 'above';
    $alt_song_two_selection_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $is_11_rule
    );
    $teacher_form->fields[] = $alt_song_two_selection_field;
    $ariaFieldIds['alt_song_two_selection'] = $alt_song_two_selection_field->id;

    // student's theory score
    $student_theory_score = new GF_Field_Number();
    $student_theory_score->label = "Theory Score (percentage)";
    $student_theory_score->id = $field_id_arr['theory_score'];
    $student_theory_score->isRequired = true;
    $student_theory_score->numberFormat = "decimal_dot";
    $student_theory_score->rangeMin = 70;
    $student_theory_score->rangeMax = 100;
    $teacher_form->fields[] = $student_theory_score;
    $ariaFieldIds['theory_score'] = $student_theory_score->id;

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
    $alternate_theory_field->inputs = array();
    $alternate_theory_field = self::aria_add_checkbox_input( $alternate_theory_field, 'Alternate theory exam completed' );
    $teacher_form->fields[] = $alternate_theory_field;
    $ariaFieldIds['alternate_theory'] = $alternate_theory_field->id;

    // competition format
    $competition_format_field = new GF_Field_Radio();
    $competition_format_field->label = "Format of Event";
    $competition_format_field->id = $field_id_arr['competition_format'];
    $competition_format_field->isRequired = true;
    $competition_format_field->choices = array(
      array('text' => 'Traditional', 'value' => 'Traditional', 'isSelected' => false),
      array('text' => 'Non-Competitive', 'value' => 'Non-Competitive', 'isSelected' => false)
    );
    if( $has_master_class == "Yes" )
    {
        $competition_format_field->choices[] = array('text' => 'Master Class', 'value' => 'Master Class', 'isSelected' => false);
    }
    $teacher_form->fields[] = $competition_format_field;
    $ariaFieldIds['competition_format'] = $competition_format_field->id;

    // timing field

    $timing_of_pieces_field = new GF_Field_Select();
    $timing_of_pieces_field->label = "Combined timing of Pieces (minutes)";
    $timing_of_pieces_field->description = "Please round up to the nearest minute.";
    $timing_of_pieces_field->descriptionPlacement = "above.";
    $timing_of_pieces_field->id = $field_id_arr['timing_of_pieces'];
    $timing_of_pieces_field->isRequired = true;
    $timing_choices = array();
    for ($i = 1; $i <= 20; $i++) {
      $single_choice = array(
        'text' => strval($i),
        'value' => strval($i),
        'isSelected' => false
      );
      $timing_choices[] = $single_choice;
    }
    $timing_of_pieces_field->choices = $timing_choices;
    $teacher_form->fields[] = $timing_of_pieces_field;
    $ariaFieldIds['timing_of_pieces'] = $timing_of_pieces_field->id;

    // custom submission message to let the festival chairman know the creation was
    // a success
  /*  $successful_submission_message = 'Congratulations! You have just successfully registered';
    $successful_submission_message .= ' one your students.';
    $competition_creation_form->confirmation['type'] = 'message';
    $competition_creation_form->confirmation['message'] = $successful_submission_message;
*/
    $successful_submission_message = 'Congratulations! You have just';
    $successful_submission_message .= ' successfully registered your student.';
    $teacher_form->confirmation['type'] = 'message';
    $teacher_form->confirmation['message'] = $successful_submission_message;

    $teacher_form_array = $teacher_form->createFormArray();
    $teacher_form_array['isTeacherPublicForm'] = true;
    $teacher_form_array['ariaFieldIds'] = $ariaFieldIds;
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
   * @param   $competition_entry  Entry Object  The entry of the newly created music competition.
   * @param   $teacher_names_and_hashes   Array   The array of teacher names in this competition.
   * @param   $command_options_array  Array   The array of command performance options.
   * @param   $competition_festival_chairman_email  String  The email of the festival chairman.
   * @param   $paypal_email   String  The email used to link to the paypal account.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_create_student_form($competition_entry,
                                                   $teacher_names_and_hashes,
                                                   $command_options_array,
                                                   $competition_festival_chairman_email,
                                                   $paypal_email) {
    // obtain the field mapping arrays for both competition creation and student registration
    $create_comp_field_mapping = ARIA_API::aria_competition_field_id_array();
    $student_field_mapping = ARIA_API::aria_student_field_id_array();

    // obtain the name of the competition and initialize a new form
    $competition_name = $competition_entry[$create_comp_field_mapping['competition_name']];
    $student_form = new GF_Form("{$competition_name} Student Registration", "");

    // add a description to the student form
    $student_form->description = "Welcome! Use this form to submit your child's
    information for the upcoming NNMTA festival. Once this form has been submitted,
    your child's teacher will receive an email with a link that they will use to
    complete the registration process. Within a few weeks, you will receive an
    email stating when your child has been registered to perform.";

    // create a designated array to hold the field id's of the fields in the student registration form
    $ariaFieldIds = array();

    // create the parent name field
    $parent_name = new GF_Field_Name();
    $parent_name->label = "Parent Name";
    $parent_name->id = $student_field_mapping['parent_name'];
    $parent_name->isRequired = true;
    $parent_name = self::aria_add_default_name_inputs($parent_name);
    $student_form->fields[] = $parent_name;

    // store the parent name field in array of field id's
    $ariaFieldIds['parent_name'] = $parent_name->id;
    $ariaFieldIds['parent_first_name'] = "{$parent_name->id}.3";
    $ariaFieldIds['parent_last_name'] = "{$parent_name->id}.6";

    // create the parent email field
    $parent_email = new GF_Field_Email();
    $parent_email->label = "Parent Email";
    $parent_email->id = $student_field_mapping['parent_email'];
    $parent_email->isRequired = true;
    $student_form->fields[] = $parent_email;

    // store the parent email field in array of field id's
    $ariaFieldIds['parent_email'] = $parent_email->id;

    // create the parent email confirmation field
    $parent_email_confirmation = new GF_Field_Email();
    $parent_email_confirmation->label = "Parent Email (confirmation)";
    $parent_email_confirmation->id = $student_field_mapping['parent_email_confirmation'];
    $parent_email_confirmation->description = "The email you enter here must match
    the email that was entered in the previous box (Parent Email).";
    $parent_email_confirmation->descriptionPlacement = 'above';
    $parent_email_confirmation->isRequired = true;
    $student_form->fields[] = $parent_email_confirmation;

    // store the parent email confirmation field in array of field id's
    $ariaFieldIds['parent_email_confirmation'] = $parent_email_confirmation->id;

    // create the student name field
    $student_name = new GF_Field_Name();
    $student_name->label = "Student Name";
    $student_name->id = $student_field_mapping['student_name'];
    $student_name->description = "Please enter your child's name here using
    appropriate capitalization. The text you submit here will be used on all
    competition documents and awards.";
    $student_name->descriptionPlacement = 'above';
    $student_name->isRequired = true;
    $student_name = self::aria_add_default_name_inputs($student_name);
    $student_form->fields[] = $student_name;

    // store the student name field in array of field id's
    $ariaFieldIds['student_name'] = $student_name->id;
    $ariaFieldIds['student_first_name'] = "{$student_name->id}.3";
    $ariaFieldIds['student_last_name'] = "{$student_name->id}.6";

    // create the student birthday field
    $student_birthday = new GF_Field_Date();
    $student_birthday->label = "Student Birthday";
    $student_birthday->id = $student_field_mapping['student_birthday'];
    $student_birthday->isRequired = true;
    $student_birthday->calendarIconType = 'calendar';
    $student_birthday->dateType = 'datedropdown';
    $student_form->fields[] = $student_birthday;

    // store the student birthday field in array of field id's
    $ariaFieldIds['student_birthday'] = $student_birthday->id;

    // create the student's piano teacher field
    $teacher_name = new GF_Field_Select();
    $teacher_name->label = "Teacher Name";
    $teacher_name->id = $student_field_mapping['teacher_name'];
    $teacher_name->isRequired = true;
    $teacher_name->description = "Please select your teacher's name from the
    drop-down below. If your teacher is not listed, please contact the festival
    chairman at $competition_festival_chairman_email.";
    $teacher_name->descriptionPlacement = 'above';
    $teacher_name->placeholder = "Select teacher from below..";

    // alphabetize teachers
    usort($teacher_names_and_hashes, function($a, $b) {
        return strcmp($a[1], $b[1]);
    });

    // add all of the piano teachers that are competing in this competition to an array
    $formatted_teacher_names = array();
    foreach ($teacher_names_and_hashes as $key => $value) {
      $single_teacher = array(
        'text' => $value[0] . ' ' . $value[1],
        'value' => serialize($value),
        'isSelected' => false
      );
      $formatted_teacher_names[] = $single_teacher;
      unset($single_teacher);
    }

    $teacher_name->choices = $formatted_teacher_names;
    $student_form->fields[] = $teacher_name;

    // store the teacher name field in array of field id's
    $ariaFieldIds['teacher_name'] = $teacher_name->id;

    // create student's available times to compete field
    $available_festival_days = new GF_Field_Radio();
    $available_festival_days->label = "Available Festival Days";
    $available_festival_days->id = $student_field_mapping['available_festival_days'];
    $available_festival_days->isRequired = true;
    $available_festival_days->description = "There is no guarantee that scheduling
    requests will be honored.";
    $available_festival_days->descriptionPlacement = 'above';
    $available_festival_days->choices = array(
      array('text' => 'Either Saturday or Sunday', 'value' => 'Either Saturday or Sunday', 'isSelected' => false),
      array('text' => 'Saturday', 'value' => 'Saturday', 'isSelected' => false),
      array('text' => 'Sunday', 'value' => 'Sunday', 'isSelected' => false)
    );
    $student_form->fields[] = $available_festival_days;

    // store the available festival days in array of field id's
    $ariaFieldIds['available_festival_days'] = $available_festival_days->id;
    for ($i = 1; $i <= count($available_festival_days->inputs); $i++) {
      $ariaFieldIds["available_festival_days_option_{$i}"] = "{$available_festival_days->id}.{$i}";
    }

    // create student's preferred command performance field
    $preferred_command_performance = new GF_Field_Radio();
    $preferred_command_performance->label = "Preferred Command Performance Time";
    $preferred_command_performance->id = $student_field_mapping['preferred_command_performance'];
    $preferred_command_performance->isRequired = true;
    $preferred_command_performance->description = "If your child receives either a
    Superior with Distinction or Superior rating from festival, he/she is elligible
    to compete in the command performance. Please select your preferred command
    performance time below.";
    $preferred_command_performance->descriptionPlacement = 'above';
    $preferred_command_performance->choices = array();
    $preferred_command_performance->choices[] = array('text' => 'Any time',
                                                      'value' => 'Any time',
                                                      'isSelected' => false);

    // add the command performance times that were input by the festival chairman
    if (is_array($command_options_array)) {
      $index = 1;
      foreach ($command_options_array as $command_time) {
        $preferred_command_performance->choices[] = array('text' => $command_time,
                                                          'value' => $command_time,
                                                          'isSelected' => false);
      }
    }

    $student_form->fields[] = $preferred_command_performance;
    for ($i=1; $i <= count($preferred_command_performance->inputs); $i++) {
      $ariaFieldIds["preferred_command_performance_option_{$i}"] = "{$preferred_command_performance->id}.{$i}";
    }

    // store the preferred command performance in array of field id's
    $ariaFieldIds['preferred_command_performance'] = $preferred_command_performance->id;

    // student's festival level
    /*
    $student_level_field = new GF_Field_Select();
    $student_level_field->label = "Student Level";
    $student_level_field->id = $student_field_mapping['student_level'];
    $student_level_field->isRequired = false;
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
    $student_level_field->description = "Please enter your student's festival level.";
    $student_level_field->description .= " If you do not know this value, please do";
    $student_level_field->description .= " not submit this form until your child";
    $student_level_field->description .= " contacts his/her instructor and can verify";
    $student_level_field->description .= " this value.";
    $student_level_field->descriptionPlacement = 'above';
    $student_level_field->hidden = true;
    $student_form->fields[] = $student_level_field;
    $ariaFieldIds['student_level'] = $student_level_field->id;
    */

    // create the student level field
    $student_level = new GF_Field_Product();
    $student_level->label = "Student Level";
    $student_level->id = $student_field_mapping['student_level'];
    $student_level->isRequired = true;
    $student_level->size = "small";
    $student_level->inputs = null;
    $student_level->inputType = "select";
    $student_level->enablePrice = true;
    $student_level->basePrice = "$1.00";
    $student_level->disableQuantity = true;
    $student_level->displayAllCategories = false;
    $student_level->description = "Please enter your child's festival level.
    If you do not know this value, please do not submit this form until your child
    contacts his/her instructor and can verify this value.";
    $student_level->descriptionPlacement = 'above';

    // add the prices to the student level field
    $student_level->choices = array();
    for ($i = 1; $i <= 11; $i++) {
      $price = $competition_entry[$create_comp_field_mapping['level_'. $i .'_price']];
      if($price != 0) {
        $student_level->choices[] = array('text' => (string)$i,
                                          'value' => (string)$i,
                                          'isSelected' => false,
                                          'price' => $price);
      }
    }
    $student_form->fields[] = $student_level;

    // store the student's level in array of field id's
    $ariaFieldIds['student_level'] = $student_level->id;

    // create the compliance field checkbox for parents
    $compliance_statement = new GF_Field_checkbox();
    $compliance_statement->label = "Compliance Statement";
    $compliance_statement->id = $student_field_mapping['compliance_statement'];
    $compliance_statement->isRequired = true;
    $compliance_statement->description = "As a parent, I understand and agree to
    comply with all rules, regulations, and amendments as stated in the
    Festival syllabus. I am in full compliance with the laws regarding
    photocopies and can provide verification of authentication of any legally
    printed music. I understand that adjudicator decisions are final and
    will not be contested. I know that small children may not remain in the
    room during performances of non-family members. I understand that
    requests for specific days/times will be scheduled if possible but cannot
    be guaranteed.";
    $compliance_statement->descriptionPlacement = 'above';
    $compliance_statement->choices = array(
      array('text' => 'I have read and agree with the above statement.',
            'value' => 'Agree',
            'isSelected' => false
      ),
    );
    $compliance_statement->inputs = array();
    $compliance_statement = self::aria_add_checkbox_input($compliance_statement,
                                                          'I have read and agree with the following statement:');
    $student_form->fields[] = $compliance_statement;

    // store the compliance statement in array of field id's
    $ariaFieldIds['compliance_statement'] = $compliance_statement->id;
    for ($i = 1; $i <= count($compliance_statement->inputs); $i++) {
      $ariaFieldIds["compliance_statement_option_{$i}"] = "{$compliance_statement->id}.{$i}";
    }

    // create the total pricing field (dependent on student's level)
    $registration_total = new GF_Field_Total();
    $registration_total->label = "Total Registration Cost";
    $registration_total->id = $student_field_mapping['registration_total'];
    $registration_total->isRequired = false;
    $student_form->fields[] = $registration_total;

    // store the registration total in array of field id's
    $ariaFieldIds['registration_total'] = $registration_total->id;

    // custom submission message to let the parent know registration was a success
    $successful_submission_message = "Congratulations! You have just successfully
    registered your child.";
    $student_form->confirmation['type'] = 'message';
    $student_form->confirmation['message'] = $successful_submission_message;

    // create the student form and add it to the WP dashboard
    $student_form_arr = $student_form->createFormArray();
    $student_form_arr['isStudentPublicForm'] = true;
    $student_form_arr['ariaFieldIds'] = $ariaFieldIds;
    $new_form_id = GFAPI::add_form($student_form_arr);
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }

    // create feed for payment (PayPal)
    $feed_meta = array(
      'feedName' => 'Student Registration Feed',
      'paypalEmail' => $paypal_email,
      'mode' => 'production',
      'transactionType' => 'product',
      'paymentAmount' => 'form_total',
      'disableShipping' => 1,
      'disableNote' => 0,
      'type' => 'product'
    );
    $feed_slug = 'gravityformspaypal';
    $new_feed_id = GFAPI::add_feed( $new_form_id, $feed_meta, $feed_slug);
    if (is_wp_error($new_feed_id)) {
      wp_die($new_feed_id->get_error_message());
    }

    // pass back the newly created student form id to the caller
    return $new_form_id;
  }
}
