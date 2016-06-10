<?php
  $student_fields = aria_student_field_id_array();
	echo json_encode($student_fields);

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
      'festival_availability' => 7,
      'command_performance_availability' => 8,
      'student_level' => 9,
      'level_pricing' => 10,
      'compliance_statement' => 11,
      'registration_total' => 12,
    );
  }
?>
