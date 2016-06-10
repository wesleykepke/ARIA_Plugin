<?php
  $teacher_fields = aria_master_teacher_field_id_array();
  echo json_encode($teacher_fields);

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
  function aria_master_teacher_field_id_array() {
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
?>
