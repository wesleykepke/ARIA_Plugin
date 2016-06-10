<?php

class ARIA_Resend_Email {

  public static function aria_create_resend_teacher_email_form() {
    // don't create form if it already exists
    if (ARIA_API::aria_get_resend_email_form_id() !== -1) {
      return;
    }

    // create the new competition form and generate the field mappings
    $form = new GF_Form(RESEND_TEACHER_EMAIL_FORM_NAME, "");
    $field_mappings = self::resend_teacher_field_id_array();

    $competition_name_select_field = new GF_Field_Select();
    $competition_name_select_field->label = "Competition";
    $competition_name_select_field->id = $field_mappings['competition_name'];
    $competition_name_select_field->isRequired = true;
    $competition_name_select_field->choices = array();
    $form->fields[] = $competition_name_select_field;

    $teacher_field = new GF_Field_Select();
    $teacher_field->label = "Teacher";
    $teacher_field->id = $field_mappings['teacher'];
    $teacher_field->isRequired = true;
    $teacher_field->choices = array();
    $form->fields[] = $teacher_field;

    $student_field = new GF_Field_Select();
    $student_field->label = "Student";
    $student_field->id = $field_mappings['student'];
    $student_field->isRequired = true;
    $student_field->choices = array();
    $form->fields[] = $student_field;

    // festival chairmans Email
    $email_field = new GF_Field_Email();
    $email_field->label = "Email";
    $email_field->id = $field_mappings['email'];
    $email_field->description = "Please enter the email address that you";
    $email_field->description .= " wish to send the regestration link to";
    $email_field->description .= " (If different from email currently saved in teacher master)";
    $email_field->descriptionPlacement = "above";
    $email_field->isRequired = false;
    $form->fields[] = $email_field;

    $successful_submission_message = 'Congratulations! Your request has been sent';
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

    $form_array = $form->createFormArray();
    $form_array['isResendEmailForm'] = true;

    // add the new form to the festival chairman's dashboard
    $new_form_id = GFAPI::add_form($form_array);

    // make sure the new form was added without error
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    } else {
      // publish this form so it can be used on the front end
      ARIA_API::aria_publish_form(RESEND_TEACHER_EMAIL_FORM_NAME, $new_form_id, CHAIRMAN_PASS, true);
    }
  }

  public static function aria_before_resend_form($form, $is_ajax) {
    // Only perform prepopulation if it's the teacher upload form
    if (!array_key_exists('isResendEmailForm', $form)
        || !$form['isResendEmailForm']) {
          return;
    }

    // Get all of the active competitions
    $all_active_competitions = ARIA_API::aria_get_all_active_comps();

    $competition_names = array();
    foreach ($all_active_competitions as $competition) {
      $single_competition = array(
        'text' => $competition['name'],
        'value' => $competition['name'] . '_' . $competition['aria_relations']['teacher_master_form_id'] .
                    '_' . $competition['aria_relations']['student_master_form_id'],
        'isSelected' => false
      );
      $competition_names[] = $single_competition;
      unset($single_competition);
    }

    $field_mapping = self::resend_teacher_field_id_array();
    $search_field = $field_mapping['competition_name'];
    $name_field = ARIA_API::aria_find_field_by_id($form['fields'], $search_field);
    $form['fields'][$name_field]->choices = $competition_names;
  }

  public static function resend_teacher_field_id_array() {
    return array (
      'competition_name' => 1,
      'teacher' => 2,
      'student' => 3,
      'email' => 4
    );
  }

  public static function aria_after_resend_form($confirmation, $form, $entry, $ajax) {
    // only perform processing is the resend url form was used
    if (!array_key_exists('isResendEmailForm', $form)
        || !$form['isResendEmailForm']) {
      return $confirmation;
    }
    $field_mapping = self::resend_teacher_field_id_array();
    $title = explode('_', $entry[$field_mapping['competition_name']])[0];

    $req_email = $entry[$field_mapping['email']];

    if( $req_email == null )
    {
      $related_form_ids = ARIA_API::aria_find_related_forms_ids($title);
      $teacher_master_form_id = $related_form_ids['teacher_master_form_id'];
      $teacher_master_field_mapping = ARIA_API::aria_master_teacher_field_id_array();

      // find teacher in master
      $search = array ( );
      $search['field_filters'][] = array( 'key' => $teacher_master_field_mapping['hash'], 
                                          'value' => $entry[$field_mapping['teacher']]);
      $search['field_filters']['mode'] = 'any';
      $sorting = array();
      $paging = array('offset' => 0, 'page_size' => 2000);
      $total_count = 0;
      $teacher_entries = GFAPI::get_entries($teacher_master_form_id, $search, $sorting, $paging, $total_count);
      $teacher_master_email_field = $teacher_master_field_mapping['email'];
      $req_email = $teacher_entries[0][$teacher_master_email_field];
      //wp_die(print_r($teacher_entries));
    }
  return $confirmation;

  }

}
