<?php

class ARIA_Field_IDs {

  /**
   * This function will return an associative array with entry field
   * mappings for the create competition form.
   *
   * Every time an entry is submitted using the form for creating a
   * competition, the submission is an Entry object, which is an
   * associative array that has a plethora of information. Also included
   * inside the Entry object is the infomation that was input by the user.
   * This function simply returns an associative array that can be used by
   * other functions to offset into the Entry object's user data, because
   * otherwise, the offset all involves magic integers that are otherwise
   * not very descriptive.
   *
   * @since 1.0.5
   * @author KREW
   */
  public static function aria_competition_field_id_array() {
    /*
    CAUTION, This array is used as a source of truth. Changing these values may
    result in catastrophic failure. If you do not want to feel the bern,
    consult an aria developer before making changes to this portion of code.

    This is super important and can't be emphasized enough! These values must
    be changed if the corresponding form is modified. Use the function
    json_encode($entry) to view the JSON and make sure it matches what this
    function returns.
    */
    return array(
      'chairman_email' => 1,
      'chairman_email_confirmation' => 2,
      'name' => 3,
      'start_date' => 4,
      'end_date' => 5,
      'main_location' => 6,
      'main_location_first_address' => 6.1,
      'main_location_second_address' => 6.2,
      'main_location_city' => 6.3,
      'main_location_state' => 6.4,
      'main_location_zip' => 6.5,
      'main_location_country' => 6.6,
      'second_location' => 7,
      'second_location_first_address' => 7.1,
      'second_location_second_address' => 7.2,
      'second_location_city' => 7.3,
      'second_location_state' => 7.4,
      'second_location_zip' => 7.5,
      'second_location_country' => 7.6,
      'student_registration_start' => 8,
      'student_registration_end' => 9,
      'teacher_registration_start' => 10,
      'teacher_registration_end' => 11,
      'volunteer_options' => 12,
      'volunteer_time_options' => 13,
      'teacher_upload' => 14,
      'command_performance_options' => 15,
      'master_class_registration_option' => 16,
      'notification_option' => 17,
      'notification_email' => 18,
      'notification_email_confirmation' => 19,
      'paypal_email' => 20,
      'paypal_email_confirmation' => 21,
      'level_1_price' => 22,
      'level_2_price' => 23,
      'level_3_price' => 24,
      'level_4_price' => 25,
      'level_5_price' => 26,
      'level_6_price' => 27,
      'level_7_price' => 28,
      'level_8_price' => 29,
      'level_9_price' => 30,
      'level_10_price' => 31,
      'level_11_price' => 32,
    );
  }

  /**
   * This function defines an associative array with entry field mappings
   * for the teacher registration form.
   *
   * This function returns an array that maps all of the names of the
   * fields in the teacher form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_teacher_field_id_array() {
    /*
    CAUTION, This array is used as a source of truth. Changing these values may
    result in catastrophic failure. If you do not want to feel the bern,
    consult an aria developer before making changes to this portion of code.

    This is super important and can't be emphasized enough! These values must
    be changed if the corresponding form is modified. Use the function
    json_encode($entry) to view the JSON and make sure it matches what this
    function returns.
    */
    return array(
      'name' => 1,
      'first_name' => 1.3,
      'last_name' => 1.6,
      'email' => 2,
      'phone' => 3,
      'is_judging' => 4,
      'volunteer_preference' => 5,
      'volunteer_time' => 6,
      'schedule_with_students' => 7,
      'student_name' => 8,
      'student_first_name' => 8.3,
      'student_last_name' => 8.6,
      'student_level' => 9,
      'song_1_period' => 10,
      'song_1_composer' => 11,
      'song_1_selection' => 12,
      'song_2_period' => 13,
      'song_2_composer' => 14,
      'song_2_selection' => 15,
      'timing_of_pieces' => 16,
      'student_division' => 17,
      'theory_score' => 18,
      'alternate_theory' => 19,
      'alt_song_2_composer' => 19,
      'alt_song_2_selection' => 20,
    );
  }

  /**
   * This function defines an associative array with entry field mappings
   * for the student registration form.
   *
   * This function returns an array that maps all of the names of the
   * fields in the student form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_student_field_id_array() {
    /*
    CAUTION, This array is used as a source of truth. Changing these values may
    result in catastrophic failure. If you do not want to feel the bern,
    consult an aria developer before making changes to this portion of code.

    This is super important and can't be emphasized enough! These values must
    be changed if the corresponding form is modified. Use the function
    json_encode($entry) to view the JSON and make sure it matches what this
    function returns.
    */
    return array(
      'parent_name' => 1,
      'parent_first_name' => 1.3,
      'parent_last_name' => 1.6,
      'parent_email' => 2,
      'parent_email_confirmation' => 3,
      'student_name' => 4,
      'student_first_name' => 4.3,
      'student_last_name' => 4.6,
      'student_birthday' => 5,
      'teacher_name' => 6,
      'available_festival_days' => 7,
      'preferred_command_performance' => 8,
      'student_level' => 9,
      'compliance_statement' => 10,
      'registration_total' => 11
    );
  }

  /**
   * This function defines an associative array with entry field mappings
   * for the student master form.
	 *
	 * This function returns an array that maps all of the names of the
   * fields in the student master form to a unique integer so that they
   * can be referenced. Moreover, this array helps prevent the case where
   * the names of these fields are modified from the dashboard.
	 *
	 * @since 1.0.0
	 * @author KREW
	 */
	public static function aria_master_student_field_id_array() {
	  /*
    CAUTION, This array is used as a source of truth. Changing these values may
    result in catastrophic failure. If you do not want to feel the bern,
    consult an aria developer before making changes to this portion of code.

    This is super important and can't be emphasized enough! These values must
    be changed if the corresponding form is modified. Use the function
    json_encode($entry) to view the JSON and make sure it matches what this
    function returns.
    */
	  return array(
	    'parent_name' => 1,
			'parent_first_name' => 1.3,
			'parent_last_name' => 1.6,
      'parent_email' => 2,
      'student_name' => 3,
			'student_first_name' => 3.3,
			'student_last_name' => 3.6,
      'student_birthday' => 4,
      'student_level' => 5,
      'teacher_name' => 6,
      'festival_availability' => 7,
      'command_performance_availability' => 8,
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
      'student_hash' => 19,
	  );
	}

  /**
   * This function defines an associative array with entry field mappings
   * for the teacher master form.
   *
   * This function returns an array that maps all of the names of the
   * fields in the student form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   *
   */
  public static function aria_master_teacher_field_id_array() {
    /*
    CAUTION, This array is used as a source of truth. Changing these values may
    result in catastrophic failure. If you do not want to feel the bern,
    consult an aria developer before making changes to this portion of code.

    This is super important and can't be emphasized enough! These values must
    be changed if the corresponding form is modified. Use the function
    json_encode($entry) to view the JSON and make sure it matches what this
    function returns.
    */
    return array(
      'name' => 1,
      'first_name' => 1.3,
      'last_name' => 1.6,
      'email' => 2,
      'phone' => 3,
      'hash' => 4,
      'students' => 5,
      'student_hashes' => 6,
      'is_judging' => 7,
      'volunteer_preference' => 8,
      'volunteer_time' => 9,
      'schedule_with_students' => 10
    );
  }
}
