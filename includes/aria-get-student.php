<?php
  $student_fields = aria_student_field_id_array();
	echo json_encode($student_fields);

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
