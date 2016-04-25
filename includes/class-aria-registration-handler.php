<?php

/**
 * The file that defines the functionality for handling competition registration.
 *
 * A class definition that includes attributes and functions that allow the
 * registration (of students and teachers) for NNMTA competitions to operate
 * seamlessly.
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

/**
 * The competition registration handler class.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
*/
class ARIA_Registration_Handler {

  /**
  * Testing payment email stuff
  */
  public static function aria_test_payment($entry, $action){
    wp_die($action['type']);
  }

	/**
	 * Function for sending emails after student registration.
   *
   * This function is responsible for sending teachers, parents, and the
   * festival chairman emails with specific information regarding the student
   * who was just recently registered.
   *
   * @param $email_info Array   An associative array containing emails and url info.
   *
   * @return void
	 */
  public static function aria_send_registration_emails($email_info) {
    // a sample url for teacher registration with 2 hashes might look like the following:
    // wesley-bruh-teacher-registration-4/?teacher_hash=fredharris&student_hash=weskepke
    // note the & in between the two hash values
/*
    $teacher_link = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $teacher_link .= "?teacher_hash=" . $teacher_hash;
    $teacher_link .= "&student_hash=" . $student_hash;
*/

    // generate the link to send to the teachers
    $send_url = $email_info['teacher_url'];
    $send_url .= "?teacher_hash=" . $email_info['teacher_hash'];
    $send_url .= "&student_hash=" . $email_info['student_hash'];

    // generate the message to send to the teachers
    $message_teacher = "<html> Hello " . $email_info['teacher_name'] . "!<br />";
    $message_teacher .= "Congratulations. " . $email_info['student_name'];
    $message_teacher .= " has registered for the NNMTA";
    $message_teacher .= " event: " . $email_info['competition_name'];
    $message_teacher .= ".<br />Please click on the following link to finish";
    $message_teacher .= " registering your student: <a href=\"" . $send_url. "\">".$email_info['student_name']."</a>";
    $message_teacher .= "<br />Once the event has been scheduled, you will receive an email with this student\'s scheduled performance time.";
    $message_teacher .= "<br /><br />Thank you, <br />NNMTA Festival Chair<br />";
    $message_teacher .= "(" . $email_info['festival_chairman_email'] . ")</html>";

    $subject = "NNMTA " . $email_info['competition_name'] . " - Registration";
    if (!wp_mail((string)$email_info['teacher_email'], $subject, $message_teacher)) {
      wp_die('Teacher email (for student registration) failed to send.');
    }

    // generate the message to send to the parents
    $message_parent = "<html>Hello " . $email_info['parent_name'] . "!<br />";
    $message_parent .= "Congratulations. ".$email_info['student_name'];
    $message_parent .= ", has registered for the NNMTA";
    $message_parent .= " music competition: " . $email_info['competition_name'];
    $message_parent .= "<br />Once the event has been scheduled, you will receive an email with this student's scheduled performance time.";
    $message_parent .= "<br /><br />Thank you, <br />NNMTA Festival Chair<br />";
    $message_parent .= "(" . $email_info['festival_chairman_email'] . ")</html>";
    if (!wp_mail($email_info['parent_email'], $subject, $message_parent)) {
      wp_die('Parent email (for student registration) failed to send.');
    }

    // generate message to send to the festival chairman
    if(array_key_exists('notification_email', $email_info))
    {
      $message_chairman = "<html>".$email_info['student_name'];
      $message_chairman .= " has just registered for " . $email_info['competition_name'];
      $message_chairman .= " and will have registration completed by ";
      $message_chairman .= $email_info['teacher_name'] . ".<br /><br />";
      $message_chairman .= "<br />Save this link in case you need to resend it to the teacher to finish";
      $message_chairman .= " registering their student: " . $send_url;
      $message_chairman .= "<br />As of this moment, there are " . strval($email_info['num_participants']);
      $message_chairman .= " students that have registered for " . $email_info['competition_name'] . ".";
      $message_chairman .= " </html>";
      if (!wp_mail((string)$email_info['notification_email'], $subject, $message_chairman)) {
        wp_die('Teacher registration email failed to send.');
      }
    }
  }

