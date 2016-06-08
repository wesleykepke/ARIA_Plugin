<?php

/**
 * The file that defines the functionality that should take place before and
 * after particular forms are submitted by students and teachers.
 *
 * @link       http://wesleykepke.github.io/ARIA_Plugin/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

// Required files
require_once("class-aria-api.php");
require_once("class-aria-registration-handler.php");
require_once("class-aria-create-competition.php");

/**
 * The aria form hooks class.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
 */
class ARIA_Form_Hooks {

  /**
   * This function will perform actions on student forms prior to their initial
   * rendering.
   *
   * This function is responsible for taking all of the entries that exist
   * in the associated competition's teacher master form and use those entries
   * to pre-populate the teacher drop-down menu.
   *
   * @param   $form   Form Object   The current form object.
   * @param   $is_ajax  Bool  Specifies if the form is submitted via AJAX.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_before_student_submission($form, $is_ajax) {
    // only perform processing if it's a student public form
    if (!array_key_exists('isStudentPublicForm', $form)
        || !$form['isStudentPublicForm']) {
          return;
    }

    $teacher_master_field_mapping = ARIA_API::aria_master_teacher_field_id_array();
    $related_forms = $form['aria_relations'];

    // get all of the teachers and sort by last name
    $search = array();
    $sorting = array('key' => strval($teacher_master_field_mapping['last_name']),
                     'direction' => 'ASC',
                     'is_numeric' => false);
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $teacher_entries = GFAPI::get_entries($related_forms['teacher_master_form_id'],
                                          $search, $sorting, $paging, $total_count);

    // format the teacher names so they can be used in the dropdown
    $formatted_teacher_names = array();
    foreach ($teacher_entries as $entry) {
      $single_teacher = array(
        'text' => $entry[strval($teacher_master_field_mapping['first_name'])] . " " . $entry[strval($teacher_master_field_mapping['last_name'])],
        'value' => serialize(array(
          $entry[strval($teacher_master_field_mapping['first_name'])],
          $entry[strval($teacher_master_field_mapping['last_name'])],
          $entry[strval($teacher_master_field_mapping['hash'])]
        )),
        'isSelected' => false
      );
      $formatted_teacher_names[] = $single_teacher;
      unset($single_teacher);
    }

    // update the current form with the previously formatted teachers
    $student_field_mapping = ARIA_API::aria_student_field_id_array();
    $search_field = $student_field_mapping['teacher_name'];
    $name_field = ARIA_API::aria_find_field_by_id($form['fields'], $search_field);
    $form['fields'][$name_field]->choices = $formatted_teacher_names;
  }

  /**
   * This function will be the hook that is called after a student submits their
   * information for a new music competition. This function will take all of the
   * information that the student submitted and update corresponding data in the
   * student form, the student master form, and the teacher master form.
   *
   * @param		$entry	GF Entry Object		The entry used to generate the PayPal transaction.
   * @param		$feed		GF Feed Object		The PayPal Feed configuration data used to generate the order.
   * @param   $transaction_id   string  The transaction ID returned by PayPal.
   * @param   $amount   float   The amount of the transaction returned by PayPal.
   *
   * @since 1.0.0
   * @author KREW
   */
  //public static function aria_after_student_submission($entry, $feed, $transaction_id, $amount) {
  public static function aria_after_student_submission($entry, $form) {
    // obtain the form object and the other related forms
    //$form_id = $entry['form_id'];
    //$form = GFAPI::get_form($form_id);
    //$related_forms = $form['aria_relations'];

    // only perform processing if it's a student form
    if (!array_key_exists('isStudentPublicForm', $form)
        || !$form['isStudentPublicForm']) {
          return;
    }

    $related_forms = $form['aria_relations'];

    // initialize various field mapping arrays
    $student_fields = ARIA_API::aria_student_field_id_array();
    $teacher_master_fields = ARIA_API::aria_master_teacher_field_id_array();
    $student_master_fields = ARIA_API::aria_master_student_field_id_array();

    // obtain the teacher's name and hash
    $teacher_name_and_hash = unserialize($entry[strval($student_fields["teacher_name"])]);
    $teacher_name = $teacher_name_and_hash[0] . ' ' . $teacher_name_and_hash[1];
    $teacher_hash = $teacher_name_and_hash[2];

    // create the hash for the student (student name and entry date)
    $student_first_name = trim($entry[strval($student_fields["student_first_name"])]);
    $student_last_name = trim($entry[strval($student_fields["student_last_name"])]);
    $student_name = $student_first_name . " " . $student_last_name;
    $student_name_and_entry_date = $student_name . $entry["date_created"];
    $student_hash = hash("md5", $student_name_and_entry_date);

    // search through the teacher master form to see if the teacher has an entry made
    $teacher_entry = ARIA_Registration_Handler::aria_find_teacher_entry($related_forms['teacher_master_form_id'],
                                                                        $teacher_hash);

    // if a teacher was located, update that teacher's array of students
    if ($teacher_entry !== false) {
      // obtain that teacher's array of students
      $students = unserialize($teacher_entry[strval($teacher_master_fields["students"])]);

      // if no students have been added yet, initialize the array
      if (!is_array($students)) {
        $students = array();
      }

      // add the newly registered student to the teacher's list of student hashes
      $students[] = $student_hash;
      $teacher_entry[strval($teacher_master_fields["students"])] = serialize($students);

      // update the teacher entry with the new student addition
      $result = GFAPI::update_entry($teacher_entry);
      if (is_wp_error($result)) {
        wp_die("Line number: " . __LINE__ . ": " . $result->get_error_message());
      }
    }

    // if that teacher was not located, create a new entry for that teacher
    else {
      // THIS SHOULD NEVER HAPPEN

      // process the teacher's name
      $teacher_name = explode(" ", $teacher_name);
      foreach ($teacher_name as &$word) {
        $word = trim($word);
      }

      // add default attributes for the teacher
      $new_teacher_entry = array();
      $new_teacher_entry[] = array(
        strval($teacher_master_fields["first_name"]) => $teacher_name[0],
        strval($teacher_master_fields["last_name"]) => count($teacher_name) > 1 ? $teacher_name[1] : '',
        strval($teacher_master_fields["email"]) => null,
        strval($teacher_master_fields["phone"]) => null,
        strval($teacher_master_fields["hash"]) => $teacher_hash,
        strval($teacher_master_fields["students"]) => serialize(array($student_hash)),
        strval($teacher_master_fields["is_judging"]) => null,
        strval($teacher_master_fields["volunteer_preference"]) => null,
        strval($teacher_master_fields["volunteer_time"]) => null,
        strval($teacher_master_fields["schedule_with_students"]) => null,
      );

      // add the new teacher to the associated teacher master form
      $result = GFAPI::add_entries($new_teacher_entry, $related_forms['teacher_master_form_id']);
      if (is_wp_error($result)) {
        wp_die("Line number: " . __LINE__ . ": " . $result->get_error_message());
      }
    }

    // make a new student master entry with the student hash
    $new_student_master_entry = array();
    $stripped_student_level = explode("|", $entry[strval($student_fields['level_pricing'])]);
    $stripped_student_level = $stripped_student_level[0];
    $new_student_master_entry[] = array(
      strval($student_master_fields["parent_name"]) => null,
      strval($student_master_fields["parent_first_name"]) => $entry[strval($student_fields["parent_first_name"])],
      strval($student_master_fields["parent_last_name"]) => $entry[strval($student_fields["parent_last_name"])],
      strval($student_master_fields["parent_email"]) => $entry[strval($student_fields["parent_email"])],
      strval($student_master_fields["student_name"]) => null,
      strval($student_master_fields["student_first_name"]) => $entry[strval($student_fields["student_first_name"])],
      strval($student_master_fields["student_last_name"]) => $entry[strval($student_fields["student_last_name"])],
      strval($student_master_fields["student_birthday"]) => $entry[strval($student_fields["student_birthday"])],
      strval($student_master_fields["student_level"]) => $stripped_student_level,
      strval($student_master_fields["teacher_name"]) => $entry[strval($student_fields["teacher_name"])],
      strval($student_master_fields["festival_availability"]) => $entry[strval($student_fields["festival_availability"])],
      strval($student_master_fields["command_performance_availability"]) => $entry[strval($student_fields["command_performance_availability"])],
      strval($student_master_fields["song_1_period"]) => null,
      strval($student_master_fields["song_1_composer"]) => null,
      strval($student_master_fields["song_1_selection"]) => null,
      strval($student_master_fields["song_2_period"]) => null,
      strval($student_master_fields["song_2_composer"]) => null,
      strval($student_master_fields["song_2_selection"]) => null,
      strval($student_master_fields["theory_score"]) => null,
      strval($student_master_fields["alternate_theory"]) => null,
      strval($student_master_fields["competition_format"]) => null,
      strval($student_master_fields["timing_of_pieces"]) => null,
      strval($student_master_fields["student_hash"]) => $student_hash
    );

    // adjust the student level in the entry object so that it comes from the pricing field
    $entry[strval($student_fields['level_pricing'])] = $stripped_student_level;

    // add the newly created student to the competition master form
    $student_result = GFAPI::add_entries($new_student_master_entry, $related_forms['student_master_form_id']);
    if (is_wp_error($student_result)) {
      wp_die("Line number: " . __LINE__ . ": " . $student_result->get_error_message());
    }

    // determine how many students have registered so far
    $search_criteria = array();
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $entries = GFAPI::get_entries($related_forms['student_master_form_id'],
                                  $search_criteria, $sorting, $paging, $total_count);

    // consolidate information for emails
    $email_info = array();
    $email_info['teacher_hash'] = $teacher_hash;
    $email_info['teacher_name'] = $teacher_name;
    $email_info['teacher_email'] = $teacher_entry[strval($teacher_master_fields["email"])];
    $email_info['festival_chairman_email'] = $related_forms["festival_chairman_email"];
    $email_info['parent_email'] = $entry[strval($student_fields["parent_email"])];
    $email_info['teacher_url'] = $related_forms["teacher_public_form_url"];
    $email_info['student_hash'] = $student_hash;
    $email_info['student_name'] = $student_name;
    $email_info['parent_name'] = $entry[strval($student_fields["parent_first_name"])] .
    " " . $entry[strval($student_fields["parent_last_name"])];
    $comp_name = strpos($form['title'], 'Student Registration');
    $comp_name = substr($form['title'], 0, $comp_name - 1);
    $email_info['competition_name'] = $comp_name;
    $email_info['num_participants'] = count($entries);

    // check to see if a notification email was included during competition creation
    if (!is_null($related_forms["notification_email"])) {
      $email_info['notification_email'] = $related_forms["notification_email"];
    }
    else {
      $email_info['notification_email'] = null;
    }

    // send emails to various parties (parents, teachers, festival chairman)
    ARIA_Registration_Handler::aria_send_registration_emails($email_info);
  }

