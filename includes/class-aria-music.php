<?php

/**
 * The file that provides music uploading/downloading functionality.
 *
 * A class definition that includes attributes and functions that allow the
 * festival chairman to upload and download music.
 *
 * @link       http://wesleykepke.github.io/ARIA/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

//require_once("class-aria-api.php");

/**
 * The create competition class.
 *
 * @since      1.0.0
 * @package    ARIA
 * @subpackage ARIA/includes
 * @author     KREW
 */
class ARIA_Music {

  /**
   * This function will parse the contents of the csv file and upload content to
   * the NNMTA music database.
   *
   * Using the csv file that the user has uploaded, this function will parse
   * through the music content for each song and add it to the NNMTA music
   * database.
   *
   * @param Entry Object  $entry  The entry object from the upload form.
   * @param Form Object   $form   The form object that contains $entry.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_add_music_from_csv($confirmation, $form, $entry, $ajax) {
    // only perform processing if the music uploading form was used
    if (!array_key_exists('isMusicUploadForm', $form)
        || !$form['isMusicUploadForm']) {
      return $confirmation;
    }

    // check if the form for storing NNMTA music exists
    $music_db_form_id = ARIA_API::aria_get_nnmta_database_form_id();
    self::aria_remove_all_music_from_nnmta_database($music_db_form_id);
    $music_db_form_id = ARIA_API::aria_get_nnmta_database_form_id();

    // locate the full path of the csv file
    $csv_music_file = ARIA_API::aria_get_music_csv_file_path($entry, $form);

    // parse csv file and add all music data to an array
    $all_songs = array();
    if (($file_ptr = fopen($csv_music_file, "r")) !== FALSE) {
      while (($single_song_data = fgetcsv($file_ptr, 1000, ",")) !== FALSE) {
        $single_song = array();
        for ($i = 1; $i <= count($single_song_data); $i++) {
          $single_song[(string) $i] = $single_song_data[$i - 1];
        }
        $all_songs[] = $single_song;
        unset($single_song);
      }
    }
    else {
      wp_die("Error: File named " . $csv_music_file . " does not exist.");
    }

    // add all song data from array into the database
    $new_song_ids = GFAPI::add_entries($all_songs, ARIA_API::aria_get_nnmta_database_form_id());
    if (is_wp_error($new_song_ids)) {
      wp_die($new_song_ids->get_error_message());
    }

    // inform the user how many songs were uploaded
    $confirmation = 'Congratulations! You have just uploaded ';
    $confirmation .= strval(count($all_songs)) . ' songs to the NNMTA music database.';

    // remove filename from upload folder
    unlink($csv_music_file);
    unset($all_songs);
    return $confirmation;
  }

  /**
   * This function is responsible for creating the NNMTA music uploading form
   * if it does not exist.
   *
   * This function is intended to be used in the event where the form for
   * uploading music does not previously exist. If no such form exists, this
   * function will create the form used for uploading music.
   *
   * @author KREW
   * @since 1.0.0
   */
  public static function aria_create_music_upload_form() {
    // don't create form if it already exists
    if (ARIA_API::aria_get_song_upload_form_id() !== -1) {
      return;
    }

    $form_name = MUSIC_UPLOAD_FORM_NAME;
    $form = new GF_FORM($form_name, "");

    // add a description for the upload music form
    $form->description = "Welcome! Please browse your computer for a CSV file" .
    " containing the music that you would like to upload. The contents of the" .
    " CSV file that you upload will <b>REPLACE</b> all music in the NNTMA music" .
    " database, so please be careful. Also, the upload process may take a while, so" .
    " please be patient. You will see a confirmation message once the process is complete.";

    // CSV file upload
    $csv_file_upload = new GF_Field_FileUpload();
    $csv_file_upload->label = CSV_UPLOAD_FIELD_NAME;
    $csv_file_upload->id = 1;
    $csv_file_upload->isRequired = true;
    $form->fields[] = $csv_file_upload;

    // add a custom confirmation
    $successful_submission_message = 'Congratulations! You have just successfully' .
    ' uploaded new music to the NNMTA music database';
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

    // Identify form as a music uploading form
    $form_array = $form->createFormArray();
    $form_array['isMusicUploadForm'] = true;

    // Add form to dashboard
    $new_form_id = GFAPI::add_form($form_array);
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }
    else {
      // publish this form so it can be used on the front end
      ARIA_API::aria_publish_form(MUSIC_UPLOAD_FORM_NAME, $new_form_id, CHAIRMAN_PASS);

      // create the form that is used to store the NNMTA music
      self::aria_create_nnmta_music_form();
    }
  }

  /**
   * This function is responsible for creating the NNMTA music form if it does
   * not previously exist.
   *
   * This function is intended to be used in the event where the festival
   * chairman tries to upload music to the NNMTA database but no such form
   * exists for adding music.
   *
   * @author KREW
   * @since 1.0.0
   */
   private static function aria_create_nnmta_music_form() {
     $nnmta_music_form_name = NNMTA_MUSIC_DATABASE_NAME;
     $nnmta_music_form = new GF_Form($nnmta_music_form_name, "");
     $field_id_arr = self::aria_music_field_id_array();

     // NNMTA song name
     $song_name_field = new GF_Field_Text();
     $song_name_field->label = "Song Name";
     $song_name_field->id = $field_id_arr['song_name'];
     $song_name_field->isRequired = true;
     $nnmta_music_form->fields[] = $song_name_field;

     // NNMTA song composer
     $song_composer_field = new GF_Field_Text();
     $song_composer_field->label = "Composer Name";
     $song_composer_field->id = $field_id_arr['song_composer'];
     $song_composer_field->isRequired = true;
     $nnmta_music_form->fields[] = $song_composer_field;

     // NNMTA song level
     $song_level_field = new GF_Field_Text();
     $song_level_field->label = "Song Level";
     $song_level_field->id = $field_id_arr['song_level'];
     $song_level_field->isRequired = true;
     $nnmta_music_form->fields[] = $song_level_field;

     // NNMTA period level
     $song_period_field = new GF_Field_Text();
     $song_period_field->label = "Song Period";
     $song_period_field->id = $field_id_arr['song_period'];
     $song_period_field->isRequired = true;
     $nnmta_music_form->fields[] = $song_period_field;

     // NNMTA song catalog
     $song_catalog_field = new GF_Field_Text();
     $song_catalog_field->label = "Song Catalog";
     $song_catalog_field->id = $field_id_arr['song_catalog'];
     $song_catalog_field->isRequired = false;
     $nnmta_music_form->fields[] = $song_catalog_field;
     $nnmta_music_form->confirmation['type'] = 'message';
     $nnmta_music_form->confirmation['message'] = 'Successful';
     
     // add the new form to the festival chairman's dashboard
     $nnmta_music_form_array = $nnmta_music_form->createFormArray();
     $nnmta_music_form_array['isMusicUploadForm'] = false;
     $new_form_id = GFAPI::add_form($nnmta_music_form_array);

     // make sure the new form was added without error
     if (is_wp_error($new_form_id)) {
       wp_die($new_form_id->get_error_message());
     }
   }

  /**
   * This function will remove all of the music from the NNMTA music database.
   *
   * This function was created to support the scenario when the festival
   * chariman needs to update the music in the NNMTA music database. In order to
   * do this, all of the existing data is removed from the database prior to
   * adding all of the new data. This ensures that the new data is added
   * appropriately without accidentally adding old, possibly unwanted music
   * data.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function aria_remove_all_music_from_nnmta_database($music_db_form_id) {
    // define criteria to obtain all music
    $sorting = array();
    $paging = array('offset' => 0, 'page_size' => 2000);
    $total_count = 0;
    $search_criteria = array();

    // get all of the music in the nnmta music database
    if (GFAPI::delete_form($music_db_form_id)) {
      self::aria_create_nnmta_music_form();
    }
    /*
    $all_songs = GFAPI::get_entries($music_db_form_id, $search_criteria,
                                    $sorting, $paging, $total_count);

    foreach ($all_songs as $song) {
      $result = GFAPI::delete_entry($song);
      if (is_wp_error($result)) {
        wp_die('ERROR: Unable to delete all songs. Please repeat the music
          upload process');
      }
    } */
  }

  /**
   * This function will change the default file path for uploaded files.
   *
   * In order to upload music from a file, we need to know where the music
	 * file resides. This function will set a pre-determined file path so
	 * the music data can be read from.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_modify_upload_path($path_info, $form_id){
  	$path_info['path'] = '/var/www/html/wp-content/uploads/testpath/';
  	return $path_info;
  }

	/**
   * This function will change the default file path for uploaded files.
   *
   * In order to upload music from a file, we need to know where the music
	 * file resides. This function will set a pre-determined file path so
	 * the music data can be read from.
   *
   * @since 1.0.0
   * @author KREW
   */
	public static function aria_music_field_id_array() {
		return array(
			'song_name' => 4,
			'song_composer' => 3,
			'song_level' => 1,
			'song_period' => 2,
			'song_catalog' => 5
		);
	}
}