	/**
	 * Function for searching through student-master to find a student.
   *
   * This function will search through the student-master form and check to see
   * if a particular student exists. If a studetn exists within the student-
   * master form of a particular competition, then the entry for that
   * student will be returned. Otherwise, if no such student exists, the
   * function will return false.
   *
   * @param $student_master_form_id   Integer   The ID of the student-master form.
   * @param $student_hash             String    The hash of a particular student.
   *
   * @since 1.0.0
   * @author KREW
	 */
  public static function aria_find_student_entry($student_master_form_id, $student_hash) {
    $hash_field_id = ARIA_API::aria_master_student_field_id_array()['hash'];

    // check to see if any of the entries in the student master have $student_hash
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $search_criteria = array(
      'field_filters' => array(
        'mode' => 'any',
        array(
         'key' => (string) $hash_field_id,
         'value' => $student_hash
        )
      )
    );

    $entries = GFAPI::get_entries($student_master_form_id, $search_criteria, $sorting, $paging, $total_count);
    if(count($entries) == 1 && rgar($entries[0], (string) $hash_field_id) == $student_hash) {
     return $entries[0];
    }

    return false;
  }

	/**
	 * Function for searching through teacher-master to find a teacher.
   *
   * This function will search through the teacher-master form and check to see
   * if a particular teacher exists. If a teacher exists within the teacher
   * master form of a particular competition, then the entry for that
   * teacher will be returned. Otherwise, if no such teacher exists, the
   * function will return false.
   *
   * @param $teacher_master_form_id   Integer   The ID of the teacher master form.
   * @param $teacher_hash             String    The hash of a particular teacher.
   *
   * @since 1.0.0
   * @author KREW
	 */
  public static function aria_find_teacher_entry($teacher_master_form_id, $teacher_hash) {

    $hash_field_id = ARIA_API::aria_master_teacher_field_id_array()['teacher_hash'];

    // check to see if any of the entries in the teacher master have $teacher_hash
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $search_criteria = array(
			'field_filters' => array(
				'mode' => 'all',
				array(
					'key' => (string) $hash_field_id,
					'value' => $teacher_hash
				)
			)
		);

    $entries = GFAPI::get_entries($teacher_master_form_id, $search_criteria, $sorting, $paging, $total_count);


    if (count($entries) === 1 && rgar($entries[0], (string) $hash_field_id) == $teacher_hash) {
      // it's reaching this wp_die()
      //wp_die("After get_entries, inside if statement: " . print_r($entries));
      return $entries[0];
    }

    return false;
  }

	/**
	 * Function to check if a student is assigned to a teacher.
	 */
   public static function aria_check_student_teacher_relationship($related_forms, $student_hash, $teacher_hash) {
     // Get field ids
		 $students_field_id = ARIA_API::aria_master_teacher_field_id_array()['students'];

     // Get the teacher entry
     $teacher_entry = self::aria_find_teacher_entry($related_forms['teacher_master_form_id'], $teacher_hash);

     // return if teacher entry does not exist.
     if($teacher_entry == false) return false;

     // get the array of students the teacher is assigned.
     $students = unserialize(rgar($teacher_entry, (string) $students_field_id));

     // find the student name in the array of students.
     foreach($students as $student) {
       if ($student == $student_hash) return true;
     }
     return false;
   }