  /**
   * This function will perform actions on teacher forms prior to their initial
   * rendering.
   *
   * This function is responsible for taking all of the entries that exist
   * in the associated competition's teacher master form and use those entries
   * to pre-populate the drop-down menu.
   *
   * @param   $form   Form Object   The current form object.
   * @param   $is_ajax  Bool  Specifies if the form is submitted via AJAX.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_before_teacher_render($form, $is_ajax) {
    // only perform processing if it's a teacher form
    if (!array_key_exists('isTeacherPublicForm', $form)
        || !$form['isTeacherPublicForm']) {
        return $form;
    }

    // get the query variables from the link
    $student_hash = get_query_var("student_hash", false);
    $teacher_hash = get_query_var("teacher_hash", false);
    $error = "You cannot access this form. Check your email to get the correct link to access this form correctly.";

    // if the query variables don't exist, prompt user with error message
    if (!$student_hash || !$teacher_hash) {
      wp_die($error);
    }

    // Get the related forms of the form
    $related_forms = $form['aria_relations'];

    // check if the variables exist as a teacher-student combination
    if (!ARIA_Registration_Handler::aria_check_student_teacher_relationship($related_forms, $student_hash, $teacher_hash)) {
      wp_die($error);
    }

    // do form prepopulation
    $teacher_prepopulation_values = ARIA_Registration_Handler::aria_get_teacher_pre_populate($related_forms, $teacher_hash);
    $student_prepopulation_values = ARIA_Registration_Handler::aria_get_student_pre_populate($related_forms, $student_hash);
    $prepopulated_form = ARIA_Registration_Handler::aria_prepopulate_form($form,
                                                                          $teacher_prepopulation_values,
                                                                          $student_prepopulation_values);
    return $prepopulated_form;
  }

  /**
   * This function will be the hook that is called after a teacher submits
   * information for a particular student.
   *
   * This function will take all of the information that the teacher submitted
   * and update corresponding data in the teacher form, the student master form,
   * and the teacher master form.
   *
   * @param		$entry	GF Entry Object		The entry that is returned after form submission.
   * @param		$form		GF Forms Object		The form this function is attached to.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_after_teacher_submission($entry, $form) {
    // only perform processing if it's a teacher form
    if (!array_key_exists('isTeacherPublicForm', $form)
        || !$form['isTeacherPublicForm']) {
          return;
    }

    // obtain various information
    $student_master_field_ids = ARIA_API::aria_master_student_field_id_array();
    $teacher_master_field_ids = ARIA_API::aria_master_teacher_field_id_array();
    $teacher_public_field_ids = ARIA_API::aria_teacher_field_id_array();
    $related_forms = $form['aria_relations'];
    $student_hash = get_query_var("student_hash", false);
    $teacher_hash = get_query_var("teacher_hash", false);

    // locate the teacher entry in the teacher master
    $teacher_master_entry = ARIA_Registration_Handler::aria_find_teacher_entry($related_forms['teacher_master_form_id'],
                                                                               $teacher_hash);

    // if the teacher doesn't exist, throw an error message
    if ($teacher_master_entry === false) {
      wp_die("Error: aria_after_teacher_submission() could not locate the specified teacher.");
    }

    // if the teacher does exist, update the teacher master with the new information
    $teacher_master_entry[strval($teacher_master_field_ids['first_name'])] =
      $entry[strval($teacher_public_field_ids['first_name'])];
    $teacher_master_entry[strval($teacher_master_field_ids['last_name'])] =
      $entry[strval($teacher_public_field_ids['last_name'])];
    $teacher_master_entry[strval($teacher_master_field_ids['email'])] =
      $entry[strval($teacher_public_field_ids['email'])];
    $teacher_master_entry[strval($teacher_master_field_ids['phone'])] =
      $entry[strval($teacher_public_field_ids['phone'])];
    $teacher_master_entry[strval($teacher_master_field_ids['is_judging'])] =
      $entry[strval($teacher_public_field_ids['is_judging'])];
    $teacher_master_entry[strval($teacher_master_field_ids['schedule_with_students'])] =
      $entry[strval($teacher_public_field_ids['schedule_with_students'])];

    // handle the volunteer preference field
    $volunteer_pref_field = ARIA_Registration_Handler::aria_find_field_by_id($form['fields'],
                                                                             $teacher_public_field_ids['volunteer_preference']);
    for ($i = 1; $i <= count($form['fields'][$volunteer_pref_field]['choices']); $i++) {
      // check to see if that volunteer preference field was set
      if (isset($entry[strval($teacher_public_field_ids['volunteer_preference']) . '.' . strval($i)])) {
        $teacher_master_entry[strval($teacher_master_field_ids['volunteer_preference']) . '.' . strval($i)] =
          $entry[strval($teacher_public_field_ids['volunteer_preference']) . '.' . strval($i)];
      }
      else {
        $teacher_master_entry[strval($teacher_master_field_ids['volunteer_preference']) . '.' . strval($i)] =
          null;
      }
    }

    // handle volunteer time field
    $volunteer_time_field = ARIA_Registration_Handler::aria_find_field_by_id($form['fields'],
                                                                             $teacher_public_field_ids['volunteer_time']);
    for ($i = 1; $i <= count($form['fields'][$volunteer_time_field]['choices']); $i++) {
      // check to see if that volunteer time field was set
      if (isset($entry[strval($teacher_public_field_ids['volunteer_time']) . '.' . strval($i)])) {
        $teacher_master_entry[strval($teacher_master_field_ids['volunteer_time']) . '.' . strval($i)] =
          $entry[strval($teacher_public_field_ids['volunteer_time']) . '.' . strval($i)];
      }
      else {
        $teacher_master_entry[strval($teacher_master_field_ids['volunteer_time']) . '.' . strval($i)] =
          null;
      }
    }

    // update the teacher master form with the new information
    $result = GFAPI::update_entry($teacher_master_entry);
		if (is_wp_error($result)) {
			wp_die($result->get_error_message());
		}

    // locate the student entry in the student master.
    $student_master_entry = ARIA_Registration_Handler::aria_find_student_entry($related_forms['student_master_form_id'],
                                                                               $student_hash);
/*
    echo "Displaying student master entry: <br>";
    echo "Displaying level stored in student master entry: " .
    intval($student_master_entry[strval($student_master_field_ids['student_level'])])
    . "<br>";

    if (intval($student_master_entry[strval($student_master_field_ids['student_level'])]) != 11) {
      echo "This value is somehow not 11. <br>";
    }
    else {
      echo "This value is 11. <br>";
    }

    echo "Displaying incoming student entry.<br>";
    echo print_r($entry);
    echo "Displaying student master entry.<br>";

    wp_die(print_r($student_master_entry));
*/

