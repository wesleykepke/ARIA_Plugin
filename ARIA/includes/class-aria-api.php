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
    $create_competition_form_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] === CREATE_COMPETITION_FORM_NAME) {
	      $create_competition_form_id = $form['id'];
      }
    }

		return $create_competition_form_id;
  }

	/**
	 * This function will find the ID of the form used to upload songs to the
	 * database.
	 *
	 * This function will iterate through all of the active form objects and return
	 * the ID of the form that is used to upload music to the database.
	 *
	 * @since 1.0.0
	 * @author KREW
	 */
	public static function aria_get_song_upload_form_id() {
    $upload_form_name_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] === MUSIC_UPLOAD_FORM_NAME) {
        $upload_form_name_id = $form['id'];
      }
    }

    return $upload_form_name_id;
	}

	/**
	 * This function will find the ID of the form used as the NNMTA music database.
	 *
	 * This function will iterate through all of the active form objects and return
	 * the ID of the form that is used to store all NNMTA music.
	 *
	 * @since 1.0.0
	 * @author KREW
	 */
	public static function aria_get_nnmta_database_form_id() {
    $nnmta_music_database_form_id = -1;
    $all_active_forms = GFAPI::get_forms();

    foreach ($all_active_forms as $form) {
      if ($form['title'] === NNMTA_MUSIC_DATABASE_NAME) {
        $nnmta_music_database_form_id = $form['id'];
      }
    }

    return $nnmta_music_database_form_id;
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
    // find the field entry used to upload the csv file
    $music_csv_field_id = NULL;
    foreach ($form['fields'] as $field) {
      if ($field['label'] === CSV_UPLOAD_FIELD_NAME) {
        $music_csv_field_id = $field['id'];
      }
    }

    if (!isset($music_csv_field_id)) {
      wp_die('Form named \'' . $form['title'] . '\' does not have a field named \''
      . CSV_UPLOAD_FIELD_NAME . '\'. Please create this field and try uploading
      music again.');
    }

    // parse the url and obtain the file path for the csv file
    $csv_file_url = $entry[strval($music_csv_field_id)];
    $csv_file_url_atomic_strings = explode('/', $csv_file_url);
    $csv_full_file_path = '/var/www/html/wp-content/uploads/testpath/'; // this may need to change
    $csv_full_file_path .= $csv_file_url_atomic_strings[count($csv_file_url_atomic_strings) - 1];
    return $csv_full_file_path;
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
		$title = null;

		foreach ($all_forms as $form) {
      if ($form["id"] == $form_id) {
				$title = $form["title"];
			}
		}

		if (!isset($title)) {
			$title = -1;
		}

		return $title;
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
		elseif (strpos($form_name, STUDENT_MASTER) !== false) {
			$found_match = true;
		}

		// check if the title contains "Teacher Registration"
		elseif (strpos($form_name, TEACHER_REG) !== false) {
			$found_match = true;
		}

		// check if the title contains "Teacher Master"
		elseif (strpos($form_name, TEACHER_MASTER) !== false) {
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
