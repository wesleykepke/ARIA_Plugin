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
  public static function aria_add_music_from_csv($entry, $form) {
    // only perform processing if the music uploading form was used
    if (!array_key_exists('isMusicUploadForm', $form)
        || !$form['isMusicUploadForm']) {
      return;
    }

    // check if the form for uploading exists
    $music_upload_form_id = ARIA_API::aria_get_song_upload_form_id();
    if ($music_upload_form_id == -1) {
      self::aria_create_music_upload_form();
    }

    // check if the form for storing NNMTA music exists
    $music_db_form_id = ARIA_API::aria_get_nnmta_database_form_id();

    //wp_die('top: ' . $music_db_form_id);
    if ($music_db_form_id === -1) {
      self::aria_create_nnmta_music_form();
    }

    // locate the full path of the csv file
    $csv_music_file = ARIA_API::aria_get_music_csv_file_path($entry, $form);
    //wp_die("Music file path: " . $csv_music_file);

    // parse csv file and add all music data to an array
    $all_songs = array();
    if (($file_ptr = fopen($csv_music_file, "r")) !== FALSE) {
      // remove all data that is already in the database
      //aria_remove_all_music_from_nnmta_database();

      // add new music
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

    //wp_die(print_r($all_songs));

    // add all song data from array into the database
    $new_song_ids = GFAPI::add_entries($all_songs, ARIA_API::aria_get_nnmta_database_form_id());
    if (is_wp_error($new_song_ids)) {
      wp_die($new_song_ids->get_error_message());
    }

    // remove filename from upload folder
    //print_r($all_songs);
    unlink($csv_music_file);
    unset($all_songs);
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
    $form_name = MUSIC_UPLOAD_FORM_NAME;
    $form = new GF_FORM($form_name, "");

    // CSV file upload
    $csv_file_upload = new GF_Field_FileUpload();
    $csv_file_upload->label = CSV_UPLOAD_FIELD_NAME;
    $csv_file_upload->id = 1;
    $csv_file_upload->isRequired = true;
    $form->fields[] = $csv_file_upload;

    // Identify form as a music uploading form
    $form_array = $form->createFormArray();
    $form_array['isMusicUploadForm'] = true;

    // Add form to dashboard
    $new_form_id = GFAPI::add_form($form_array);
    if (is_wp_error($new_form_id)) {
      wp_die($new_form_id->get_error_message());
    }
    else {
      ARIA_API::aria_publish_form(MUSIC_UPLOAD_FORM_NAME, $new_form_id, CHAIRMAN_PASS);
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

     // add the new form to the festival chairman's dashboardi
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
  private static function aria_remove_all_music_from_nnmta_database() {
    // to be implemented
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