    // if the student doesn't exist, throw an error message
    if ($student_master_entry === false) {
      wp_die("Error: aria_after_teacher_submission() could not locate the specified student.");
    }

    // if the student does exist, update the student master with the new information
    $student_master_entry[strval($student_master_field_ids['song_1_period'])] =
      $entry[strval($teacher_public_field_ids['song_1_period'])];
    $student_master_entry[strval($student_master_field_ids['song_1_composer'])] =
      $entry[strval($teacher_public_field_ids['song_1_composer'])];
    $student_master_entry[strval($student_master_field_ids['song_1_selection'])] =
      $entry[strval($teacher_public_field_ids['song_1_selection'])];
    $student_master_entry[strval($student_master_field_ids['theory_score'])] =
      $entry[strval($teacher_public_field_ids['theory_score'])];
    $student_master_entry[strval($student_master_field_ids['competition_format'])] =
      $entry[strval($teacher_public_field_ids['student_division'])];
    $student_master_entry[strval($student_master_field_ids['timing_of_pieces'])] =
      $entry[strval($teacher_public_field_ids['timing_of_pieces'])];

    // if student level != 11
    if (intval($student_master_entry[strval($student_master_field_ids['student_level'])]) != 11) {
      $student_master_entry[strval($student_master_field_ids['song_2_period'])] =
        $entry[strval($teacher_public_field_ids['song_2_period'])];
      $student_master_entry[strval($student_master_field_ids['song_2_composer'])] =
        $entry[strval($teacher_public_field_ids['song_2_composer'])];
      $student_master_entry[strval($student_master_field_ids['song_2_selection'])] =
        $entry[strval($teacher_public_field_ids['song_2_selection'])];
    }
    else {
      // if student level == 11
      $student_master_entry[strval($student_master_field_ids['song_2_composer'])] =
        $entry[strval($teacher_public_field_ids['alt_song_2_composer'])];
      $student_master_entry[strval($student_master_field_ids['song_2_selection'])] =
        $entry[strval($teacher_public_field_ids['alt_song_2_selection'])];
    }

