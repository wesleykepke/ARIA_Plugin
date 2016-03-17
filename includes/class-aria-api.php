<?php

/**
 * The file acts like an API for functions that may be called repeatedly.
 *
 * This file lists various functions and their implementation that may be
 * used throughout ARIA. Simply require_once() this file and the all of the
 * associated functionality will be available.
 *
 * @link       http://wesleykepke.github.io/ARIA/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

// Make sure Gravity Forms is installed and enabled
require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_plugin_active('gravityforms/gravityforms.php')) {
  wp_die("Error: ARIA requires the Gravity Forms plugin to be installed
  and enabled. Please enable the Gravity Forms plugin and reactivate
  ARIA.");
}

require_once("aria-constants.php");

class ARIA_API {

  /**
   * This function will find the ID of the form used to create music competitions.
   *
   * This function will iterate through all of the active form objects and return
   * the ID of the form that is used to create music competitions. If no music
   * competition exists, the function will return -1.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_get_create_competition_form_id() {
    $form_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] === CREATE_COMPETITION_FORM_NAME) {
        $form_id = $form['id'];
      }
    }

    return $form_id;
  }

  /**
   * This function will find the ID of the form used to upload music teachers.
   *
   * This function will iterate through all of the active form objects and
   * return the ID of the form that is used to upload music teachers. If no such
   * form exists, the function will return -1.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_get_teacher_upload_form_id() {
    $form_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] === TEACHER_UPLOAD_FORM_NAME) {
        $form_id = $form['id'];
      }
    }

    return $form_id;
  }

  /**
   * This function will find the ID of the form used to upload songs.
   *
   * This function will iterate through all of the active form objects and
   * return the ID of the form that is used to upload music to the NNMTA music
   * database.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_get_song_upload_form_id() {
    $form_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] === MUSIC_UPLOAD_FORM_NAME) {
        $form_id = $form['id'];
      }
    }

    return $form_id;
  }

  /**
   * This function will find the ID of the form used as the NNMTA music database.
   *
   * This function will iterate through all of the active form objects and return
   * the ID of the form that is used to store all of the NNMTA music.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_get_nnmta_database_form_id() {
    $form_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] == NNMTA_MUSIC_DATABASE_NAME) {
        $form_id = $form['id'];
      }
    }

    return $form_id;
	}

  /**
   * This function will find the ID of the form used to schedule competitions.
   *
   * This function will iterate through all of the active form objects and
   * return the ID of the form that is used to schedule music competitions. If
   * no such form exists, the function will return -1.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_get_scheduler_form_id() {
    $form_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] === SCHEDULER_FORM_NAME) {
        $form_id = $form['id'];
      }
    }

    return $form_id;
  }

  /**
   * This function will find the file path of the uploaded csv music file.
   *
   * This function will extract the name of the csv file containing the music
   * and return the file path so that it can be used in other functions.
   *
   * @param		Entry Object	$entry	The entry object from the upload form.
   * @param		Form Object		$form		The form object that contains $entry.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_get_music_csv_file_path($entry, $form) {
    // find the field id used to upload the music csv file
    $field_id = NULL;
    foreach ($form['fields'] as $field) {
      if ($field['label'] === CSV_UPLOAD_FIELD_NAME) {
        $field_id = $field['id'];
      }
    }

    // display error if the field is not present in the form
    if (!isset($field_id)) {
      wp_die('Form named \'' . $form['title'] . '\' does not have a field named \''
      . CSV_UPLOAD_FIELD_NAME . '\'. Please create this field and try uploading
      music again.');
    }

    // parse the url and obtain the file path for the csv file
    $file_url = $entry[strval($field_id)];
    $file_url_atomic_strings = explode('/', $file_url);
    $full_file_path = '/var/www/html/wp-content/uploads/testpath/'; // this may need to change
    $full_file_path .= $file_url_atomic_strings[count($file_url_atomic_strings) - 1];
    return $full_file_path;
  }

  /**
   * This function will find the file path of the uploaded csv teacher file.
   *
   * This function will extract the name of the csv file containing the teacher
   * data that was uploaded during the create competition form and return the
   * file path so that it can be used in other functions.
   *
   * @param		Entry Object	$entry	The entry object from the upload form.
   * @param		Form Object		$form		The form object that contains $entry.
   *
   * @since 1.0.0
   * @author KREW
   */
	public static function aria_get_teacher_csv_file_path($entry, $form) {
    // find the field id used to upload the teacher csv file
    $field_id = NULL;
    foreach ($form['fields'] as $field) {
      if ($field['label'] === CSV_TEACHER_FIELD_NAME) {
        $field_id = $field['id'];
      }
    }

    // display error if the field is not present in the form
    if (!isset($field_id)) {
      wp_die('Form named \'' . $form['title'] . '\' does not have a field named \''
      . CSV_TEACHER_FIELD_NAME . '\'. Please create this field and try uploading
      teacher data again.');
    }

    // parse the url and obtain the file path for the csv file
    $file_url = $entry[strval($field_id)];
    $file_url_atomic_strings = explode('/', $file_url);
    $full_file_path = '/var/www/html/wp-content/uploads/testpath/'; // this may need to change
    $full_file_path .= $file_url_atomic_strings[count($file_url_atomic_strings) - 1];
    return $full_file_path;
	}

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
      'competition_theory_score' => 21
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
      'available_festival_days' => 7,
      'available_festival_days_saturday' => 7.1,
      'available_festival_days_sunday' => 7.2,
      'preferred_command_performance' => 8,
      'preferred_command_performance_earlier' => 8.1,
      'preferred_command_performance_later' => 8.2,
      'student_level' => 9,
      'compliance_statement' => 10,
      'compliance_statement_agreement' => 10.1
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
      'available_festival_days_saturday' => 7.1,
      'available_festival_days_sunday' => 7.2,
      'preferred_command_performance' => 8,
      'preferred_command_performance_earlier' => 8.1,
      'preferred_command_performance_later' => 8.2,
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
      'student_hash' => 6, // needs checking!
      'volunteer_preference' => 7, // needs checking!
      'volunteer_time' => 8, // needs checking!
      'is_judging' => 9 // needs checking!
    );
  }

  /**
   * This function will publish a new page with a specific form.
   *
   * When a new form is created, that form most likely needs to be published
   * to a page so that it can be used. This function is responsible for creating
   * a published page with a form embedded within it.
   *
   * @param String $form_title The title of the form.
   * @param Int $form_id The id of the form.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_publish_form($form_title, $form_id, $password = null){
    // Set Parameters for the form
    $postarr = array(
      'post_title' => $form_title,
      'post_content' => "[gravityform id=\"{$form_id}\" title=\"true\" description=\"true\"]",
      'post_status' => 'publish',
      'post_type' => 'page',
      'post_password' => $password
    );

    // Force a wp_error to be returned on failure
    $return_wp_error_on_failure = true;

    // Create a wp_post
    $post_id = wp_insert_post($postarr, $return_wp_error_on_failure);

    // If not a wp_error, get the url from the post and return.
    if(!is_wp_error($post_id)) {
      return esc_url(get_permalink($post_id));
    }
    return $post_id;
  }

  /**
   * This function will return the title of a form given its ID.
   *
   * This function will return the title of a form in the event where only
   * the form ID is known (gform_after_submission). If no such form exists for
   * the given ID, the function will return -1.
   *
   * @param   $form_id   Integer   The id of the form to search form_id
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_find_form_title_from_id($form_id) {
    $all_forms = GFAPI::get_forms();
    $title = -1;

    foreach ($all_forms as $form) {
      if ($form["id"] == $form_id) {
        $title = $form["title"];
      }
    }

    return $title;
  }

	/**
	 * Function for returning related forms.
	 *
	 * This function will return an associative array that maps the titles of
	 * the associated forms in a music competition (student, student master,
	 * teacher, and teacher master) to their respective form IDs.
	 *
	 * @param $prepended_title	String	The prepended portion of the competition title.
	 *
	 * @author KREW
	 * @since 1.0.0
	 */
	public static function aria_find_related_forms_ids($prepended_title) {
		// make sure to get all forms! check this
		$all_forms = GFAPI::get_forms();

		$form_ids = array(
			'student_public_form_id' => null,
			'teacher_public_form_id' => null,
			'student_master_form_id' => null,
			'teacher_master_form_id' => null
		);

		$student_form = $prepended_title . " Student Registration";
		$student_master_form = $prepended_title . " Student Master";
		$teacher_form = $prepended_title . " Teacher Registration";
		$teacher_master_form = $prepended_title . " Teacher Master";
		$all_competition_forms = array($student_form, $student_master_form,
      $teacher_form, $teacher_master_form);

		foreach ($all_forms as $form) {
			switch ($form["title"]) {
				case $student_form:
					$form_ids['student_public_form_id'] = $form["id"];
					break;

				case $student_master_form:
					$form_ids['student_master_form_id'] = $form["id"];
					break;

				case $teacher_form:
						$form_ids['teacher_public_form_id'] = $form["id"];
					break;

				case $teacher_master_form:
					$form_ids['teacher_master_form_id'] = $form["id"];
					break;

				default:
					break;
			}
		}

		// make sure all forms exist
		foreach ($form_ids as $key => $value) {
			if (!isset($value)) {
				wp_die('Error: The form titled ' . $all_competition_forms[$key] .
				" does not exist.");
			}
		}
		return $form_ids;
	}

  /**
   * This function will parse a form name for the competition title.
   *
   * In the event where only the entire title of a form is available, this
   * function will parse the form title and return the prepended title that
   * is unique to the student, student master, teacher, and teacher master form.
   * For example, if the title is a form called "February Competition 2/16/16
   * Student Registration", then this function will simply return "February
   * Competition 2/16/16". However, if this function receives a string that
   * is not a valid competition name (doesn't contain "Student Registration",
   * "Student Master", "Teacher Registration", or "Teacher Master"), then this
   * function will simply return false.
   *
   * @param   $form_name   String   The name of the form to parse.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_parse_form_name_for_title($form_name) {
    $found_match = false;

    // check if the title contains "Student Registration"
    if (strpos($form_name, STUDENT_REG) !== false) {
      $found_match = true;
    }

    // check if the title contains "Student Master"
    elseif (strpos($form_name, STUDENT_MAS) !== false) {
      $found_match = true;
    }

    // check if the title contains "Teacher Registration"
    elseif (strpos($form_name, TEACHER_REG) !== false) {
      $found_match = true;
    }

    // check if the title contains "Teacher Master"
    elseif (strpos($form_name, TEACHER_MAS) !== false) {
      $found_match = true;
    }

    // check to see if there is a match
    if ($found_match) {
      $form_words = explode(' ', $form_name);
      $title = null;

      // iterate through the complete name and strip away the important part
      for ($i = 0; $i < (count($form_words) - 2); $i++) {
        $title .= $form_words[$i];

        // don't add an extra space at the end of the last word
        if (($i + 1) !== (count($form_words) - 2)) {
          $title .= ' ';
        }
      }
    }

    return $title;
  }
}