	/**
	 * Function to get pre-populate values based on teacher-master.
	 */
	 public static function aria_get_teacher_pre_populate($related_forms, $teacher_hash) {
		$hash_field_id = ARIA_API::aria_master_teacher_field_id_array()['teacher_hash'];
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
		 $search_criteria = array(
       'field_filters' => array(
         'mode' => 'any',
         array(
           'key' => (string) $hash_field_id,
           'value' => $teacher_hash
         )
       )
		 );

		 $entries = GFAPI::get_entries($related_forms['teacher_master_form_id'], $search_criteria, $sorting, $paging, $total_count);

		 if (is_wp_error($entries)) {
 			wp_die($entries->get_error_message());
 		}

		$field_ids = ARIA_API::aria_master_teacher_field_id_array();

    $volunteer_pref_array = array();
    foreach($entries[0] as $key => $value){
      //wp_die(intval($key));
      if( intval($key) == (int) $field_ids['volunteer_preference'] ){
        $volunteer_pref_array[$key] = $value;
      }
    }

    $volunteer_time_array = array();
    foreach($entries[0] as $key => $value){
      //wp_die(intval($key));
      if( intval($key) == (int) $field_ids['volunteer_time'] ){
        $volunteer_time_array[$key] = $value;
      }
    }

		return array(
			'first_name' => rgar( $entries[0], (string) $field_ids['first_name'] ),
			'last_name' => rgar( $entries[0], (string) $field_ids['last_name'] ),
			'email' => rgar( $entries[0], (string) $field_ids['email'] ),
			'phone' => rgar( $entries[0], (string) $field_ids['phone'] ),
			'volunteer_preference' => $volunteer_pref_array,
			'volunteer_time' => $volunteer_time_array,
			'students' => rgar( $entries[0], (string) $field_ids['students'] ),
			'is_judging' => rgar( $entries[0], (string) $field_ids['is_judging'] ),
      'schedule_with_students' => rgar( $entries[0], (string) $field_ids['schedule_with_students'] ),
			'teacher_hash' => rgar( $entries[0], (string) $field_ids['teacher_hash'])

		);
	 }

	/**
	 * Function to get pre-populate values based on student-master.
	 */
	 public static function aria_get_student_pre_populate($related_forms, $student_hash) {
		$hash_field_id = ARIA_API::aria_master_student_field_id_array()['hash'];
    $sorting = null;
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
		 $search_criteria = array(
       'field_filters' => array(
         'mode' => 'any',
         array(
           'key' => (string) $hash_field_id,
           'value' => $student_hash
         )
       )
		 );

		 $entries = GFAPI::get_entries($related_forms['student_master_form_id'], $search_criteria, $sorting, $paging, $total_count);

		 if (is_wp_error($entries)) {
 			wp_die($entries->get_error_message());
 		}

		$field_ids = ARIA_API::aria_master_student_field_id_array();

		return array(
			'parent_name' => rgar( $entries[0], (string) $field_ids['parent_name']),
	    'parent_email' => rgar( $entries[0], (string) $field_ids['parent_email']),
	    'student_first_name' => rgar( $entries[0], (string) $field_ids['student_first_name']),
	    'student_last_name' => rgar( $entries[0], (string) $field_ids['student_last_name']),
	    'student_level' => rgar( $entries[0], (string) $field_ids['student_level']),
	    'student_birthday' => rgar( $entries[0], (string) $field_ids['student_birthday']),
	    'teacher_name' => rgar( $entries[0], (string) $field_ids['teacher_name']),
	    'not_listed_teacher_name' => rgar( $entries[0], (string) $field_ids['not_listed_teacher_name']),
	    'available_festival_days' => rgar( $entries[0], (string) $field_ids['available_festival_days']),
	    'preferred_command_performance' => rgar( $entries[0], (string) $field_ids['preferred_command_performance']),
	    'song_1_period' => rgar( $entries[0], (string) $field_ids['song_1_period']),
	    'song_1_composer' =>  rgar( $entries[0], (string) $field_ids['song_1_composer']),
	    'song_1_selection' =>  rgar( $entries[0], (string) $field_ids['song_1_selection']),
	    'song_2_period' =>  rgar( $entries[0], (string) $field_ids['song_2_period']),
	    'song_2_composer' =>  rgar( $entries[0], (string) $field_ids['song_2_composer']),
	    'song_2_selection' =>  rgar( $entries[0], (string) $field_ids['song_2_selection']),
	    'theory_score' =>  rgar( $entries[0], (string) $field_ids['theory_score']),
	    'alternate_theory' =>  rgar( $entries[0], (string) $field_ids['alternate_theory']),
	    'competition_format' =>  rgar( $entries[0], (string) $field_ids['competition_format']),
	    'timing_of_pieces' =>  rgar( $entries[0], (string) $field_ids['timing_of_pieces']),
			'hash' => rgar( $entries[0], (string) $field_ids['hash'])
		);
	}


