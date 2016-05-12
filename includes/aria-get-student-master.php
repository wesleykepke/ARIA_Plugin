<?php
  $student_fields = aria_master_student_field_id_array();
  echo json_encode($student_fields);

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
	function aria_master_student_field_id_array() {
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
?>