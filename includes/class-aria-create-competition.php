<?php

/**
 * The file that defines create competition functionality.
 *
 * A class definition that includes attributes and functions that allow the
 * festival chairman to create new music competitions for NNMTA.
 *
 * @link       http://wesleykepke.github.io/ARIA_Plugin/
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
    $title = $entry[strval($field_mapping['name'])];
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
   * Using name of a given competition, this function will search the upload
   * location for a file of the essentially the same name (will have _Entry.txt).
   * If located, this function will read the serialized contents of that file,
   * unserialize this content, and return an Entry object that was submitted from
   * the create competition form. If no such competition was found, this function
   * will return -1.
   *
   * @param   String  $title   The title of the competition to search for.
   *
   * @return  Will return the associated Entry object (if found) or -1 (not found).
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_read_comp_from_file($title) {
    $title = str_replace(' ', '_', $title);
    $file_path = ARIA_FILE_UPLOAD_LOC . $title . "_Entry.txt";
    if (file_exists($file_path)) {
      $entry = file_get_contents($file_path);
      $entry = unserialize($entry);
      return $entry;
    }
    return -1;
  }

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
    // only perform processing if it's the create competition form
    if (!array_key_exists('isCompetitionCreationForm', $form)
        || !$form['isCompetitionCreationForm']) {
          return $confirmation;
    }

    // check to see if the given competition name has already been used
    $field_mapping = ARIA_API::aria_competition_field_id_array();
    $competition_name = $entry[strval($field_mapping['name'])];
    $all_forms = GFAPI::get_forms();
    foreach ($all_forms as $single_form) {
      if (strpos($single_form['title'], $competition_name) !== false) {
        // instruct user on how to proceed if there are duplicate names
        wp_die("<h1>Oh no! A competition with the name '$competition_name' already
            exists. Please remove all of the forms and pages for '$competition_name'
            in the WordPress dashboard and try creating the competition again <i>or</i>
            change the name of the competition you're trying to create.</h1>");
      }
    }

    // create the student master form
    $command_performance_options = $entry[strval($field_mapping['command_performance_options'])];
    $command_performance_options = unserialize($command_performance_options);
    $master_class_registration_option = $entry[strval($field_mapping['master_class_registration_option'])];
    $student_master_form_id =
      ARIA_Create_Master_Forms::aria_create_student_master_form($competition_name,
                                                                $command_performance_options,
                                                                $master_class_registration_option);

    // create the teacher master form
    $volunteer_options = $entry[strval($field_mapping['volunteer_options'])];
    $volunteer_options = unserialize($volunteer_options);
    $volunteer_time_options = $entry[strval($field_mapping['volunteer_time_options'])];
    $volunteer_time_options = unserialize($volunteer_time_options);
    $teacher_master_form_id =
      ARIA_Create_Master_Forms::aria_create_teacher_master_form($competition_name,
                                                                $volunteer_options,
                                                                $volunteer_time_options);

    // upload content of the teacher csv file into the teacher master form
    $teacher_csv_file_path = ARIA_API::aria_get_teacher_csv_file_path($entry, $form);
    $teacher_names_and_hashes = ARIA_Teacher::aria_upload_from_csv($teacher_csv_file_path,
                                                                   $teacher_master_form_id);

    // create the student public form
    $student_form_id = self::aria_create_student_form($entry,
                                                      $teacher_names_and_hashes);
    $student_form_url = ARIA_API::aria_publish_form("{$competition_name} Student Registration",
                                                    $student_form_id);

    // create the teacher public form
    $teacher_form_id = self::aria_create_teacher_form($entry);
    $teacher_form_url = ARIA_API::aria_publish_form("{$competition_name} Teacher Registration",
                                                    $teacher_form_id);

    // associate all of the related forms
    $related_forms = array(
      'student_public_form_id' => $student_form_id,
      'teacher_public_form_id' => $teacher_form_id,
      'student_master_form_id' => $student_master_form_id,
      'teacher_master_form_id' => $teacher_master_form_id,
      'student_public_form_url' => $student_form_url,
      'teacher_public_form_url' => $teacher_form_url,
      'festival_chairman_email' => $entry[strval($field_mapping['chairman_email'])],
      'festival_name' => $competition_name
    );

    // if requested, add the notification email
    if ($entry[strval($field_mapping['notification_option'])] == 'Yes') {
      $related_forms['notification_email'] = $entry[strval($field_mapping['notification_email'])];
    }
    else {
      $related_forms['notification_email'] = null;
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

    // add time limits to the student public form (from competition creation)
    $student_public_form['scheduleForm'] = true;
    $student_public_form['scheduleStart'] = $entry[strval($field_mapping['student_registration_start'])];
    $student_public_form['scheduleStartHour'] = 12;
    $student_public_form['scheduleStartMinute'] = 0;
    $student_public_form['scheduleStartAmpm'] = 'am';
    $student_public_form['scheduleEnd'] = $entry[strval($field_mapping['student_registration_end'])];
    $student_public_form['scheduleEndHour'] = 11;
    $student_public_form['scheduleEndMinute'] = 59;
    $student_public_form['scheduleEndAmpm'] = 'pm';
    $student_public_form['scheduleMessage'] = "Please be patient as we wait for
    Festival Registration to open. The deadline will be extended if necessary to
    allow every student an opportunity to register.";
    $student_public_form['schedulePendingMessage'] = $student_public_form['scheduleMessage'];

    // add time limits to the teacher public form (from competition creation)
    $teacher_public_form['scheduleForm'] = true;
    $teacher_public_form['scheduleStart'] = $entry[strval($field_mapping['teacher_registration_start'])];
    $teacher_public_form['scheduleStartHour'] = 12;
    $teacher_public_form['scheduleStartMinute'] = 0;
    $teacher_public_form['scheduleStartAmpm'] = 'am';
    $teacher_public_form['scheduleEnd'] = $entry[strval($field_mapping['teacher_registration_end'])];
    $teacher_public_form['scheduleEndHour'] = 11;
    $teacher_public_form['scheduleEndMinute'] = 59;
    $teacher_public_form['scheduleEndAmpm'] = 'pm';
    $teacher_public_form['scheduleMessage'] = "Please be patient as we wait for
    Festival Registration to open. The deadline will be extended if necessary to
    allow every teacher an opportunity to register his/her students.";
    $teacher_public_form['schedulePendingMessage'] = $teacher_public_form['scheduleMessage'];

    // update the related forms
    GFAPI::update_form($student_public_form);
    GFAPI::update_form($teacher_public_form);
    GFAPI::update_form($student_master_form);
    GFAPI::update_form($teacher_master_form);

    // save the entry object to a file on the server in case it is deleted
    self::aria_save_comp_to_file($entry);

    // add a custom verification message that competition creation was successful
    $confirmation = "Congratulations! A new music competition has been created.
    The following forms are now available for students and teachers to use for
    registration:</br>
    <a href={$student_form_url}>{$competition_name} Student Registration</a>.
    </br>
    <a href={$teacher_form_url}>{$competition_name} Teacher Registration</a>.";
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
    $volunteer_options = new GF_Field_List();
    $volunteer_options->label = "Volunteer Options for Teachers";
    $volunteer_options->id = $field_mapping['volunteer_options'];
    $volunteer_options->isRequired = true;
    $volunteer_options->description = "Enter options for volunteers to participate
    in (clean up, door monitor, section proctor, etc.).";
    $volunteer_options->descriptionPlacement = 'above';
    $form->fields[] = $volunteer_options;

    // teacher volunteer time options field
    $volunteer_time_options = new GF_Field_List();
    $volunteer_time_options->label = "Volunteer Time Options for Teachers";
    $volunteer_time_options->id = $field_mapping['volunteer_time_options'];
    $volunteer_time_options->isRequired = true;
    $volunteer_time_options->description = "Enter at least two times for teachers
    to volunteer (Saturday (10am-4pm), Sunday night, etc.).";
    $volunteer_time_options->descriptionPlacement = 'above';
    $form->fields[] = $volunteer_time_options;

    // teacher csv file upload field
    $teacher_upload = new GF_Field_FileUpload();
    $teacher_upload->label = CSV_TEACHER_FIELD_NAME;
    $teacher_upload->id = $field_mapping['teacher_upload'];
    $teacher_upload->isRequired = true;
    $teacher_upload->description = "Browse your computer for a CSV file of
    teachers that will be participating in this music competition. If a teacher
    decides that he/she wants to participate in this competition later, you will
    have the opportunity to add more teachers using the 'ARIA: Add Teacher' page
    (located in the 'Pages' section of the WordPress dashboard). <b>Please note
    that the CSV file should be in the following format: First Name, Last Name,
    Phone, Email</b>";
    $teacher_upload->descriptionPlacement = 'above';
    $form->fields[] = $teacher_upload;

    // command performance options field
    $command_performance_options = new GF_Field_List();
    $command_performance_options->label = "Command Performance Time Options For Students";
    $command_performance_options->id = $field_mapping['command_performance_options'];
    $command_performance_options->isRequired = true;
    $command_performance_options->description = "These are the options that will
    be shown to the students when registering (e.g. Thursday at 5:30pm,
    7PM on Jan 1, etc.).";
    $command_performance_options->descriptionPlacement = 'above';
    $form->fields[] = $command_performance_options;

    // master class registration option field
    $master_class_registration_option = new GF_Field_Radio();
    $master_class_registration_option->label = "Master Class Sections?";
    $master_class_registration_option->id = $field_mapping['master_class_registration_option'];
    $master_class_registration_option->isRequired = true;
    $master_class_registration_option->description = "Should students be allowed
    to register for a master class section in this competition?";
    $master_class_registration_option->choices = array(
        array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
        array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $form->fields[] = $master_class_registration_option;

    // field for enabling email notifications to festival chairman
    $notification_option = new GF_Field_Radio();
    $notification_option->label = "Would you like to be notified when students register?";
    $notification_option->id = $field_mapping['notification_option'];
    $notification_option->isRequired = true;
    $notification_option->description = "This email will be used to send you
    updates when a student registers for festival and also how many students
    have registered so far. This information can significantly help during the
    scheduling phase. Moreover, these emails will also include the links
    that will be sent to teachers in order to complete the registration process,
    so if a teacher misplaces his/her student registration email, you can supply
    them with the necessary registration link.";
    $notification_option->descriptionPlacement = 'above';
    $notification_option->choices = array(
        array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
        array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $form->fields[] = $notification_option;

    // notification email field
    $notification_email = new GF_Field_Email();
    $notification_email->label = "Notification Email";
    $notification_email->id = $field_mapping['notification_email'];
    $notification_email->description = "Please enter the email address you
    would like notificiation emails to be sent to.";
    $notification_email->descriptionPlacement = "above";
    $notification_email->isRequired = true;

    // add the conditional rules for the notification email field
    $notification_email_conditional_rules = array();
    $notification_email_conditional_rules[] = array(
      'fieldId' => $field_mapping['notification_option'],
      'operator' => 'is',
      'value' => 'Yes'
    );
    $notification_email->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $notification_email_conditional_rules
    );
    $form->fields[] = $notification_email;

    // notification email confirmation field
    $notification_email_confirmation = new GF_Field_Email();
    $notification_email_confirmation->label = "Notification Email (confirmation)";
    $notification_email_confirmation->id = $field_mapping['notification_email_confirmation'];
    $notification_email_confirmation->description = "This email address must match
    the email address entered in the previous field (Notification Email).";
    $notification_email_confirmation->descriptionPlacement = "above";
    $notification_email_confirmation->isRequired = true;

    // add the conditional rules for the notification email confirmation field
    $notification_email_confirmation->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $notification_email_conditional_rules
    );
    $form->fields[] = $notification_email_confirmation;

    // add a section break and begin pricing
    $section_break = new GF_Field_Section();
    $section_break->label = "Pricing";
    $section_break->description = "Enter prices only for levels eligible to
    participate in this competition.";
    $form->fields[] = $section_break;

    // PayPal email field
    $paypal_email = new GF_Field_Email();
    $paypal_email->label = "PayPal Account Email";
    $paypal_email->id = $field_mapping['paypal_email'];
    $paypal_email->description = "Please enter the email address associated
    with your PayPal account. Please make sure this PayPal is setup according to
    the Gravity Forms PayPal Add-On directions.";
    $paypal_email->descriptionPlacement = "above";
    $paypal_email->isRequired = true;
    $form->fields[] = $paypal_email;

    // PayPal email confirmation field
    $paypal_email_confirmation = new GF_Field_Email();
    $paypal_email_confirmation->label = "PayPal Account Email (confirmation)";
    $paypal_email_confirmation->id = $field_mapping['paypal_email_confirmation'];
    $paypal_email_confirmation->description = "This email address must match
    the email address entered in the previous field (PayPal Account Email).";
    $paypal_email_confirmation->descriptionPlacement = "above";
    $paypal_email_confirmation->isRequired = true;
    $form->fields[] = $paypal_email_confirmation;

    // level pricing field for PayPal
    for ($i = 1; $i <= 11; $i++) {
      $level_price = new GF_Field_Number();
      $level_price->label = "Price for Level " . strval($i) . " Student";
      $level_price->id = $field_mapping['level_' . strval($i) . '_price'];
      $level_price->defaultValue = '0.00';
      $level_price->size = 'small';
      $level_price->isRequired = false;
      $level_price->numberFormat = 'currency';
      $form->fields[] = $level_price;
      //$pricing[] = $level_price;
      unset($level_price);
    }

    // add a default message for successful creation
    $successful_submission_message = "Congratulations! You have just successfully
    registered your student.";
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

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
   * This function will perform validation on the input obtain from the create
   * competition form.
   *
   * Various validation checks will be performed to ensure that the data input
   * into the create competition form is accurate and valid.
   *
   * @param   $validation_result  Array   Contains the validation result and the current Form Object.
   *
   * @return  $validation_result  Array   Contains the validation result and the current Form Object.
   *
   * @since 2.0.0
   * @author KREW
   */
  public static function aria_create_competition_validation($validation_result) {
    // obtain the form object and the field mapping for this object
    $form = $validation_result['form'];
    $field_mapping = ARIA_API::aria_competition_field_id_array();

    // only perform form validation if it's the create competition form
    if (!array_key_exists('isCompetitionCreationForm', $form)
        || !$form['isCompetitionCreationForm']) {
          return $validation_result;
    }

    // obtain the input for festival chairman email and the confirmation email
    $chairman_email = "input_" . strval($field_mapping['chairman_email']);
    $chairman_email_confirmation = "input_" . strval($field_mapping['chairman_email_confirmation']);
    $chairman_email = rgpost($chairman_email);
    $chairman_email_confirmation = rgpost($chairman_email_confirmation);

    // obtain the input for notification email and the confirmation email
    $notification_email = "input_" . strval($field_mapping['notification_email']);
    $notification_email_confirmation = "input_" . strval($field_mapping['notification_email_confirmation']);
    $notification_email = rgpost($notification_email);
    $notification_email_confirmation = rgpost($notification_email_confirmation);

    // obtain the input for PayPal email and confirmation email
    $paypal_email = "input_" . strval($field_mapping['paypal_email']);
    $paypal_email_confirmation = "input_" . strval($field_mapping['paypal_email_confirmation']);
    $paypal_email = rgpost($paypal_email);
    $paypal_email_confirmation = rgpost($paypal_email_confirmation);

    // obtain the start and end dates for the festival
    $month_offset = 0;
    $day_offset = 1;
    $year_offset = 2;
    $start_date = "input_" . strval($field_mapping['start_date']);
    $end_date = "input_" . strval($field_mapping['end_date']);
    $start_date = rgpost($start_date);
    $end_date = rgpost($end_date);

    // obtain the start and end dates for student registration
    $student_registration_start = "input_" . strval($field_mapping['student_registration_start']);
    $student_registration_end = "input_" . strval($field_mapping['student_registration_end']);
    $student_registration_start = rgpost($student_registration_start);
    $student_registration_end = rgpost($student_registration_end);

    // obtain the start and end dates for teacher registration
    $teacher_registration_start = "input_" . strval($field_mapping['teacher_registration_start']);
    $teacher_registration_end = "input_" . strval($field_mapping['teacher_registration_end']);
    $teacher_registration_start = rgpost($teacher_registration_start);
    $teacher_registration_end = rgpost($teacher_registration_end);

    // compare the festival chairman email with the festival chairman confirmation email
    if (strcmp($chairman_email, $chairman_email_confirmation) !== 0) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // festival chairman confirmation email field
        if ($field->id == strval($field_mapping['chairman_email_confirmation'])) {
          $field->failed_validation = true;
          $field->validation_message = "This email must match the email in the
          field titled 'Festival Chairman Email'.";
        }
      }
    }

    // compare the notification email with the notification confirmation email
    if (strcmp($notification_email, $notification_email_confirmation) !== 0) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // notification confirmation email field
        if ($field->id == strval($field_mapping['notification_email_confirmation'])) {
          $field->failed_validation = true;
          $field->validation_message = "This email must match the email in the
          field titled 'Notification Email'.";
        }
      }
    }

    // compare the PayPal email with the PayPal confirmation email
    if (strcmp($paypal_email, $paypal_email_confirmation) !== 0) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // PayPal confirmation email field
        if ($field->id == strval($field_mapping['paypal_email_confirmation'])) {
          $field->failed_validation = true;
          $field->validation_message = "This email must match the email in the
          field titled 'PayPal Account Email'.";
        }
      }
    }

    // check competition dates to ensure they occur in chronological order
    $festival_date_incorrect = false;

    // start date year is less than today's year
    /*
    if ($start_date[$year_offset] < date("Y")) {
      foreach ($form['fields'] as &$field) {
        // end date field
        if ($field->id == strval($field_mapping['start_date'])) {
          $field->failed_validation = true;
          $field->validation_message = "The festival start date must occur in
          the past.";
        }
      }
    }

    // same year, but date and month are in past
    elseif ($start_date[$year_offset] <= date("Y") &&
            $start_date[$month_offset] <= date("m") &&
            $start_date[$day_offset] < date("d")) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // end date field
        if ($field->id == strval($field_mapping['start_date'])) {
          $field->failed_validation = true;
          $field->validation_message = "The festival start date must occur in
          the past.";
        }
      }
    }
    */

    if ($start_date[$year_offset] > $end_date[$year_offset]) {
      $festival_date_incorrect = true;
    }

    elseif ($start_date[$month_offset] > $end_date[$month_offset]) {
      $festival_date_incorrect = true;
    }

    elseif ($start_date[$year_offset] == $end_date[$year_offset] &&
            $start_date[$month_offset] == $end_date[$month_offset] &&
            $start_date[$day_offset] > $end_date[$day_offset]) {
      $festival_date_incorrect = true;
    }

    if ($festival_date_incorrect) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // end date field
        if ($field->id == strval($field_mapping['end_date'])) {
          $field->failed_validation = true;
          $field->validation_message = "The festival end date must occur on a
          date after the festival start date.";
        }
      }
    }

    // check student registration dates to ensure they occur in chronological order
    $student_registration_date_incorrect = false;
    if ($student_registration_start[$year_offset] > $student_registration_end[$year_offset]) {
      $student_registration_date_incorrect = true;
    }

    elseif ($student_registration_start[$month_offset] > $student_registration_end[$month_offset]) {
      $student_registration_date_incorrect = true;
    }

    elseif ($student_registration_start[$year_offset] == $student_registration_end[$year_offset] &&
            $student_registration_start[$month_offset] == $student_registration_end[$month_offset] &&
            $student_registration_start[$day_offset] > $student_registration_end[$day_offset]) {
      $student_registration_date_incorrect = true;
    }

    if ($student_registration_date_incorrect) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // end date field
        if ($field->id == strval($field_mapping['student_registration_end'])) {
          $field->failed_validation = true;
          $field->validation_message = "The student registration end date must
          occur on a date after the student registration start date.";
        }
      }
    }

    // check teacher registration dates to ensure they occur in chronological order
    $teacher_registration_date_incorrect = false;
    if ($teacher_registration_start[$year_offset] > $teacher_registration_end[$year_offset]) {
      $teacher_registration_date_incorrect = true;
    }

    elseif ($teacher_registration_start[$month_offset] > $teacher_registration_end[$month_offset]) {
      $teacher_registration_date_incorrect = true;
    }

    elseif ($teacher_registration_start[$year_offset] == $teacher_registration_end[$year_offset] &&
            $teacher_registration_start[$month_offset] == $teacher_registration_end[$month_offset] &&
            $teacher_registration_start[$day_offset] > $teacher_registration_end[$day_offset]) {
      $teacher_registration_date_incorrect = true;
    }

    if ($teacher_registration_date_incorrect) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // teacher registration end date field
        if ($field->id == strval($field_mapping['teacher_registration_end'])) {
          $field->failed_validation = true;
          $field->validation_message = "The teacher registration end date must
          occur on a date after the teacher registration start date.";
        }
      }
    }

    $validation_result['form'] = $form;
    return $validation_result;
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
   * In order for a checkbox field to be properly displayed on a form, it needs
   * to be initialized. This function is responsible for taking a checkbox field
   * as input and providing initialized state for that checkbox field.
   *
   * @param   Field Object  $field  The field used for checkbox input.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_add_checkbox_input(&$field, $new_input) {
    // if the field's input data member is not an array yet, create it
    if (!is_array($field->inputs)) {
      $field->inputs = array();
    }

    $next_input = sizeof($field->inputs) + 1;

    // for array of checklist items, add every option
    if (is_array($new_input)) {
      foreach ($new_input as $input) {
        $field->inputs[] = array(
          "id" => "{$field->id}.{$next_input}",
          "label" => $input,
          "name" => ""
        );
        $next_input = $next_input + 1;
      }
    }

    // otherwise, just add the single option
    else {
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
  private static function aria_create_teacher_form($competition_entry) {
    // obtain the corresponding field mapping and create the teacher form
    $competition_field_mapping = ARIA_API::aria_competition_field_id_array();
    $competition_name = $competition_entry[strval($competition_field_mapping['name'])];
    $form = new GF_Form("{$competition_name} Teacher Registration", "");
    $teacher_field_mapping = ARIA_API::aria_teacher_field_id_array();
    $ariaFieldIds = array();

    // field for teacher name
    $name = new GF_Field_Name();
    $name->label = "Name";
    $name->id = $teacher_field_mapping['name'];
    $name->isRequired = true;
    $name = self::aria_add_default_name_inputs($name);
    $form->fields[] = $name;
    $ariaFieldIds['name'] = $name->id;

    // field for teacher email
    $email = new GF_Field_Email();
    $email->label = "Email";
    $email->id = $teacher_field_mapping['email'];
    $email->isRequired = true;
    $form->fields[] = $email;
    $ariaFieldIds['email'] = $email->id;

    // field for teacher phone
    $phone = new GF_Field_Phone();
    $phone->label = "Phone";
    $phone->id = $teacher_field_mapping['phone'];
    $phone->isRequired = true;
    $form->fields[] = $phone;
    $ariaFieldIds['phone'] = $phone->id;

    // teacher is judging
    $is_judging = new GF_Field_Radio();
    $is_judging->label = "Are you scheduled to judge for the festival?";
    $is_judging->id = $teacher_field_mapping['is_judging'];
    $is_judging->isRequired = true;
    $is_judging->choices = array(
    	array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
    	array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );
    $form->fields[] = $is_judging;
    $ariaFieldIds['is_judging'] = $is_judging->id;

    // teacher volunteer preference field
    $volunteer_options_array = $competition_entry[strval($competition_field_mapping['volunteer_options'])];
    $volunteer_options_array = unserialize($volunteer_options_array);
    $volunteer_preference = new GF_Field_Checkbox();
    $volunteer_preference->label = "Volunteer Preference";
    $volunteer_preference->id = $teacher_field_mapping['volunteer_preference'];
    $volunteer_preference->isRequired = true;
    $volunteer_preference->description = "Please check at least two volunteer job
    preferences for this festival. You will be notified by email of your volunteer
    assignments as the event approaches.";
    $volunteer_preference->descriptionPlacement = 'above';

    // add the volunteer options that were input from create competition
    $volunteer_preference->choices = array();
    if (is_array($volunteer_options_array)) {
      foreach ($volunteer_options_array as $option) {
        $volunteer_preference->choices[] = array(
          'text' => $option,
          'value' => $option,
          'isSelected' => false
        );
      }
    }

    // add the volunteer options as inputs to the checkbox
    $volunteer_preference->inputs = array();
    $volunteer_preference = self::aria_add_checkbox_input($volunteer_preference,
                                                          $volunteer_options_array);

    // finish adding the volunteer options field into the form
    $conditional_volunteer_preference_rules = array();
    $conditional_volunteer_preference_rules[] = array(
      'fieldId' => $teacher_field_mapping['is_judging'], // dependent on 'is_judging' because if 'no', then need to display options
      'operator' => 'is',
      'value' => 'No'
    );
    $volunteer_preference->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $conditional_volunteer_preference_rules
    );
    $form->fields[] = $volunteer_preference;
    $ariaFieldIds['volunteer_preference'] = $volunteer_preference->id;
    for ($i = 1; $i <= count($volunteer_preference->inputs); $i++) {
      $ariaFieldIds["volunteer_preference_option_{$i}"] = "{$volunteer_preference->id}.{$i}";
    }

    // volunteer time field
    $volunteer_time_options_array = $competition_entry[strval($competition_field_mapping['volunteer_time_options'])];
    $volunteer_time_options_array = unserialize($volunteer_time_options_array);
    $volunteer_time = new GF_Field_Checkbox();
    $volunteer_time->label = "Times Available for Volunteering";
    $volunteer_time->id = $teacher_field_mapping['volunteer_time'];
    $volunteer_time->isRequired = true;
    $volunteer_time->description = "Please check at least two times that you are
    available to volunteer during Festival weekend. If possible, please select
    every option so that you can be scheduled during the time when there are the
    most students (where the most help is needed).";
    $volunteer_time->descriptionPlacement = 'above';

    // add the volunteer time options that were input from create competition
    $volunteer_time->choices = array();
    if (is_array($volunteer_time_options_array)) {
      foreach ($volunteer_time_options_array as $single_volunteer_time_option) {
        $volunteer_time->choices[] = array(
          'text' => $single_volunteer_time_option,
          'value' => $single_volunteer_time_option,
          'isSelected' => false
        );

      }
    }

    // add the volunteer options as input to the checkbox
    $volunteer_time->inputs = array();
    $volunteer_time = self::aria_add_checkbox_input($volunteer_time,
                                                    $volunteer_time_options_array);

    // finish adding the volunteer options field into the form
    $conditional_volunteer_time_rules = array();
    $conditional_volunteer_time_rules[] = array(
      'fieldId' => $teacher_field_mapping['is_judging'], // dependent on 'is_judging' because if 'no', then need to display options
      'operator' => 'is',
      'value' => 'No'
    );
    $volunteer_time->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $conditional_volunteer_time_rules
    );
    $form->fields[] = $volunteer_time;
    $ariaFieldIds['volunteer_time'] = $volunteer_time->id;
    for ($i = 1; $i <= count($volunteer_time->inputs); $i++) {
      $ariaFieldIds["volunteer_time_option_{$i}"] = "{$volunteer_time->id}.{$i}";
    }

    // field for teacher to decide if they want to volunteer in their student's section
    $schedule_with_students = new GF_Field_Radio();
    $schedule_with_students->label = "Volunteer in Sections with Your Students";
    $schedule_with_students->id = $teacher_field_mapping['schedule_with_students'];
    $schedule_with_students->description = "Do you wish to be scheduled as a
    proctor or door monitor for a session in which one of your own students
    is playing?";
    $schedule_with_students->descriptionPlacement = 'above';
    $schedule_with_students->isRequired = true;
    $schedule_with_students->choices = array(
      array('text' => 'Yes', 'value' => 'Yes', 'isSelected' => false),
      array('text' => 'No', 'value' => 'No', 'isSelected' => false)
    );

    $schedule_with_students_rules = array();
    $schedule_with_students_rules[] = array(
      'fieldId' => $teacher_field_mapping['is_judging'], // dependent on 'is_judging' because if 'no', then need to display options
      'operator' => 'is',
      'value' => 'No'
    );
    $schedule_with_students->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $schedule_with_students_rules
    );
    $form->fields[] = $schedule_with_students;
    $ariaFieldIds['schedule_with_students'] = $schedule_with_students->id;

    // student name field
    $student_name = new GF_Field_Name();
    $student_name->label = "Student Name";
    $student_name->id = $teacher_field_mapping['student_name'];
    $student_name->isRequired = false;
    $student_name = self::aria_add_default_name_inputs($student_name);
    $form->fields[] = $student_name;
    $ariaFieldIds['student_name'] = $student_name->id;
    $ariaFieldIds['student_first_name'] = "{$student_name->id}.3";
    $ariaFieldIds['student_last_name'] = "{$student_name->id}.6";

    // student level field
    $student_level = new GF_Field_Select();
    $student_level->label = "Student Level";
    $student_level->id = $teacher_field_mapping['student_level'];
    $student_level->isRequired = false;
    $student_level->placeholder = "Select level...";
    $student_level->choices = array();
    for ($i = 1; $i <= 11; $i++) {
      $student_level->choices[] = array(
        'text' => strval($i),
        'value' => strval($i),
        'isSelected' => false
      );
    }
    $form->fields[] = $student_level;
    $ariaFieldIds['student_level'] = $student_level->id;

    // field for student's first song period
    $song_one_period = new GF_Field_Select();
    $song_one_period->label = "Song 1 Period";
    $song_one_period->id = $teacher_field_mapping['song_1_period'];
    $song_one_period->choices = array(
      array('text' => 'Baroque', 'value' => '1', 'isSelected' => false),
      array('text' => 'Classical', 'value' => '2', 'isSelected' => false),
      array('text' => 'Romantic', 'value' => '3', 'isSelected' => false),
      array('text' => 'Contemporary', 'value' => '4', 'isSelected' => false),
    );
    $song_one_period->isRequired = true;
    $song_one_period->placeholder = "Select Period...";
    $form->fields[] = $song_one_period;
    $ariaFieldIds['song_one_period'] = $song_one_period->id;

    // field for student's first song composer
    $song_one_composer = new GF_Field_Select();
    $song_one_composer->label = "Song 1 Composer";
    $song_one_composer->id = $teacher_field_mapping['song_1_composer'];
    $song_one_composer->isRequired = true;
    $song_one_composer->placeholder = "Select Composer...";
    $form->fields[] = $song_one_composer;
    $ariaFieldIds['song_one_composer'] = $song_one_composer->id;

    // field for student's first song selection
    $song_one_selection = new GF_Field_Select();
    $song_one_selection->label = "Song 1 Selection";
    $song_one_selection->id = $teacher_field_mapping['song_1_selection'];
    $song_one_selection->isRequired = true;
    $song_one_selection->placeholder = "Select Song...";
    $form->fields[] = $song_one_selection;
    $ariaFieldIds['song_one_selection'] = $song_one_selection->id;

    // !!! need to add column E (conflict resolution)
      // idk what this is or means

    // define some rules for level 11 students
    $is_11_rule = array();
    $is_11_rule[] = array(
    	'fieldId' => $teacher_field_mapping['student_level'],
    	'operator' => 'is',
    	'value' => '11'
    );

    $is_not_11_rule = array();
    $is_not_11_rule[] = array(
    	'fieldId' => $teacher_field_mapping['student_level'],
    	'operator' => 'isnot',
    	'value' => '11'
    );

    // field for student's second song period
    $song_two_period = new GF_Field_Select();
    $song_two_period->label = "Song 2 Period";
    $song_two_period->id = $teacher_field_mapping['song_2_period'];
    $song_two_period->isRequired = true;
    $song_two_period->choices = array(
      array('text' => 'Baroque', 'value' => '1', 'isSelected' => false),
      array('text' => 'Classical', 'value' => '2', 'isSelected' => false),
      array('text' => 'Romantic', 'value' => '3', 'isSelected' => false),
      array('text' => 'Contemporary', 'value' => '4', 'isSelected' => false),
    );
    $song_two_period->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );
    $song_two_period->placeholder = "Select Period...";
    $form->fields[] = $song_two_period;
    $ariaFieldIds['song_two_period'] = $song_two_period->id;

    // field for student's second song composer
    $song_two_composer = new GF_Field_Select();
    $song_two_composer->label = "Song 2 Composer";
    $song_two_composer->id = $teacher_field_mapping['song_2_composer'];
    $song_two_composer->isRequired = true;
    $song_two_composer->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );
    $song_two_composer->placeholder = "Select Composer...";
    $form->fields[] = $song_two_composer;
    $ariaFieldIds['song_two_composer'] = $song_two_composer->id;

    // student's second song selection
    $song_two_selection = new GF_Field_Select();
    $song_two_selection->label = "Song 2 Selection";
    $song_two_selection->id = $teacher_field_mapping['song_2_selection'];
    $song_two_selection->isRequired = true;
    $song_two_selection->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_not_11_rule
    );
    $song_two_selection->placeholder = "Select Song...";
    $form->fields[] = $song_two_selection;
    $ariaFieldIds['song_two_selection'] = $song_two_selection->id;

    // if the student is level 11, we need to obtain an alternate composer
    $alt_song_two_composer = new GF_Field_Text();
    $alt_song_two_composer->label = "Song 2 Composer";
    $alt_song_two_composer->id = $teacher_field_mapping['alt_song_2_composer'];
    $alt_song_two_composer->isRequired = true;
    $alt_song_two_composer->conditionalLogic = array(
    	'actionType' => 'show',
    	'logicType' => 'all',
    	'rules' => $is_11_rule
    );
    $form->fields[] = $alt_song_two_composer;
    $ariaFieldIds['alt_song_two_composer'] = $alt_song_two_composer->id;

    // if the student is level 11, we need to obtain an alternate song
    $alt_song_two_selection = new GF_Field_Text();
    $alt_song_two_selection->label = "Song 2 Piece Title";
    $alt_song_two_selection->id = $teacher_field_mapping['alt_song_2_selection'];
    $alt_song_two_selection->isRequired = true;
    $alt_song_two_selection->description = "Please be as descriptive as possible.
    If applicable, include key (D Major, F Minor, etc.), movement number (1st,
    2nd, etc.), movement description (Adante, Rondo Allegro Comodo, etc.), and
    identifying number (BWV, Opus, etc.).";
    $alt_song_two_selection->descriptionPlacement = 'above';
    $alt_song_two_selection->conditionalLogic = array(
      'actionType' => 'show',
      'logicType' => 'all',
      'rules' => $is_11_rule
    );
    $form->fields[] = $alt_song_two_selection;
    $ariaFieldIds['alt_song_two_selection'] = $alt_song_two_selection->id;

    // timing of pieces field
    $timing_of_pieces = new GF_Field_Select();
    $timing_of_pieces->label = "Combined Timing of Pieces (minutes)";
    $timing_of_pieces->description = "Please round up to the nearest minute.";
    $timing_of_pieces->descriptionPlacement = "above";
    $timing_of_pieces->id = $teacher_field_mapping['timing_of_pieces'];
    $timing_of_pieces->isRequired = true;
    for ($i = 1; $i <= 20; $i++) {
      $timing_of_pieces->choices[] = array(
        'text' => strval($i),
        'value' => strval($i),
        'isSelected' => false
      );
    }
    $form->fields[] = $timing_of_pieces;
    $ariaFieldIds['timing_of_pieces'] = $timing_of_pieces->id;

    // student division field
    $master_class_registration_option = $competition_entry[strval($competition_field_mapping['master_class_registration_option'])];
    $student_division = new GF_Field_Radio();
    $student_division->label = "Student Division";
    $student_division->id = $teacher_field_mapping['student_division'];
    $student_division->isRequired = true;
    $student_division->choices = array(
      array('text' => 'Traditional', 'value' => 'Traditional', 'isSelected' => false),
      array('text' => 'Non-Competitive', 'value' => 'Non-Competitive', 'isSelected' => false)
    );
    if ($master_class_registration_option == "Yes") {
        $student_division->choices[] = array('text' => 'Master Class', 'value' => 'Master Class', 'isSelected' => false);
    }
    $form->fields[] = $student_division;
    $ariaFieldIds['student_division'] = $student_division->id;

    // field for student's theory score
    $theory_score = new GF_Field_Number();
    $theory_score->label = "Theory Score (percentage)";
    $theory_score->id = $teacher_field_mapping['theory_score'];
    $theory_score->isRequired = true;
    $theory_score->numberFormat = "decimal_dot";
    $theory_score->rangeMin = 70;
    $theory_score->rangeMax = 100;
    $form->fields[] = $theory_score;
    $ariaFieldIds['theory_score'] = $theory_score->id;

    // field for student's alternate theory
    $alternate_theory = new GF_Field_Checkbox();
    $alternate_theory->label = "Check if alternate theory exam was completed.";
    $alternate_theory->id = $teacher_field_mapping['alternate_theory'];
    $alternate_theory->isRequired = false;
    $alternate_theory->choices = array(
      array(
        'text' => 'Alternate theory exam completed',
        'value' => 'Alternate theory exam completed',
        'isSelected' => false
      )
    );
    $alternate_theory->inputs = array();
    $alternate_theory = self::aria_add_checkbox_input($alternate_theory, 'Alternate theory exam completed');
    $form->fields[] = $alternate_theory;
    $ariaFieldIds['alternate_theory'] = $alternate_theory->id;

    // custom submission message to inform teacher registration was successful
    $successful_submission_message = "Congratulations! You have just successfully registered your student.";
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

    // create the new form and publish to the WordPress dashboard
    $teacher_form_array = $form->createFormArray();
    $teacher_form_array['isTeacherPublicForm'] = true;
    $teacher_form_array['ariaFieldIds'] = $ariaFieldIds;
    $result = GFAPI::add_form($teacher_form_array);
    if (is_wp_error($result)) {
      wp_die($result->get_error_message());
    }

    return $result;
  }

  /**
   * This function will perform validation on the input obtained from the form
   * used to register students for an NNMTA festival.
   *
   * Various validation checks will be performed to ensure that the data input
   * into the student registration form is accurate and valid.
   *
   * @param   $validation_result  Array   Contains the validation result and the current Form Object.
   *
   * @return  $validation_result  Array   Contains the validation result and the current Form Object.
   *
   * @since 2.0.0
   * @author KREW
   */
   public static function aria_teacher_form_validation($validation_result) {
     // obtain the form object and the field mapping for this object
     $form = $validation_result['form'];
     $field_mapping = ARIA_API::aria_teacher_field_id_array();

     // only perform form validation if it's the student registration form
     if (!array_key_exists('isTeacherPublicForm', $form)
         || !$form['isTeacherPublicForm']) {
           return $validation_result;
     }

     $validation_result['form'] = $form;
     return $validation_result;
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
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_create_student_form($competition_entry,
                                                   $teacher_names_and_hashes) {
    // obtain the field mapping arrays for both competition creation and student registration
    $create_comp_field_mapping = ARIA_API::aria_competition_field_id_array();
    $student_field_mapping = ARIA_API::aria_student_field_id_array();

    // obtain the name of the competition and initialize a new form
    $competition_name = $competition_entry[strval($create_comp_field_mapping['name'])];
    $student_form = new GF_Form("{$competition_name} Student Registration", "");

    // add a description to the student form
    $student_form->description = "Welcome! Use this form to submit your child's
    information for the upcoming NNMTA music festival. Once this form has been
    submitted, your child's teacher will receive an email with a link that they
    will use to complete the registration process. Within a few weeks, you will
    receive an email informing you when/where your child has been scheduled to
    perform.";

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
    appropriate capitalization. <b>The text you submit here will be used on all
    competition documents and awards.</b>";
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
    $chairman_email = $competition_entry[strval($create_comp_field_mapping['chairman_email'])];
    $teacher_name = new GF_Field_Select();
    $teacher_name->label = "Teacher Name";
    $teacher_name->id = $student_field_mapping['teacher_name'];
    $teacher_name->isRequired = true;
    $teacher_name->description = "Please select your teacher's name from the
    drop-down below. If your teacher is not listed, please contact the festival
    chairman at $chairman_email.";
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
    $available_festival_days->id = $student_field_mapping['festival_availability'];
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
    $ariaFieldIds['festival_availability'] = $available_festival_days->id;
    for ($i = 1; $i <= count($available_festival_days->inputs); $i++) {
      $ariaFieldIds["available_festival_days_option_{$i}"] = "{$available_festival_days->id}.{$i}";
    }

    // create student's preferred command performance field
    $command_performance_options = $competition_entry[strval($create_comp_field_mapping['command_performance_options'])];
    $command_performance_options = unserialize($command_performance_options);
    $preferred_command_performance = new GF_Field_Radio();
    $preferred_command_performance->label = "Preferred Command Performance Time";
    $preferred_command_performance->id = $student_field_mapping['command_performance_availability'];
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
    if (is_array($command_performance_options)) {
      foreach ($command_performance_options as $command_time) {
        $preferred_command_performance->choices[] = array('text' => $command_time,
                                                          'value' => $command_time,
                                                          'isSelected' => false);
      }
    }

    $student_form->fields[] = $preferred_command_performance;
    $ariaFieldIds['command_performance_availability'] = $preferred_command_performance->id;
    for ($i = 1; $i <= count($preferred_command_performance->inputs); $i++) {
      $ariaFieldIds["preferred_command_performance_option_{$i}"] = "{$preferred_command_performance->id}.{$i}";
    }

    // store the preferred command performance in array of field id's

    // hidden field for student's festival level
    $student_level = new GF_Field_Select();
    $student_level->label = "Student Level";
    $student_level->id = $student_field_mapping['student_level'];
    $student_level->isRequired = false;
    $student_level->choices = array();
    for ($i = 1; $i <= 11; $i++) {
      $student_level->choices[] = array(
        'text' => strval($i),
        'value' => strval($i),
        'isSelected' => false
      );
    }

    $student_level->description = "Please enter your child's festival level.
    If you do not know this value, please do not submit this form until your child
    contacts his/her instructor and can verify this value.";
    $student_level->descriptionPlacement = 'above';
    $student_level->hidden = true;
    $student_form->fields[] = $student_level;
    $ariaFieldIds['student_level'] = $student_level->id;

    // student level pricing field
    $level_pricing = new GF_Field_Product();
    $level_pricing->label = "Student Level";
    $level_pricing->id = $student_field_mapping['level_pricing'];
    $level_pricing->isRequired = true;
    $level_pricing->size = "small";
    $level_pricing->inputs = null;
    $level_pricing->inputType = "select";
    $level_pricing->enablePrice = true;
    $level_pricing->basePrice = "$1.00";
    $level_pricing->disableQuantity = true;
    $level_pricing->displayAllCategories = false;
    $level_pricing->description = "Please enter your child's festival level.
    If you do not know this value, please do not submit this form until your child
    contacts his/her instructor and can verify this value.";
    $level_pricing->descriptionPlacement = 'above';

    // add the prices to the student level pricing field
    $level_pricing->choices = array();
    for ($i = 1; $i <= 11; $i++) {
      $price = $competition_entry[$create_comp_field_mapping['level_'. $i .'_price']];
      if ($price != 0) {
        $level_pricing->choices[] = array('text' => strval($i),
                                          'value' => strval($i),
                                          'isSelected' => false,
                                          'price' => $price);
      }
    }
    $student_form->fields[] = $level_pricing;

    // store the student's level in array of field id's
    $ariaFieldIds['level_pricing'] = $level_pricing->id;

    // create the compliance field checkbox for parents
    $compliance_statement = new GF_Field_Checkbox();
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
      array(
        'text' => 'I have read and agree with the above statement.',
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
    $successful_submission_message = "Congratulations! You have just successfully registered your child.";
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
    $paypal_email_address = $competition_entry[strval($create_comp_field_mapping['paypal_email'])];
    $feed_meta = array(
      'feedName' => 'Student Registration Feed',
      'paypalEmail' => $paypal_email_address,
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

  /**
   * This function will perform validation on the input obtained from the form
   * used to register students for an NNMTA festival.
   *
   * Various validation checks will be performed to ensure that the data input
   * into the student registration form is accurate and valid.
   *
   * @param   $validation_result  Array   Contains the validation result and the current Form Object.
   *
   * @return  $validation_result  Array   Contains the validation result and the current Form Object.
   *
   * @since 2.0.0
   * @author KREW
   */
  public static function aria_student_form_validation($validation_result) {
    // obtain the form object and the field mapping for this object
    $form = $validation_result['form'];
    $field_mapping = ARIA_API::aria_student_field_id_array();

    // only perform form validation if it's the student registration form
    if (!array_key_exists('isStudentPublicForm', $form)
        || !$form['isStudentPublicForm']) {
          return $validation_result;
    }

    // obtain the input for parent email and the confirmation email
    $parent_email = "input_" . strval($field_mapping['parent_email']);
    $parent_email_confirmation = "input_" . strval($field_mapping['parent_email_confirmation']);
    $parent_email = rgpost($parent_email);
    $parent_email_confirmation = rgpost($parent_email_confirmation);

    // compare the parent email with the parent confirmation email
    if (strcmp($parent_email, $parent_email_confirmation) !== 0) {
      $validation_result['is_valid'] = false;
      foreach ($form['fields'] as &$field) {
        // parent confirmation email field
        if ($field->id == strval($field_mapping['parent_email_confirmation'])) {
          $field->failed_validation = true;
          $field->validation_message = "This email must match the email in the
          field titled 'Parent Email'.";
        }
      }
    }

    $validation_result['form'] = $form;
    return $validation_result;
  }
}
