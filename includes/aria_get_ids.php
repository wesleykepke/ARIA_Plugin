<?php
	//require_once("class-aria-create-competition.php");
	require_once("class-aria-music.php");
	$teacher_fields = aria_teacher_field_id_array();
	$music_fields = ARIA_Music::aria_music_field_id_array();
  $student_fields = aria_student_field_id_array();
	$all_fields = array_merge( $teacher_fields, $music_fields, $student_fields );
	echo json_encode($all_fields);

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
      'is_judging' => 17, // !!!DO WE WANT TO CHANGE THIS NUMBER
      'student_level' => 18,
      'alt_song_2_composer' => 19,
      'alt_song_2_selection' => 20,
      'alt_song_2_key' => 21,
      'alt_song_2_movement_number' => 22,
      'alt_song_2_movement_description' => 23,
      'alt_song_2_identifying_number' => 24
    );
  }

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
?>
