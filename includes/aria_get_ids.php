<?php
	//require_once("class-aria-create-competition.php");
	require_once("class-aria-music.php");
	$teacher_fields = aria_teacher_field_id_array();
	$music_fields = ARIA_Music::aria_music_field_id_array();
  $student_fields = aria_student_field_id_array();
	$all_fields = array_merge( $teacher_fields, $music_fields, $student_fields );
	echo json_encode($all_fields);

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
  function aria_teacher_field_id_array() {
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
  function aria_student_field_id_array() {
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
      'registration_total' => 11,
      'level_pricing' => 12
    );
  }
?>
