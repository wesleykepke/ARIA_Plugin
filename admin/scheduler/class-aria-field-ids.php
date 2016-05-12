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
      'competition_name' => 1,
      'competition_start_date' => 2,
      'competition_end_date' => 3,
      'competition_location' => 4,
      'competition_address_first' => 4.1,
      'competition_address_second' => 4.2,
      'competition_city' => 4.3,
      'competition_state_province_region' => 4.4,
      'competition_zip_postal' => 4.5,
      'competition_country' => 4.6,
      'competition_student_reg_start' => 5,
      'competition_student_reg_end' => 6,
      'competition_teacher_reg_start' => 7,
      'competition_teacher_reg_end' => 8,
      'competition_volunteer_times' => 9,
      'competition_teacher_csv_upload' => 10,
      'competition_num_traditional' => 11,
      'competition_num_master' => 12,
      'competition_section_length' => 13,
      'competition_beg_time_buffer' => 14,
      'competition_end_time_buffer' => 15,
      'competition_lunch_break' => 16,
      'competition_num_judges_per_section' => 17,
      'competition_num_command_performances' => 18,
      'competition_command_performance_date' => 19,
      'competition_command_performance_time' => 20,
      'competition_theory_score' => 21,
      'competition_judge_csv_upload' => 22,
      'competition_festival_chairman_email' => 23,
      'competition_command_performance_opt' => 24,
      'competition_2_address' => 25,
      'competition_2_address_first' => 25.1,
      'competition_2_address_second' => 25.2,
      'competition_2_city' => 25.3,
      'competition_2_state' => 25.4,
      'competition_2_zip' => 25.5,
      'competition_2_country' => 25.6,
      'competition_has_master_class' => 26,
      'level_1_price' => 27,
      'level_2_price' => 28,
      'level_3_price' => 29,
      'level_4_price' => 30,
      'level_5_price' => 31,
      'level_6_price' => 32,
      'level_7_price' => 33,
      'level_8_price' => 34,
      'level_9_price' => 35,
      'level_10_price' => 36,
      'level_11_price' => 37,
      'paypal_email' => 38,
      'notification_enabled' => 39,
      'notification_email' => 40

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
      'volunteer_preference' => 4,
      'volunteer_time' => 5,
      'student_name' => 6,
      'student_first_name' => 6.3,
      'student_last_name' => 6.6,
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
      'is_judging' => 17,
      'student_level' => 18,
      'alt_song_2_composer' => 19,
      'alt_song_2_selection' => 20,
      'schedule_with_students' => 21
    );
    /*
    ,
      'alt_song_2_key' => 21,
      'alt_song_2_movement_number' => 22,
      'alt_song_2_movement_description' => 23,
      'alt_song_2_identifying_number' => 24
      */
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
      'student_name' => 3,
      'student_first_name' => 3.3,
      'student_last_name' => 3.6,
      'student_birthday' => 4,
      'teacher_name' => 5,
      'not_listed_teacher_name' => 6,
      'not_listed_teacher_email' => 7,
      'available_festival_days' => 8,
      'preferred_command_performance' => 9,
      'student_level' => 10,
      'compliance_statement' => 11,
      'compliance_statement_agreement' => 12,
      'level_pricing' => 13,
      'registration_total' => 14
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
      'teacher_name' => 5,
      'not_listed_teacher_name' => 6,
      'available_festival_days' => 7,
      'preferred_command_performance' => 8,
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
      'hash' => 19,
      'student_level' => 20
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
      'students' => 1,
      'name' => 2,
      'first_name' => 2.3,
      'last_name' => 2.6,
      'email' => 3,
      'phone' => 4,
      'teacher_hash' => 5,
      'student_hash' => 6,
      'volunteer_preference' => 7,
      'volunteer_time' => 8,
      'is_judging' => 9,
      'schedule_with_students' => 10
    );
  }

}
