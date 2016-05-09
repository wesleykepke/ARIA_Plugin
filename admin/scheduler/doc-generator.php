<?php

/**
 * The document generator.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ABSPATH . "wp-content/plugins/ARIA/includes/class-aria-api.php");
require_once(ABSPATH . "wp-content/plugins/ARIA/admin/scheduler/class-aria-scheduler.php");
require_once(ABSPATH . "wp-content/plugins/ARIA/admin/scheduler/scheduler.php");
require_once(ABSPATH . "wp-content/plugins/ARIA/admin/scheduler/PHPRtfLite/lib/PHPRtfLite.php");
const USE_HTML_TAGS = true;

/*
$dir = dirname(__FILE__);
require_once($dir . "/PHPRtfLite/lib/PHPRtfLite.php");
*/

class Doc_Generator {

  /**
   * This function handles processing after the festival chairman has elected to
   * generate documents for a given competition.
   *
   * This function will, through the use of other functions, generate all
   * competition documents and send parents/teachers emails with information
   * regarding when their students and chilren are playing (respectively).
   *
   * @param 	Entry Object  $entry  The entry that was just submitted.
   * @param 	Form Object   $form   The form used to submit entries.
   * @param 	String/Array 	$confirmation 	The confirmation message to be filtered.
   * @param 	Bool 	$ajax 	Specifies if this form is configured to be submitted via AJAX.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function doc_gen_and_email($confirmation, $form,
  	                                       $entry, $ajax) {
    // only perform processing if it's the doc. gen. form
     if (!array_key_exists('isDocGenForm', $form)
        || !$form['isDocGenForm']) {
          return $confirmation;
    }

    // determine which competition to gen. docs. and send emails for
    $field_mapping = self::doc_gen_field_id_array();
    $title = $entry[strval($field_mapping['active_competitions'])];
    $related_forms = ARIA_API::aria_find_related_forms_ids($title);

    // locate that serialized version of the scheduler object
    $non_formatted_title = $title;
    $title = str_replace(' ', '_', $title);
    $file_path = ARIA_FILE_UPLOAD_LOC . $title . ".txt";
    if (file_exists($file_path)) {
      $scheduler = file_get_contents($file_path);
      $scheduler = unserialize($scheduler);
    }
    else {
    	wp_die("<h1>ERROR: It seems as if no such schedule has been created yet for " .
    		$entry[strval($field_mapping['active_competitions'])] . ". Have
    		you tried running the scheduler yet?</h1>");
    }

    // use the scheduler object to prepare the format(s) required for doc. generation
    $event_sections = $scheduler->get_section_info_for_doc_gen();
    //wp_die(print_r($event_sections));
    //self::rtf_activation_func($non_formatted_title, $event_sections);
    self::create_announcing_sheets($non_formatted_title, $event_sections);

    // send all participating teachers emails regarding when their students are playing
    // and their volunteer information
    /*
    Scheduling_Algorithm::send_teachers_competition_info($related_forms['teacher_master_form_id'],
    	                                                   $scheduler,
    	                                                   $entry[strval($field_mapping['active_competitions'])]);
*/
    // send all associated parents emails regarding when/where their child/children
    // are performing


