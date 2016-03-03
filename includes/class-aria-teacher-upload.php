<?php

/**
 * The file that provides teacher uploading functionality.
 *
 * A class definition that includes functions that allow the festival chairman
 * to upload music teachers to a specific music competition.
 *
  * @link       http://wesleykepke.github.io/ARIA/
  * @since      1.0.0
  *
  * @package    ARIA
  * @subpackage ARIA/includes
 */

require_once("class-aria-api.php");
require_once("class-aria-create-competition.php");

/**
 * The teacher upload class.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
 */
class ARIA_Teacher {

  /**
   * This function defines an associative array used in the teacher upload form.
   *
   * This function returns an array that maps all of the names of the fields in
   * the teacher upload form to a unique integer so that they can be referenced.
   * Moreover, this array helps prevent the case where the names of these fields
   * are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_teacher_upload_field_id_array() {
    /*
    CAUTION, This array is used as a source of truth. Changing these values may
    result in catastrophic failure. If you do not want to feel the bern,
    consult an aria developer before making changes to this portion of code.

    This is super important and can't be emphasized enough! These values must
    be changed if the corresponding form is modified. Use the function
    json_encode($entry) to view the JSON and make sure it matches what this
    function returns.

    Last modified by wes on 2/27/2016 at 10:00 PM.
    */
    return array (
      'csv_upload' => 1,
      'teacher_name' => 2,
      'teacher_first_name' => 2.3,
      'teacher_last_name' => 2.6,
      'teacher_email' => 3
    );
  }

  /**
   * This function will create the form that is used to upload teachers.
   *
   * In order to have teachers in the teacher-master form of a specific
   * competition, the festival chairman needs functionality for adding
   * teachers. This function will create the form that is used by the festival
   * chairman to upload a csv file of all of the teachers that will be
   * participating in a competition (if it does not already exist).
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_teacher_upload_form() {
    // check if form already exists
    if (ARIA_API::aria_get_teacher_upload_form_id() !== -1) {
      return;
    }

    $form_name = TEACHER_UPLOAD_FORM_NAME;
    $form = new GF_FORM($form_name, "");
    $form->description = "Use the file upload field to upload multiple";
    $form->description .= " teachers or add a single teacher by filling out";
    $form->description .= " the required information below";
    $field_mapping = self::aria_teacher_upload_field_id_array();

    // CSV file upload for teachers
    $csv_file_upload = new GF_Field_FileUpload();
    $csv_file_upload->label = CSV_TEACHER_FIELD_NAME;
    $csv_file_upload->id = $field_mapping['csv_upload'];
    $csv_file_upload->isRequired = false;
    $form->fields[] = $csv_file_upload;

    // Option for entering a single teacher
    $teacher_name_field = new GF_Field_Name();
    $teacher_name_field->label = "Teacher Name";
    $teacher_name_field->id = $field_mapping['teacher_name'];
    $teacher_name_field->isRequired = false;
    $teacher_name_field = ARIA_Create_Competition::aria_add_default_name_inputs($teacher_name_field);
    $form->fields[] = $teacher_name_field;

    $teacher_email_field = new GF_Field_Email();
    $teacher_email_field->label = "Teacher Email";
    $teacher_email_field->id = $field_mapping['teacher_email'];
    $teacher_email_field->isRequired = false;
    $form->fields[] = $teacher_email_field;

    // Identify form as a teacher uploading form
    $form_array = $form->createFormArray();
    $form_array['isTeacherUploadForm'] = true;

    // Add form to dashboard
    $new_form_id = GFAPI::add_form($form_array);
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }
    else {
      ARIA_API::aria_publish_form(TEACHER_UPLOAD_FORM_NAME, $new_form_id);
    }
  }

  /**
   *
   * This function will add teachers to a specified music competition.
   *
   * This function will extract the incoming csv file containing teacher
   * information and call another function that is responsible for uploading
   * this information to the corresponding teacher-master form.
   *
   * @param Entry Object $entry The entry object from the upload form.
   * @param Form Object $form The form object used to upload data.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_upload_teachers($entry, $form) {
    // only perform processing is the teacher upload form was used
    if (!array_key_exists('isTeacherUploadForm', $form)
        || !$form['isTeacherUploadForm']) {
      return;
    }

    $field_mapping = ARIA_Create_Competition::aria_competition_field_id_array();

    // if a csv file was given, upload the content
    if (!empty($entry[strval($field_mapping['competition_teacher_csv_upload'])])) {
      self::aria_upload_from_csv($entry, $form);
    }

    // if a teacher's information was entered by hand, add it
/*
    $teacher_given = (!empty($entry[strval($field_mapping['teacher_first_name'])])) &&
      (!empty($entry[strval($field_mapping['teacher_last_name'])])) &&
      (!empty($entry[strval($field_mapping['teacher_email'])]));
    if ($teacher_given) {
      wp_die('all attributes given');
    }
      wp_die(print_r($teacher_given) . print_r($csv_given)); */
  }

  /**
   *
   *
   * This function will parse the contents of the csv file that is used to
   * store teacher data and upload this information to the corresponding
   * teacher-master form for a specific competition.
   */
  private static function aria_upload_from_csv($entry, $form) {
    // get the file path of the csv file
    $csv_file_path = ARIA_API::aria_get_teacher_csv_file_path($entry, $form);
    wp_die('csv file path: ' . $csv_file_path);

    // check incoming object
    wp_die('incoming entry object: ' . json_encode($entry));

    // check the related forms to ensure this data gets put in the correct
    // teacher master form
    wp_die('checking related forms: ' . print_r($form));

    // upload all of the teachers in the file
    $all_teachers = array();
    if (($file_ptr = fopen($csv_file_path, "r")) !== FALSE) {
      while (($single_teacher_data = fgetcsv($file_ptr, 1000, ",")) !== FALSE) {
        $single_teacher = array();
        for ($i = 1; $i <= count($single_teacher_data); $i++) {
				  $single_teacher[(string) $i] = $single_teacher_data[$i - 1];
				}
        $all_teachers[] = $single_teacher;
        unset($single_teacher);
      }

      // hash all of the teachers names

      // add all data to the corresponding teacher-master form
    }

    // remove the uploaded file from the current WP directory
    unlink($csv_file_path);
  }

  /**
   * TODO? Implement code to upload a single teacher with a seperate form?
   */
  private static function aria_upload_from_form($entry, $form) {

  }
}
