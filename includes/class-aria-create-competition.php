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
   * This function is responsible for creating and adding all of the
   * associated fields that are necessary for the festival chairman to
   * create new music competitions.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_create_competition_form() {
    // create the new competition form and generate the field mappings
    $form = new GF_Form(CREATE_COMPETITION_FORM_NAME, "");
    $field_mappings = ARIA_API::aria_competition_field_id_array();

    // description
    $form->description = 'Welcome! Please submit information for all of the';
    $form->description .= ' fields in the form below in order to create a new';
    $form->description .= '  NNMTA music competition.';

    // Festival Chairman Email
    $fc_email_field = new GF_Field_Email();
    $fc_email_field->label = "Festival Chairman's Email";
    $fc_email_field->id = $field_mappings['competition_festival_chairman_email'];
    $fc_email_field->description = "Please enter your email address. This address";
    $fc_email_field->description .= " will be used in the event you need to be";
    $fc_email_field->description .= " contacted.";
    $fc_email_field->descriptionPlacement = "above";
    $fc_email_field->isRequired = true;

    // Notifications enabled
    $notification_field = new GF_Field_Radio();
    $notification_field->label = "Would you like to be notified when students register?";
    $notification_field->id = $field_mappings['notification_enabled'];
    $notification_field->isRequired = true;
    $notification_field->choices = array(
        array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
        array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );

    // Notifications Email
    $notification_email_field = new GF_Field_Email();
    $notification_email_field->label = "Notification Email";
    $notification_email_field->id = $field_mappings['notification_email'];
    $notification_email_field->description = "Please enter the email address you would like";
    $notification_email_field->description .= " notificiation emails to be sent to.";
    $notification_email_field->descriptionPlacement = "above";
    $notification_email_field->isRequired = true;
    $conditionalRules = array();
    $conditionalRules[] = array(
      'fieldId' => $field_mappings['notification_enabled'],
      'operator' => 'is',
      'value' => 'Yes'
    );
    $notification_email_field->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $conditionalRules
    );

    // name
    $name_field = new GF_Field_Text();
    $name_field->label = "Competition Name";
    $name_field->id = $field_mappings['competition_name'];
    $name_field->isRequired = true;

    // start date of the competition
    $start_date_field = new GF_Field_Date();
    $start_date_field->label = "Competition Start Date";
    $start_date_field->id = $field_mappings['competition_start_date'];
    $start_date_field->isRequired = true;
    $start_date_field->calendarIconType = 'calendar';
    $start_date_field->dateType = 'datepicker';

    // end date of the competition
    $end_date_field = new GF_Field_Date();
    $end_date_field->label = "Competition End Date";
    $end_date_field->id = $field_mappings['competition_end_date'];
    $end_date_field->isRequired = true;
    $end_date_field->calendarIconType = 'calendar';
    $end_date_field->dateType = 'datepicker';

    // location
    $location_field = new GF_Field_Address();
    $location_field->label = "Competition Location";
    $location_field->id = $field_mappings['competition_location'];
    $location_field->isRequired = true;
    $location_field = self::aria_add_default_address_inputs($location_field);

    // second location
    $location_field_2 = new GF_Field_Address();

    $location_field_2->label = "Sunday Competition Location (If different from above)";
    $location_field_2->id = $field_mappings['competition_2_address'];
    $location_field_2->isRequired = false;
    $location_field_2->description = 'If different location for second day.';
    $location_field_2->descriptionPlacement = 'above';
    $location_field_2 = self::aria_add_default_address_inputs($location_field_2);


    // student registration begin date
    $student_registration_start_date_field = new GF_Field_Date();
    $student_registration_start_date_field->label = "Student Registration Start Date";
    $student_registration_start_date_field->id = $field_mappings['competition_student_reg_start'];
    $student_registration_start_date_field->isRequired = true;
    $student_registration_start_date_field->calendarIconType = 'calendar';
    $student_registration_start_date_field->dateType = 'datepicker';

    // student registration deadline
    $student_registration_end_date_field = new GF_Field_Date();
    $student_registration_end_date_field->label = "Student Registration Deadline";
    $student_registration_end_date_field->id = $field_mappings['competition_student_reg_end'];
    $student_registration_end_date_field->isRequired = true;
    $student_registration_end_date_field->calendarIconType = 'calendar';
    $student_registration_end_date_field->dateType = 'datepicker';

    // teacher registration start date
    $teacher_registration_start_date_field = new GF_Field_Date();
    $teacher_registration_start_date_field->label = "Teacher Registration Start Date";
    $teacher_registration_start_date_field->id = $field_mappings['competition_teacher_reg_start'];
    $teacher_registration_start_date_field->isRequired = true;
    $teacher_registration_start_date_field->calendarIconType = 'calendar';
    $teacher_registration_start_date_field->dateType = 'datepicker';

    // teacher registration deadline
    $teacher_registration_end_date_field = new GF_Field_Date();
    $teacher_registration_end_date_field->label = "Teacher Registration Deadline";
    $teacher_registration_end_date_field->id = $field_mappings['competition_teacher_reg_end'];
    $teacher_registration_end_date_field->isRequired = true;
    $teacher_registration_end_date_field->calendarIconType = 'calendar';
    $teacher_registration_end_date_field->dateType = 'datepicker';

    // teacher volunteer options
    $teacher_volunteer_times_field = new GF_Field_List();
    $teacher_volunteer_times_field->label = "Volunteer Time Options for Teachers";
    $teacher_volunteer_times_field->id = $field_mappings['competition_volunteer_times'];
    $teacher_volunteer_times_field->isRequired = true;
    $teacher_volunteer_times_field->description = "Enter at least two times for teachers to volunteer ";
    $teacher_volunteer_times_field->description .= "e.g. Saturday (10am-4pm), Sunday night, etc.";
    $teacher_volunteer_times_field->descriptionPlacement = 'above';

    // teacher csv file upload
    $teacher_csv_file_upload_field = new GF_Field_FileUpload();
    $teacher_csv_file_upload_field->label = CSV_TEACHER_FIELD_NAME;
    $teacher_csv_file_upload_field->id = $field_mappings['competition_teacher_csv_upload'];
    $teacher_csv_file_upload_field->isRequired = true;
    $teacher_csv_file_upload_field->description = 'Browse your computer for a CSV';
    $teacher_csv_file_upload_field->description .= ' file of teachers that';
    $teacher_csv_file_upload_field->description .= ' will be participating in';
    $teacher_csv_file_upload_field->description .= ' this music competition.';
    $teacher_csv_file_upload_field->description .= ' Don\'t worry, you will have';
    $teacher_csv_file_upload_field->description .= ' the opportunity to add more';
    $teacher_csv_file_upload_field->description .= ' teachers to this competition later.</br>';
    $teacher_csv_file_upload_field->description .= ' <b>The CSV file should be in the';
    $teacher_csv_file_upload_field->description .= ' following format:</br>First Name, ';
    $teacher_csv_file_upload_field->description .= ' Last Name, Phone, Email</b>';
    $teacher_csv_file_upload_field->descriptionPlacement = 'above';

