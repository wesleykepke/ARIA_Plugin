<?php
  $teacher_fields = aria_master_teacher_field_id_array();
  echo json_encode($teacher_fields);

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
?>