    // handle the alternate theory field
    $alt_theory_field = ARIA_Registration_Handler::aria_find_field_by_id($form['fields'],
                                                                         $teacher_public_field_ids['alternate_theory']);
    for ($i = 1; $i <= count($form['fields'][$alt_theory_field]['choices']); $i++) {
      if (isset($entry[strval($teacher_public_field_ids['alternate_theory']) . '.' . strval($i)])) {
        $student_master_entry[strval($student_master_field_ids['alternate_theory']) . '.' . strval($i)] =
          $entry[strval($teacher_public_field_ids['alternate_theory']) . '.' . strval($i)];
      }
      else {
        $student_master_entry[strval($student_master_field_ids['alternate_theory']) . '.' . strval($i)] =
          null;
      }
    }

    // send an email to the teacher acknowledging successful registration
    $email_info = array();
    $teacher_name_for_email = unserialize($student_master_entry[strval($student_master_field_ids['teacher_name'])]);
    $email_info['teacher_name'] = $teacher_name_for_email[0] . ' ' . $teacher_name_for_email[1];
    $email_info['teacher_email'] = $teacher_master_entry[strval($teacher_master_field_ids['email'])];
    $email_info['student_name'] = $student_master_entry[strval($student_master_field_ids['student_first_name'])] .
      ' ' . $student_master_entry[strval($student_master_field_ids['student_last_name'])];
    $email_info['competition_name'] = $related_forms['festival_name'];
    $email_info['festival_chairman_email'] = $related_forms['festival_chairman_email'];
    ARIA_Registration_Handler::aria_after_teacher_submission_email($email_info);