	  /**
   * This function will prepopulate student and teacher values
   * @param $form
   * @param $teacher_prepop_values
   * @param $student_prepop_values
   *
   * @since 1.0.0
   * @author KREW
  */
	public static function aria_prepopulate_form( $form, $teacher_prepop_vals, $student_prepop_vals){

	  $teacher_public_fields = ARIA_API::aria_teacher_field_id_array();

	  // Prepopulate teacher name
	  $search_field = $teacher_public_fields['name'];
	  $name_field = self::aria_find_field_by_id($form['fields'], $search_field);
	  $search_field = $teacher_public_fields['first_name'];
	  $first_name_field = self::aria_find_field_by_id($form['fields'][$name_field]['inputs'], $search_field);
	  $search_field = $teacher_public_fields['last_name'];
	  $last_name_field = self::aria_find_field_by_id($form['fields'][$name_field]['inputs'], $search_field);
	  $name = $form['fields'][$name_field]['inputs'];
	  if($first_name_field != null && ($teacher_prepop_vals['first_name'] != "") ){
	    $name[$first_name_field]['defaultValue'] = $teacher_prepop_vals['first_name'];
	  }
	  if($last_name_field != null && ($teacher_prepop_vals['last_name'] != "")) {
	    $name[$last_name_field]['defaultValue'] = $teacher_prepop_vals['last_name'];
	  }
	  $form['fields'][$name_field]['inputs'] = $name;

    // Prepopulate teacher email
    //wp_die(print_r($form['fields']));
    $search_field = $teacher_public_fields['email'];
    $email_field = self::aria_find_field_by_id($form['fields'], $search_field);
    if($email_field != null && ($teacher_prepop_vals['email'] != "") ){
      $form['fields'][$email_field]['defaultValue'] = $teacher_prepop_vals['email'];
    }

    // Prepopulate teacher phone
    $search_field = $teacher_public_fields['phone'];
    $phone_field = self::aria_find_field_by_id($form['fields'], $search_field);
    if($phone_field != null && ($teacher_prepop_vals['phone'] != "") ){
      $form['fields'][$phone_field]['defaultValue'] = $teacher_prepop_vals['phone'];
    }

    // Prepopulate teacher judging
    $search_field = $teacher_public_fields['is_judging'];
    $judging_field = self::aria_find_field_by_id($form['fields'], $search_field);
    if($judging_field != null && ($teacher_prepop_vals['is_judging'] != "") ){
      // loop through each choice
          $choices = $form['fields'][$judging_field]['choices'];
      for( $i = 0; $i < count($form['fields'][$judging_field]['choices']); $i++){
        if($form['fields'][$judging_field]['choices'][$i]['text'] == $teacher_prepop_vals['is_judging']){
          // set is selected
          $choices[$i]['isSelected'] = true;
        }
      }
          $form['fields'][$judging_field]['choices'] = $choices;
    }

    // Prepopulate teacher volunteer pref
    $search_field = $teacher_public_fields['volunteer_preference'];
    $preference_field = self::aria_find_field_by_id($form['fields'], $search_field);
    $choices = $form['fields'][$preference_field]['choices'];
    foreach($teacher_prepop_vals['volunteer_preference'] as $pref){
      if($pref != null){
        $choice = self::aria_find_choice_by_val($choices, $pref);
        $choices[$choice]['isSelected'] = true;
      }
    }
    $form['fields'][$preference_field]['choices'] = $choices;

    // Prepopulate teacher volunteer times
    $search_field = $teacher_public_fields['volunteer_time'];
    $preference_field = self::aria_find_field_by_id($form['fields'], $search_field);
    $choices = $form['fields'][$preference_field]['choices'];
    foreach($teacher_prepop_vals['volunteer_time'] as $pref){
      if($pref != null){
        $choice = self::aria_find_choice_by_val($choices, $pref);
        $choices[$choice]['isSelected'] = true;
      }
    }
    $form['fields'][$preference_field]['choices'] = $choices;

    // Prepopulate volunteer with students
    $search_field = $teacher_public_fields['schedule_with_students'];
    $schedule_with_field = self::aria_find_field_by_id($form['fields'], $search_field);
    if($schedule_with_field != null && ($teacher_prepop_vals['schedule_with_students'] != "") ){
      // loop through each choice
          $choices = $form['fields'][$schedule_with_field]['choices'];
      for( $i = 0; $i < count($form['fields'][$schedule_with_field]['choices']); $i++){
        if($form['fields'][$schedule_with_field]['choices'][$i]['text'] == $teacher_prepop_vals['schedule_with_students']){
          // set is selected
          $choices[$i]['isSelected'] = true;
        }
      }
          $form['fields'][$schedule_with_field]['choices'] = $choices;
    }

	  // Prepopulate student name
	  $search_field = $teacher_public_fields['student_name'];
	  $student_name_field = self::aria_find_field_by_id($form['fields'], $search_field);
	  $search_field = $teacher_public_fields['student_first_name'];
	  $first_name_field = self::aria_find_field_by_id($form['fields'][$student_name_field]['inputs'], $search_field);
	  $search_field = $teacher_public_fields['student_last_name'];
	  $last_name_field = self::aria_find_field_by_id($form['fields'][$student_name_field]['inputs'], $search_field);
	  $name = $form['fields'][$student_name_field]['inputs'];
	  if($first_name_field != null && ($student_prepop_vals['student_first_name'] != "")){
	    $name[$first_name_field]['defaultValue'] = $student_prepop_vals['student_first_name'];
	  }
	  if($last_name_field != null && ($student_prepop_vals['student_last_name'] != "")) {
	    $name[$last_name_field]['defaultValue'] = $student_prepop_vals['student_last_name'];
	  }
	  $form['fields'][$student_name_field]['inputs'] = $name;

	  // Prepopulate student level
	  $search_field = $teacher_public_fields['student_level'];
	  $level_field = self::aria_find_field_by_id($form['fields'], $search_field);
	  $level = $form['fields'][$level_field]['choices'];
	  $level[$student_prepop_vals['student_level']-1]['isSelected'] = true;
	  $form['fields'][$level_field]['choices'] = $level;
	}

	  /**
   * This function will find the field number with the specified ID.
   *
   * The function will search through the given array of fields and
   * locate the field with the given ID number. The ID of the field
   * is then returned.
   * @param $fields   Array   The array of fields to search through
   * @param $id       Float     The id of the array to search for
   *
   * @since 1.0.0
   * @author KREW
  */
  public static function aria_find_field_by_id( $fields, $id ){
    $field_num = 0;
    foreach($fields as $key){
      if($fields[$field_num]['id'] == $id){
        return $field_num;
      }
      $field_num++;
    }
    return null;
  }

  public static function aria_find_choice_by_val( $choices, $val ){
    for($i = 0; $i < count($choices); $i++){
      //wp_die(print_r($choices));
      if($choices[$i]['value'] == $val){
        return $i;
      }
    }
    return null;
  }
}