    $confirmation = "Congratulations! You have just generated documents for $non_formatted_title.";
    return $confirmation;
  }

  /**
   * This function creates the document generation page.
   *
   * This function is responsible for creating and initializing all of the fields
   * that are required in the page for document generation.
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function aria_create_doc_gen_page() {
    // prevent form from being created twice
    if (ARIA_API::aria_get_doc_gen_form_id() !== -1) {
    	return;
    }

    $field_mapping = self::doc_gen_field_id_array();
    $form = new GF_Form(DOC_GEN_FORM_NAME, "");

    // drop-down menu of active competitions
    $active_competitions_field = new GF_Field_Select();
    $active_competitions_field->label = "Active Competitions";
    $active_competitions_field->id = $field_mapping['active_competitions'];
    $active_competitions_field->isRequired = false;
    $active_competitions_field->description = "Please select the name of the" .
    " competition that you would like to generate documents for and send " .
    " teachers/parents emails regarding scheduling information.";
    $active_competitions_field->descriptionPlacement = "above";
    $active_competitions_field->choices = array("Select from below");
    $form->fields[] = $active_competitions_field;

    // add a default submission message for the doc. gen. form
    $successful_submission_message = 'Congratulations! You have just successfully' .
    ' generated all competition documents and sent teachers/parents emails.';
    $form->confirmation['type'] = 'message';
    $form->confirmation['message'] = $successful_submission_message;

    // identify form as the scheduling page
    $form_arr = $form->createFormArray();
    $form_arr['isDocGenForm'] = true;

    // add form to dashboard
    $form_id = GFAPI::add_form($form_arr);
    if (is_wp_error($form_id)) {
      wp_die($form_id->get_error_message());
    }
    else {
      $doc_gen_url = ARIA_API::aria_publish_form(DOC_GEN_FORM_NAME,
      	                                         $form_id, CHAIRMAN_PASS, true);
    }
  }

  /**
   * Returns an associative array for field mappings of doc. gen. form.
   *
   * This function returns an array that maps all of the names of the
   * fields in the doc. gen. form to a unique integer so that they can be
   * referenced. Moreover, this array helps prevent the case where the
   * names of these fields are modified from the dashboard.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function doc_gen_field_id_array() {
    return array(
      'active_competitions' => 1
    );
  }

  /**
   * This function will pre-populate the drop-down menu on the doc. gen.
   * page with all of the active competitions.
   *
   * Whenever the festival chairman visits the page that is used for generating,
   * comp. docs., that page needs to have the drop-down menu of active competitions
   * pre-populated. This function is responsible for accomplishing that goal.
   *
   * @param $form 	Form Object 	The current form object.
   * @param $is_ajax 	Bool 	Specifies if the form is submitted via AJAX
   *
   * @since 1.0.0
   * @author KREW
   */
  public static function before_doc_gen_render($form, $is_ajax) {
    // Only perform prepopulation if it's the scheduler form
    if (!array_key_exists('isDocGenForm', $form)
        || !$form['isDocGenForm']) {
          return;
    }

    // Get all of the active competitions
    $all_active_competitions = ARIA_API::aria_get_all_active_comps();
    $competition_names = array();
    foreach ($all_active_competitions as $competition) {
      $single_competition = array(
        'text' => $competition['name'],
        'value' => $competition['name'],
        'isSelected' => false
      );
      $competition_names[] = $single_competition;
      unset($single_competition);
    }

    $doc_gen_field_mapping = self::doc_gen_field_id_array();
    $search_field = $doc_gen_field_mapping['active_competitions'];
    $name_field = ARIA_API::aria_find_field_by_id($form['fields'], $search_field);
    $form['fields'][$name_field]->choices = $competition_names;
  }

  /**
   * This function will create all of the announcing sheets for a competition.
   *
   *
   * @param   $event_name  String  The name of the competition to generate docs for.
   * @param   $event_sections  Array   The list of sections to use for doc gen.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function create_announcing_sheets($event_name, $event_sections) {
    // registers PHPRtfLite autoloader (spl)
    PHPRtfLite::registerAutoloader();

    /*
    echo "inside create_announcing_sheets<br>";
    echo($event_name);
    echo "<br>";
    wp_die(print_r($event_sections));
    */

    // rtf document instance
    $rtf = new PHPRtfLite();
    $rtf->setMargins(1.25, 1.25, 1.25, 1.25);

    // Set Fonts
    $styles = self::aria_styles($rtf);
    foreach($event_sections as $event_section) {
      // Add section
      $body = $rtf->addSection();

      // Title
      $body->writeText($event_name.'<br>', $styles['h1'], $styles['h1ParFormat'], USE_HTML_TAGS);
      $body->writeText('Announcing Sheet<br><br>', $styles['h2'], $styles['h1ParFormat'], USE_HTML_TAGS);

      //Body
      $title_table = $body->addTable();
      $title_table->addRow(0.5); // Section_Name
      $title_table->addRow(0.5); // Blank
      $title_table->addRow(0.5); // Judge
      $title_table->addRow(0.5); // Proctor
      $title_table->addRow(0.5); // Monitor
      $title_table->addRow(0.5); // Blank
      $title_table->addRow(0.5); // Section Order
      $title_table->addColumnsList(array(6, 12.5));
      $title_table->mergeCellRange(1, 1, 1, 2);

      $title_table->writeToCell(1, 1, $event_section['section_name'], $styles['h2']);
      $title_table->writeToCell(3, 1, 'Judge:', $styles['p']);
      $title_table->writeToCell(3, 2, $event_section['judge'], $styles['p']);
      $title_table->writeToCell(4, 1, 'Proctor:', $styles['p']);
      $title_table->writeToCell(4, 2, $event_section['proctor'], $styles['p']);
      $title_table->writeToCell(5, 1, 'Door Monitor:', $styles['p']);
      $title_table->writeToCell(5, 2, $event_section['monitor'], $styles['p']);
      $title_table->writeToCell(7, 1, 'Session Order:', $styles['h2']);

      $students_table = $body->addTable();
      $students_table->addColumnsList(array(1, 5, 12.5));

      $student_counter = 0;
      foreach($event_section['students'] as $student) {
        $students_table->addRow(0.5); // Student Name
        $students_table->addRow(0.5); // Blank
        $students_table->addRow(0.5); // Song 1
        $students_table->addRow(0.5); // Song 2
        $students_table->addRow(0.5); // Blank

        $students_table->mergeCellRange(5*$student_counter + 1, 1, 5*$student_counter + 1, 3);
        $students_table->writeToCell(5*$student_counter + 1, 1, $student['name'], $styles['h3']);
        $students_table->writeToCell(5*$student_counter + 3, 2, $student['song_one']['composer'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 3, 3, $student['song_one']['song'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 4, 2, $student['song_two']['composer'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 4, 3, $student['song_two']['song'], $styles['p']);

        $student_counter++;;
      }
    }

    // save rtf document as $event_name.rtf in the uploads folder
    $file_name = ABSPATH.'/wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_announcing_sheet_'.time().'.rtf';
    $rtf->save($file_name);

    // download the file
    if (file_exists($file_name)) {
      header('Content-Description: File Transfer');
      header('Content-Type: application/octet-stream');
      header('Content-Disposition: attachment; filename="'.basename($file_name).'"');
      header('Expires: 0');
      header('Cache-Control: must-revalidate');
      header('Pragma: public');
      header('Content-Length: ' . filesize($file_name));
      readfile($file_name);
      exit;
    }
  }

  /**
   * This function will define the styling used for the generated documents.
   *
   * For each of the RTF documents that is generated, there needs to be an
   * accompanying style. This function is responsible for defining that styles
   * for each form that will be created.
   *
   * @param   $rtf
   *
   * @author KREW
   * @since 1.0.0
   */
  private static function aria_styles($rtf) {
   // Initialize return value
   $styles = array();

   $font_face = 'Georgia';
   $foreground = '#000000';
   $background = '#FFFFFF';

   // h1
   $h1 = new PHPRtfLite_Font(18, $font_face, $foreground, $background);
   $h1->setBold();
   $styles['h1'] = $h1;

   $h1ParFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_CENTER);
   $styles['h1ParFormat'] = $h1ParFormat;

   // h2
   $h2 = new PHPRtfLite_Font(13, $font_face, $foreground, $background);
   $h2->setBold();
   $styles['h2'] = $h2;

   $h2ParFormat = new PHPRtfLite_ParFormat(PHPRtfLite_ParFormat::TEXT_ALIGN_LEFT);
   $styles['h2ParFormat'] = $h2ParFormat;

   // h3
   $h3 = new PHPRtfLite_Font(12, $font_face, $foreground, $background);
   $h3->setBold();
   $styles['h3'] = $h3;

   // p
   $p = new PHPRtfLite_Font(11, $font_face, $foreground, $background);
   $styles['p'] = $p;

   $styles['underlined_border'] = new PHPRtfLite_Border(
     $rtf,                                       // PHPRtfLite instance
     null, // left border: 2pt, green color
     null, // top border: 1pt, yellow color
     null, // right border: 2pt, red color
     new PHPRtfLite_Border_Format(1, '#000000')  // bottom border: 1pt, blue color
   );

   return $styles;
  }
}