    // update the student master form with the new information
    $result = GFAPI::update_entry($student_master_entry);
		if (is_wp_error($result)) {
			wp_die($result->get_error_message());
		}
  }

  /**
   * This function will expose the new, custom query variables to WP_Query.
   *
   * In order for ARIA's query hash method to work, specific query vars (the
   * query vars that will be added to URLs) need to be added to the public
   * query variables that are available to WP_Query. This function is
   * responsible for adding these query vars to the $query_vars property of
   * WP_Query.
   *
   * @param $vars   Array   The array of query vars passed via the filter
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_add_query_vars_filter($vars) {
    $vars[] = "teacher_hash";
    $vars[] = "student_hash";
    return $vars;
  }

  /**
   * This function does something idk
   *
   * SOmething idk
   *
   * @param   $form   Form Object   The form object for the entry.
   * @param   $entry_id   Int   The entry ID.
   * @param   $original_entry   Entry Object  The entry before being updated.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_student_master_post_update_entry($form, $entry_id, $original_entry) {
    // only perform processing if it's a student master form
    if (!array_key_exists('isStudentMasterForm', $form)
        || !$form['isStudentMasterForm']) {
          return;
    }

    // acquire basic information about the associated entry and form
    $entry = GFAPI::get_entry($entry_id);
    $related_forms = $form['aria_relations'];
    $student_fields = ARIA_API::aria_student_field_id_array();
    $teacher_master_fields = ARIA_API::aria_master_teacher_field_id_array();
    $student_master_fields = ARIA_API::aria_master_student_field_id_array();

    // acquire old information about the teacher's name
    $old_teacher_val_str = $original_entry[strval($student_master_fields['teacher_name'])];
    $old_teacher_val = unserialize($old_teacher_val_str);
    $old_teacher_name = null;
    $old_teacher_hash = null;
    if ($old_teacher_val == false) {
      // the teacher's old name was not unserializable, so provide some default values
      $old_teacher_name = '';
      $old_teacher_hash = '';
    }
    else {
      $old_teacher_name = $old_teacher_val[0] . ' ' . $old_teacher_val[1];
      $old_teacher_hash = $old_teacher_val[2];
    }

    // get the updated teacher name
    $teacher_name = $entry[strval($student_master_fields['teacher_name'])];

    // initialize more values for later
    $found = true;
    $student_hash = $entry[strval($student_master_fields['student_hash'])];

    // compare the teacher's old name with the teacher's new name to see if they have changed
    if ($teacher_name != $old_teacher_val_str) {
      // find teacher in teacher master form
      $search = array();
      $sorting = array();
      $paging = array('offset' => 0, 'page_size' => 2000);
      $total_count = 0;
      $teacher_entries = GFAPI::get_entries($related_forms['teacher_master_form_id'],
                                            $search, $sorting, $paging, $total_count);

      // iterate through all of the teacher entries
      $found = false;
      foreach ($teacher_entries as $teacher) {
        $teacher_first = $teacher[strval($teacher_master_fields['first_name'])];
        $teacher_last = $teacher[strval($teacher_master_fields['last_name'])];
        $teacher_hash =  $teacher[strval($teacher_master_fields['hash'])];
        $full_name = $teacher_first . ' ' . $teacher_last;

        // idk what's going on..
        if ($full_name == $teacher_name) {
          $found = true;
          $teacher_val = array();
          $teacher_val[] = $teacher_first;
          $teacher_val[] = $teacher_last;
          $teacher_val[] = $teacher_hash;
          $teacher_serial = serialize($teacher_val);
          $entry[strval($student_master_fields['teacher_name'])] = $teacher_serial;

          // update student master
          $result = GFAPI::update_entry($entry);

          // add student into new teacher
            // ???

          // determine whether a student has been added or not (if it's an array)
          $students = $teacher[strval($teacher_master_fields["students"])];
          $students = unserialize($students);
          if (!is_array($students)) {
            $students = array();
          }

          // add the newly registered student to the teacher's list of student hashes
          $students[] = $student_hash;
          $teacher[strval($teacher_master_fields["students"])] = serialize($students);

          // update the teacher entry with the new student addition
          $result = GFAPI::update_entry($teacher);
          if (is_wp_error($result)) {
            wp_die(__LINE__.$result->get_error_message());
          }

          // send email
            // ???

          // determine how many students have registered so far
          $search_criteria = array();
          $sorting = null;
          $paging = array('offset' => 0, 'page_size' => 2000);
          $total_count = 0;
          $entries = GFAPI::get_entries($related_forms['student_master_form_id'], $search_criteria,
                                  $sorting, $paging, $total_count);

          // consolidate information for emails
          $email_info = array();
          $email_info['teacher_hash'] = $teacher_hash;
          $email_info['teacher_name'] = $teacher_name;
          $email_info['teacher_email'] = $teacher_entry[strval($teacher_master_fields["email"])];
          $email_info['festival_chairman_email'] = $related_forms["festival_chairman_email"];
          $email_info['parent_email'] = $entry[strval($student_fields["parent_email"])];
          $email_info['teacher_url'] = $related_forms["teacher_public_form_url"];
          $email_info['student_hash'] = $student_hash;
          $email_info['student_name'] = $student_name;
          $email_info['parent_name'] = $entry[strval($student_fields["parent_first_name"])] .
          " " . $entry[strval($student_fields["parent_last_name"])];
          $comp_name = strpos($form['title'], 'Student Registration');
          $comp_name = substr($form['title'], 0, $comp_name - 1);
          $email_info['competition_name'] = $comp_name;
          $email_info['num_participants'] = count($entries);

          // send emails to various parties (parents, teachers, festival chairman)
          ARIA_Registration_Handler::aria_send_registration_emails($email_info);
        }

        // remove student from old teacher
        if($teacher_hash == $old_teacher_hash)
        {
          // Get students
          $students = $teacher[strval($teacher_master_fields["students"])];
          $students = unserialize($students);

          if (is_array($students)) {

            // loop through students
            for($i = 0; $i < count($students); $i++){

              // if student hash is found
              if( $students[$i] == $student_hash ){
                // delete it
                if(count($students == 1))
                {
                  //$students = null;
                  $teacher[strval($teacher_master_fields["students"])] = null;
                }
                else
                {
                  array_splice($students, $i, 1);
                  $teacher[strval($teacher_master_fields["students"])] = serialize($students);
                }

                // Update the teacher entry with the new student edition
                $result = GFAPI::update_entry($teacher);
                if (is_wp_error($result)) {
                  wp_die(__LINE__.$result->get_error_message());
                }
                break;
              }
            }
          }
        }
      }
      //wp_die(print_r($teacher_entries));
      //wp_die($teacher_name . $old_teacher_name);
      if($found == false )
      {
        $entry[ strval($student_master_fields['teacher_name']) ] = $old_teacher_val_str;
        $result = GFAPI::update_entry($entry);
        if (is_wp_error($result)) {
          wp_die(__LINE__.$result->get_error_message());
        }

        wp_die("Error: Please type the teacher's name exactly as it appears in the teacher master. Ex: FirstName LastName");
      }
    }
  }

}