/*


    // number of judges per sections
    $num_judges_per_section_field = new GF_Field_Number();
    $num_judges_per_section_field->label = "Number of Judges per Section";
    $num_judges_per_section_field->id = $field_mappings['competition_num_judges_per_section'];
    $num_judges_per_section_field->isRequired = false;

    // judge upload form
    $judge_csv_file_upload_field = new GF_Field_FileUpload();
    $judge_csv_file_upload_field->label = CSV_JUDGE_FIELD_NAME;
    $judge_csv_file_upload_field->id = $field_mappings['competition_judge_csv_upload'];
    $judge_csv_file_upload_field->isRequired = false;
    $judge_csv_file_upload_field->description = 'Browse your computer for a CSV';
    $judge_csv_file_upload_field->description .= ' file of judges that';
    $judge_csv_file_upload_field->description .= ' will be participating in';
    $judge_csv_file_upload_field->description .= ' this music competition.';
    $judge_csv_file_upload_field->description .= ' Don\'t worry, you will have';
    $judge_csv_file_upload_field->description .= ' the opportunity to add more';
    $judge_csv_file_upload_field->description .= ' judges to this competition later.';
    $judge_csv_file_upload_field->descriptionPlacement = 'above';

    // number of students per section per level (needed for lower level where
    // times aren't provided during registration)
      // not sure how to handle this at the moment

    // number of command performances

    $num_command_performance_field = new GF_Field_Number();
    $num_command_performance_field->label = "Number of Command Performance Performances";
    $num_command_performance_field->id = $field_mappings['competition_num_command_performances'];
    $num_command_performance_field->isRequired = false;


    // date of command performance
    $command_perf_date_field = new GF_Field_Date();
    $command_perf_date_field->label = "Command Performance Date";
    $command_perf_date_field->id = $field_mappings['competition_command_performance_date'];
    $command_perf_date_field->isRequired = false;
    $command_perf_date_field->calendarIconType = 'calendar';
    $command_perf_date_field->dateType = 'datepicker';

    // time of command performance
    $command_performance_time_field = new GF_Field_Time();
    $command_performance_time_field->label = "Command Performance Start Time";
    $command_performance_time_field->id = $field_mappings['competition_command_performance_time'];
    $command_performance_time_field->isRequired = false;
    */

    // command performance options
    $command_performance_option_field = new GF_Field_List();
    $command_performance_option_field->label = "Command Performance Time Options For Students";
    $command_performance_option_field->id = $field_mappings['competition_command_performance_opt'];
    $command_performance_option_field->isRequired = true;
    $command_performance_option_field->description = "These are the options given to the students when registering. ";
    $command_performance_option_field->description .= "e.g. Thursday at 5:30pm, 7PM on Jan 1, etc.";
    $command_performance_option_field->descriptionPlacement = 'above';

    // theory score required for special recognition
    $theory_score_field = new GF_Field_Select();
    $theory_score_field->label = "Theory Score for Recognition (70-100)";
    $theory_choices = array();
    for ($i = 70; $i <= 100; $i++) {
      $single_theory_choice = array();
      $single_theory_choice['text'] = strval($i);
      $single_theory_choice['value'] = strval($i);
      $single_theory_choice['isSelected'] = false;
      $theory_choices[] = $single_theory_choice;
      unset($single_theory_choice);
    }
    $theory_score_field->choices = $theory_choices;
    $theory_score_field->id = $field_mappings['competition_theory_score'];
    $theory_score_field->isRequired = true;

    // master class
    $has_master_class = new GF_Field_Radio();
    $has_master_class->label = "Allow students to register for master class?";
    $has_master_class->id = $field_mappings['competition_has_master_class'];
    $has_master_class->isRequired = true;
    $has_master_class->choices = array(
        array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
        array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );

    // Pricing section break
    $section_break = new GF_Field_Section();
    $section_break->label = "Pricing";
    $section_break->description = "Enter prices only for levels eligible to";
    $section_break->description .= " participate in this competition.";

    // PayPal Email
    $paypal_email_field = new GF_Field_Email();
    $paypal_email_field->label = "Paypal Account Email";
    $paypal_email_field->id = $field_mappings['paypal_email'];
    $paypal_email_field->description = "Please enter the email address associated";
    $paypal_email_field->description .= " with your PayPal account. Please make sure this";
    $paypal_email_field->description .= " PayPal is setup according to the Gravity Forms";
    $paypal_email_field->description .= " PayPal Add On directions.";
    $paypal_email_field->descriptionPlacement = "above";
    $paypal_email_field->isRequired = true;


    // level price
    $pricing = array();
   for( $i = 1; $i <= 11; $i++ )
    {
        $level_price = new GF_Field_Number();
        $level_price->label = "Price for Level " . $i . " Student";
        $level_price->id = $field_mappings['level_' . $i . '_price'];
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

    /*
    $form->fields[] = $num_traditional_sections_field;
    $form->fields[] = $num_master_sections_field;
    $form->fields[] = $beginning_time_buffer_field;
    $form->fields[] = $ending_time_buffer_field;
    $form->fields[] = $lunch_break_field;
    $form->fields[] = $num_judges_per_section_field;
    $form->fields[] = $judge_csv_file_upload_field;
    $form->fields[] = $command_perf_date_field;
    $form->fields[] = $command_performance_time_field;
    */

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
   * @param $competition_entry Entry Object The entry of the newly created music competition
   * @param $teacher_names_and_hashes Array The array of teacher names in this competition
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_create_student_form($competition_entry, $teacher_names_and_hashes, $command_options_array, $competition_festival_chairman_email, $paypal_email) {
    $create_comp_field_mapping = ARIA_API::aria_competition_field_id_array();
    $field_id_array = ARIA_API::aria_student_field_id_array();
    $competition_name = $competition_entry[$create_comp_field_mapping['competition_name']];
    $student_form = new GF_Form("{$competition_name} Student Registration", "");
    $ariaFieldIds = array();

    // parent name
    $parent_name_field = new GF_Field_Name();
    $parent_name_field->label = "Parent Name";
    $parent_name_field->id = $field_id_array['parent_name'];
    $parent_name_field->isRequired = true;
    $parent_name_field = self::aria_add_default_name_inputs($parent_name_field);
    $student_form->fields[] = $parent_name_field;
    $ariaFieldIds['parent_name'] = $parent_name_field->id;
    $ariaFieldIds['parent_first_name'] = "{$parent_name_field->id}.3";
    $ariaFieldIds['parent_last_name'] = "{$parent_name_field->id}.6";

    // parent email
    $parent_email_field = new GF_Field_Email();
    $parent_email_field->label = "Parent's Email";
    $parent_email_field->id = $field_id_array['parent_email'];
    $parent_email_field->isRequired = true;
    $student_form->fields[] = $parent_email_field;
    $ariaFieldIds['parent_email'] = $parent_email_field->id;

    // parent email confirmation
    $parent_email_confirmation = new GF_Field_Email();
    $parent_email_confirmation->label = "Parent's Email (confirmation)";
    $parent_email_confirmation->id = $field_id_array['parent_email_confirmation'];
    $parent_email_confirmation->isRequired = true;
    $parent_email_confirmation->description = "This email must match the email
    entered in the previous box above (Parent's Email).";
    $parent_email_confirmation->descriptionPlacement = "above";
    $student_form->fields[] = $parent_email_confirmation;
    $ariaFieldIds['parent_email_confirmation'] = $parent_email_confirmation->id;

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
    $ariaFieldIds['student_name'] = $student_name_field->id;
    $ariaFieldIds['student_first_name'] = "{$student_name_field->id}.3";
    $ariaFieldIds['student_last_name'] = "{$student_name_field->id}.6";

    // student birthday
    $student_birthday_date_field = new GF_Field_Date();
    $student_birthday_date_field->label = "Student Birthday";
    $student_birthday_date_field->id = $field_id_array['student_birthday'];
    $student_birthday_date_field->isRequired = true;
    $student_birthday_date_field->calendarIconType = 'calendar';
    $student_birthday_date_field->dateType = 'datepicker';
    $student_form->fields[] = $student_birthday_date_field;
    $ariaFieldIds['student_birthday'] = $student_birthday_date_field->id;

    // student's piano teacher
    $piano_teachers_field = new GF_Field_Select();
    $piano_teachers_field->label = "Piano Teacher's Name";
    $piano_teachers_field->id = $field_id_array['teacher_name'];
    $piano_teachers_field->isRequired = true;
    $piano_teachers_field->description = "Please select your teachers name";
    $piano_teachers_field->description .= " from the drop-down below. ";
    $piano_teachers_field->description .= "If your teacher is not listed, please ";
    $piano_teachers_field->description .= 'contact the festival chairman at '.$competition_festival_chairman_email.'.';
    $piano_teachers_field->descriptionPlacement = 'above';

    // add all of the piano teachers that are competing in this competition
    $formatted_teacher_names = array();

    // alphabetize teachers
    usort($teacher_names_and_hashes, function($a, $b) {
        return strcmp($a[1], $b[1]);
    });

    foreach ($teacher_names_and_hashes as $key => $value) {
      $single_teacher = array(
        'text' => $value[0] . ' ' . $value[1],
        'value' => serialize($value),
        'isSelected' => false
      );
      $formatted_teacher_names[] = $single_teacher;
      unset($single_teacher);
    }

    $piano_teachers_field->choices = $formatted_teacher_names;
    $student_form->fields[] = $piano_teachers_field;
    $ariaFieldIds['teacher_name'] = $piano_teachers_field->id;

/*
    // student's piano teacher does not exist
    $teacher_missing_field_name = new GF_Field_Text();
    $teacher_missing_field_name->label = "If your teacher's name is not listed, " .
    "please enter your teacher's name below.";
    $teacher_missing_field_name->id = $field_id_array['not_listed_teacher_name'];
    $teacher_missing_field_name->isRequired = false;
    $student_form->fields[] = $teacher_missing_field_name;
    $ariaFieldIds['not_listed_teacher_name'] = $teacher_missing_field_name->id;

    // student's piano teacher does not exist
    $teacher_missing_field_email = new GF_Field_Email();
    $teacher_missing_field_email->label = "If your teacher's name is not listed, " .
    "please enter your teacher's email below.";
    $teacher_missing_field_email->id = $field_id_array['not_listed_teacher_email'];
    $teacher_missing_field_email->isRequired = false;
    $student_form->fields[] = $teacher_missing_field_email;
    $ariaFieldIds['not_listed_teacher_name'] = $teacher_missing_field_email->id;
*/
    // student's available times to compete
    $available_times = new GF_Field_Radio();
    $available_times->label = "Available Festival Days";
    $available_times->id = $field_id_array['available_festival_days'];
    $available_times->isRequired = true;
    $available_times->description = "There is no guarantee that scheduling ".
    "requests will be honored.";
    $available_times->descriptionPlacement = 'above';
    $available_times->choices = array(
      array('text' => 'Either Saturday or Sunday', 'value' => 'Either Saturday or Sunday', 'isSelected' => false),
      array('text' => 'Saturday', 'value' => 'Saturday', 'isSelected' => false),
      array('text' => 'Sunday', 'value' => 'Sunday', 'isSelected' => false)
    );
    $student_form->fields[] = $available_times;
    $ariaFieldIds['available_festival_days'] = $available_times->id;
    for ($i=1; $i <= count($available_times->inputs); $i++) {
      $ariaFieldIds["available_festival_days_option_{$i}"] = "{$available_times->id}.{$i}";
    }

    // student's available times to compete for command performance
    $command_times = new GF_Field_Radio();
    $command_times->label = "Preferred Command Performance Time";
    $command_times->id = $field_id_array['preferred_command_performance'];
    $command_times->isRequired = true;
    $command_times->description = "Please select the Command Performance time ".
    "that you prefer in the event that your child receives a superior rating.";
    $command_times->descriptionPlacement = 'above';
    $command_times->choices = array();
    $command_times->choices[]
          = array('text' => 'Any time', 'value' => 'Any time', 'isSelected' => false);
    if (is_array($command_options_array)) {
      $index = 1;
      foreach( $command_options_array as $command_time ) {
        $command_times->choices[]
          = array('text' => $command_time, 'value' => $command_time, 'isSelected' => false);
      }
    }
    $student_form->fields[] = $command_times;
    $ariaFieldIds['preferred_command_performance'] = $command_times->id;
    for ($i=1; $i <= count($command_times->inputs); $i++) {
      $ariaFieldIds["preferred_command_performance_option_{$i}"] = "{$command_times->id}.{$i}";
    }

    // student's festival level
    $student_level_field = new GF_Field_Select();
    $student_level_field->label = "Student Level";
    $student_level_field->id = $field_id_array['student_level'];
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

    $product_field = new GF_Field_Product();
    $product_field->label = "Student Level";
    $product_field->id = $field_id_array['level_pricing'];
    $product_field->isRequired = true;
    $product_field->size = "small";
    $product_field->inputs = null;
    $product_field->inputType = "select";
    $product_field->enablePrice = true;
    $product_field->basePrice = "$1.00";
    $product_field->disableQuantity = true;
    $product_field->displayAllCategories = false;
    $product_field->description = "Please enter your student's festival level.";
    $product_field->description .= " If you do not know this value, please do";
    $product_field->description .= " not submit this form until your child";
    $product_field->description .= " contacts his/her instructor and can verify";
    $product_field->description .= " this value.";
    $product_field->descriptionPlacement = 'above';

    $product_field->choices = array();
    for( $i = 1; $i <= 11; $i++ )
    {
        $price = $competition_entry[$create_comp_field_mapping['level_'. $i .'_price']];
        if($price != 0)
        {
          $product_field->choices[] = array('text' => (string)$i,
                                            'value' => (string)$i,
                                            'isSelected' => false,
                                            'price' => $price);
        }
    }
    $student_form->fields[] = $product_field;

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
    $compliance_field->descriptionPlacement = 'above';
    $compliance_field->choices = array(
      array('text' => 'I have read and agree with the above statement.',
      'value' => 'Agree',
      'isSelected' => false),
    );
    $compliance_field->inputs = array();
    $compliance_field = self::aria_add_checkbox_input( $compliance_field, 'I have read and agree with the following statement:' );
    $student_form->fields[] = $compliance_field;
    $ariaFieldIds['compliance_statement'] = $compliance_field->id;
    for ($i=1; $i <= count($compliance_field->inputs); $i++) {
      $ariaFieldIds["compliance_statement_option_{$i}"] = "{$compliance_field->id}.{$i}";
    }


    $total_field = new GF_Field_Total();
    $total_field->label = "Total Registration Cost";
    $total_field->id = $field_id_array['registration_total'];
    $total_field->isRequired = false;
    $student_form->fields[] = $total_field;


    // custom submission message to let the festival chairman know the creation was
    // a success

    $successful_submission_message = 'Congratulations! You have just';
    $successful_submission_message .= ' successfully registered your child.';
    $student_form->confirmation['type'] = 'message';
    $student_form->confirmation['message'] = $successful_submission_message;

    $student_form_arr = $student_form->createFormArray();
    $student_form_arr['isStudentPublicForm'] = true;
    $student_form_arr['ariaFieldIds'] = $ariaFieldIds;
    // add the new form to the festival chairman's dashboard
    $new_form_id = GFAPI::add_form($student_form_arr);

    // make sure the new form was added without error
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }

    // create feed for payment
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

    return $new_form_id;
  }

  /**
   * This function will perform form validation on the student form.
   *
   *
   *
   * @param   $result   Array   The validation result to be filtered.
   * @param   $value  String/Array  The field value to be validated.
   * @param   $form   Form Object   The current form object.
   * @param   $field  Field Object  The current field object.
   */
  public static function aria_student_form_validation($result, $value, $form, $field) {
    // only perform processing if it's the student registration form
    if (!array_key_exists('isStudentPublicForm', $form)
        || !$form['isStudentPublicForm']) {
          return $result;
    }

    $field_mapping = ARIA_API::aria_student_field_id_array();
    $parent_email = trim(rgar($value, $field_mapping['parent_email']));
    $parent_email_confirmation = trim(rgar($value, $field_mapping['parent_email_confirmation']));

    // check to see if email fields match
    if (strcmp($parent_email, $parent_email_confirmation) !== 0) {
      $result['is_valid'] = false;
      $result['message'] = "The email in the 'Parent's Email' field must match the email in
      the 'Parent's Email (confirmation)' field.";
    }

    return $result;
  }
}
