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
    self::generate_documents($non_formatted_title, $title, $event_sections);


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

  private static function generate_documents($non_formatted_title, $title, $event_sections) {
    $files = array();
    $files[] = array(
      'path' => self::create_announcing_sheets($non_formatted_title, $event_sections),
      'new_name' => strtolower(str_replace(' ', '_', $event_name).'_announcing_sheet.rtf')
    );
    $files[] = array(
      'path' => self::create_adjudication_forms($non_formatted_title, $event_sections),
      'new_name' => strtolower(str_replace(' ', '_', $event_name).'_adjudication_forms.rtf')
    );
    $files[] = array(
      'path' => self::create_results_sheets($non_formatted_title, $event_sections),
      'new_name' => strtolower(str_replace(' ', '_', $event_name).'_results_sheets.rtf')
    );
    $files[] = array(
      'path' => self::create_teacher_master($non_formatted_title, $event_sections),
      'new_name' => strtolower(str_replace(' ', '_', $event_name).'_teacher_master.rtf')
    );
    $files[] = array(
      'path' => self::create_session_assignments($non_formatted_title, array($event_sections)),
      'new_name' => strtolower(str_replace(' ', '_', $event_name).'_session_assignments.rtf')
    );
    $files[] = array(
      'path' => self::create_competition_csv($non_formatted_title, $event_sections),
      'new_name' => strtolower(str_replace(' ', '_', $event_name).'_competiton_overview.csv')
    );
    self::download_documents($title, $files);
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
    $form->description = "<h4>Please select from the drop-down menu the competition
    that you would like to generate documents for. Once you click on 'Submit',
    all competition documents will automatically begin downloading. Also, all
    of the teachers and parents will be emailed with information about when their
    students/children will be participating.</h4>";

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
   * @param   $event_name  String  The name of the competition to generate docs for.
   * @param   $event_sections  Array   The list of sections to use for doc gen.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function create_announcing_sheets($event_name, $event_sections) {
    // registers PHPRtfLite autoloader (spl)
    PHPRtfLite::registerAutoloader();

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
      $students_table->addColumnsList(array(1, 12.5, 5));

      $student_counter = 0;
      foreach($event_section['students'] as $student) {
        $students_table->addRow(0.5); // Student Name
        $students_table->addRow(0.5); // Blank
        $students_table->addRow(0.5); // Song 1
        $students_table->addRow(0.5); // Song 2
        $students_table->addRow(0.5); // Blank

        $students_table->mergeCellRange(5*$student_counter + 1, 1, 5*$student_counter + 1, 3);
        $students_table->writeToCell(5*$student_counter + 1, 1, ((string) ($student_counter + 1)).'. '.$student['name'], $styles['h3']);
        $students_table->writeToCell(5*$student_counter + 3, 2, $student['song_one']['song'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 3, 3, $student['song_one']['composer'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 4, 2, $student['song_two']['song'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 4, 3, $student['song_two']['composer'], $styles['p']);

        $student_counter++;;
      }
    }

    // save rtf document and download it from browser
    $file_name = ABSPATH.'wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_announcing_sheet.rtf';
    $rtf->save($file_name);
    return $file_name;
  }

  /**
   * This function will create all of the adjudication forms for a competition.
   *
   * @param   $event_name  String  The name of the competition to generate docs for.
   * @param   $event_sections  Array   The list of sections to use for doc gen.
   *
   * @since 1.0.0
   * @author KREW
   */
  private static function create_adjudication_forms($event_name, $event_sections) {
    // registers PHPRtfLite autoloader (spl)
    PHPRtfLite::registerAutoloader();

    // rtf document instance
    $rtf = new PHPRtfLite();
    $rtf->setMargins(1.25, 1.25, 1.25, 1.25);

    // Get styles
    $styles = self::aria_styles($rtf);

    foreach($event_sections as $event_section) {
      $sections = $event_section['section_name'];
      foreach($event_section['students'] as $student) {
        // Add section
        $body = $rtf->addSection();

        $title_table = $body->addTable();
        $title_table->setVerticalAlignmentForCellRange(
          PHPRtfLite_Table_Cell::VERTICAL_ALIGN_BOTTOM,
          1, // startRow
          1 // startColumn,
        );

        // Scaffold Title Table
        $title_table->addRow(0.5); // Title
        $title_table->addRow(0.5); // Blank
        $title_table->addRow(0.5); // Name
        $title_table->addRow(0.5); // Teacher
        $title_table->addRow(0.5); // Level
        $title_table->addRow(0.5); // Rating
        $title_table->addColumnsList(array(10, 2, 6.5));
        $title_table->mergeCellRange(1, 2, 1, 3);

        // Title Row
        $title_table->writeToCell(1,1, $event_name, $styles['h2']);
        $title_table->writeToCell(1,2, $event_section['section_name'], $styles['h2']);

        // Information Rows
        $title_table->writeToCell(5,1, 'For a Superior or Superior with Distinction Rating,', $styles['p']);
        $title_table->writeToCell(6,1, 'indicate the piece to be played in the Command Performance.', $styles['p']);
        $title_table->writeToCell(3,2, 'Performer: ', $styles['p']);
        $title_table->writeToCell(3,3, $student['name'], $styles['p']);
        $title_table->writeToCell(4,2, 'Teacher: ', $styles['p']);
        $title_table->writeToCell(4,3, $student['teacher'], $styles['p']);
        $title_table->writeToCell(5,2, 'Level: ', $styles['p']);
        $title_table->writeToCell(5,3, $student['level'], $styles['p']);
        $title_table->writeToCell(6,2, 'Rating: ', $styles['p']);
        $title_table->getCell(6,2)->setBorder($styles['underlined_border']);
        $title_table->getCell(6,3)->setBorder($styles['underlined_border']);

        $title_table->setBorderForCellRange($styles['underlined_border'], 1, 1, 1, 2);

        $songs_table = $body->addTable();
        $songs_table->setVerticalAlignmentForCellRange(
          PHPRtfLite_Table_Cell::VERTICAL_ALIGN_BOTTOM,
          1, // startRow
          1 // startColumn,
        );

        // Scaffold Songs Table
        $songs_table->addRow(0.5); // Song 1
        $songs_table->addRow(8.25); // Song 1
        $songs_table->addRow(0.5); // Song 2
        $songs_table->addRow(8.25); // Song 2
        $songs_table->addRow(4.5); // Comments
        $songs_table->addRow(0.5); // Judge Name
        $songs_table->addColumnsList(array(10, 8.5));

        // Songs
        $songs_table->writeToCell(1,1, '__ '.$student['song_one']['composer'], $styles['h3']);
        $songs_table->writeToCell(1,2, $student['song_one']['song'], $styles['h3']);
        $songs_table->writeToCell(3,1, '__ '.$student['song_two']['composer'], $styles['h3']);
        $songs_table->writeToCell(3,2, $student['song_two']['song'], $styles['h3']);
        $songs_table->writeToCell(5,1, 'General Comments:', $styles['h3']);
        $songs_table->writeToCell(6,1, 'Judge:', $styles['h3']);
        $songs_table->writeToCell(6,2, 'Signature:', $styles['h3']);
        $songs_table->setBorderForCellRange($styles['underlined_border'], 6, 1, 6, 2);
      }
    }

    // save rtf document and download it from browser
    $file_name = ABSPATH.'wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_adjudication_forms.rtf';
    $rtf->save($file_name);
    return $file_name;
  }

  function create_results_sheets($event_name, $event_sections) {
    // registers PHPRtfLite autoloader (spl)
    PHPRtfLite::registerAutoloader();

    // rtf document instance
    $rtf = new PHPRtfLite();
    $rtf->setMargins(1.25, 1.25, 1.25, 1.25);

    // Set Fonts
    $styles = aria_styles($rtf);


    foreach($event_sections as $event_section) {
      // Add section
      $body = $rtf->addSection();

      // Title
      $body->writeText($event_name.'<br><br>', $styles['h1'], $styles['h1ParFormat'], USE_HTML_TAGS);
      $body->writeText($event_section['section_name'].'<br>', $styles['h2'], $styles['h2ParFormat'], USE_HTML_TAGS);
      $body->writeText('Judge: '.$event_section['judge'].'<br>', $styles['p'], $styles['h2ParFormat'], USE_HTML_TAGS);

      $students_table = $body->addTable();
      $students_table->addColumnsList(array(1, 5, 12.5));

      $student_counter = 0;
      foreach($event_section['students'] as $student) {
        $students_table->addRow(0.5); // Student Name
        $students_table->addRow(0.5); // Blank
        $students_table->addRow(0.5); // Song 1
        $students_table->addRow(0.5); // Song 2
        $students_table->addRow(0.5); // Blank

        $students_table->mergeCellRange(5*$student_counter + 1, 1, 5*$student_counter + 1, 2);
        $students_table->writeToCell(5*$student_counter + 1, 1, $student['name'], $styles['h3']);
        $students_table->writeToCell(5*$student_counter + 1, 3, '___Sw/D ___S ___E ___NA ___NC ___W', $styles['h3']);
        $students_table->writeToCell(5*$student_counter + 3, 2, '___ '.$student['song_one']['composer'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 3, 3, $student['song_one']['song'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 4, 2, '___ '.$student['song_two']['composer'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 4, 3, $student['song_two']['song'], $styles['p']);

        $student_counter++;;
      }
    }

    // save rtf document to hello_world.rtf
    $rtf->save(ABSPATH.'wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_results_sheet.rtf');

    return ABSPATH.'wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_results_sheet.rtf';
  }

  function create_teacher_to_student_map ($event_sections){
    $teacher_array = array();

    foreach ($event_sections as $event_section) {
      $performance_number = 1;
      foreach ($event_section['students'] as $student) {
        // Update student information.
        $student_copy = $student;
        $student_copy['section'] = $event_section['section_name'];
        $student_copy['performance_number'] = $performance_number++;

        // Create teacher map if not exists.
        if (!key_exists($student['teacher'], $teacher_array)) {
          $teacher_array[$student['teacher']] = array();
        }

        // Add student to it's teacher array.
        $teacher_array[$student['teacher']][] = $student_copy;
      }
    }

    return $teacher_array;
  }

  function create_teacher_master ($event_name, $event_sections, $additional_information = '', $command_performance_location = null, $command_performance_date = null){
    // Change sections to teacher maps.
    $teacher_array = create_teacher_to_student_map($event_sections);

    // registers PHPRtfLite autoloader (spl)
    PHPRtfLite::registerAutoloader();

    // rtf document instance
    $rtf = new PHPRtfLite();
    $rtf->setMargins(1.25, 1.25, 1.25, 1.25);

    // Get styles
    $styles = aria_styles($rtf);

    foreach ($teacher_array as $teacher_name => $students) {
      // Add section
      $body = $rtf->addSection();
      $footer = $body->addFooter();
      $footer->writeText($additional_information);

      // Title
      $body->writeText($event_name.'<br><br>', $styles['h1'], $styles['h1ParFormat'], USE_HTML_TAGS);
      $body->writeText($teacher_name.'<br>', $styles['h2'], $styles['h2ParFormat'], USE_HTML_TAGS);

      if(isset($command_performance_location) && isset($command_performance_date)) {
        $body->writeText('Command Performance '.$command_performance_location.', '.$command_performance_date.'<br>', $styles['h2'], $styles['h2ParFormat'], USE_HTML_TAGS);
        $body->writeText('The Command Performance schedule for your student(s) is as follows:<br>', $styles['p'], $styles['h2ParFormat'], USE_HTML_TAGS);
      } else {
        $body->writeText('The following students of yours have registered for the '.$event_name.'<br>', $styles['p'], $styles['h2ParFormat'], USE_HTML_TAGS);
      }

      $students_table = $body->addTable();
      $students_table->addColumnsList(array(1, 5, 12.5));

      $student_counter = 0;
      foreach($students as $student) {
        $students_table->addRow(0.5); // Name
        $students_table->addRow(0.5); // Song 1
        $students_table->addRow(0.5); // Song 2
        $students_table->addRow(0.5); // Blank
        $students_table->addRow(0.5); // Blank

        $students_table->mergeCellRange(5*$student_counter + 1, 1, 5*$student_counter + 1, 3);
        $students_table->writeToCell(5*$student_counter + 1, 1, $student['name'].', '.$student['section'].', performer # '.$student['performance_number'], $styles['h3']);
        $students_table->writeToCell(5*$student_counter + 2, 2, $student['song_one']['composer'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 2, 3, $student['song_one']['song'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 3, 2, $student['song_two']['composer'], $styles['p']);
        $students_table->writeToCell(5*$student_counter + 3, 3, $student['song_two']['song'], $styles['p']);

        $student_counter++;
      }
    }

    // Write to file
    $file_name = (isset($command_performance_location) && isset($command_performance_date)) ? 'command_performance' : '';
    $file_name .= 'teacher_master';
    $rtf->save(ABSPATH.'wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_'.$file_name.'.rtf');
    return ABSPATH.'wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_'.$file_name.'.rtf';
  }

  function create_session_assignments($event_name, $days) {
    // rtf document instance
    $rtf = new PHPRtfLite();
    $rtf->setMargins(1.25, 1.25, 1.25, 1.25);
    $rtf->setLandscape();

    // Get styles
    $styles = aria_styles($rtf);

    foreach($days as $day) {
      // Add section
      $body = $rtf->addSection();
      $session_table = $body->addTable();
      $session_table->addColumnsList(array(3.5, 4.0, 1.5, 3.5, 4.0, 1.5, 3.5, 4.0));

      $index = 0;
      foreach($day as $section) {
        if ($index % 3 == 0) {
          $session_table->addRow(0.4);
          $session_table->addRow(0.4);
          $session_table->addRow(0.4);
          $session_table->addRow(0.8);
          $session_table->addRow(0.4);
          $session_table->mergeCellRange(5*floor($index/3) + 1, 1, 5*floor($index/3) + 1, 2);
          $session_table->mergeCellRange(5*floor($index/3) + 1, 4, 5*floor($index/3) + 1, 5);
          $session_table->mergeCellRange(5*floor($index/3) + 1, 7, 5*floor($index/3) + 1, 8);
        }

        $session_table->writeToCell(5*floor($index/3) + 1, ($index%3)*3 + 1, $section['section_name'], $styles['h3']);
        $session_table->writeToCell(5*floor($index/3) + 2, ($index%3)*3 + 1, 'Proctor:', $styles['p']);
        $session_table->writeToCell(5*floor($index/3) + 3, ($index%3)*3 + 1, 'Door Monitor:', $styles['p']);
        $session_table->writeToCell(5*floor($index/3) + 4, ($index%3)*3 + 1, 'Judge:', $styles['p']);
        $session_table->writeToCell(5*floor($index/3) + 2, ($index%3)*3 + 2, $section['proctor'], $styles['p']);
        $session_table->writeToCell(5*floor($index/3) + 3, ($index%3)*3 + 2, $section['monitor'], $styles['p']);
        $session_table->writeToCell(5*floor($index/3) + 4, ($index%3)*3 + 2, $section['judge'], $styles['p']);
        $index++;
      }
    }
    $rtf->save(ABSPATH.'/wp-content/uploads'.strtolower(str_replace(' ', '_', $event_name)).'_session_assignments.rtf');
    return ABSPATH.'/wp-content/uploads'.strtolower(str_replace(' ', '_', $event_name)).'_session_assignments.rtf';
  }

  function create_competition_csv($event_name, $event_sections) {
    $file = fopen(ABSPATH.'/wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_competiton_overview.csv', 'w');

    fputcsv($file, array(
      'Section',
      'Teacher',
      'Student',
      'Level',
      'Piece 1',
      'Piece 2',
      'Format',
      'Judge',
      'Proctor',
      'Door'
    ));

    foreach($event_sections as $event_section) {
      $section_info_printed = false;
      foreach($event_section['students'] as $student) {
        fputcsv($file, array(
          $section_info_printed
            ? ''
            : array_key_exists('section_name', $event_section)
              ? $event_section['section_name']
              : '',
          array_key_exists('teacher', $student) ? $student['teacher'] : '',
          array_key_exists('name', $student) ? $student['name']: '',
          array_key_exists('level', $student) ? $student['level'] : '',
          array_key_exists('song_one', $student) ? $student['song_one']['song'] : '',
          array_key_exists('song_two', $student) ? $student['song_two']['song'] : '',
          array_key_exists('format', $student) ? $student['format'] : '',
          $section_info_printed
            ? ''
            : array_key_exists('judge', $event_section)
              ? $event_section['judge']
              : '',
          $section_info_printed
            ? ''
            : array_key_exists('proctor', $event_section)
              ? $event_section['proctor']
              : '',
          $section_info_printed
            ? ''
            : array_key_exists('monitor', $event_section)
              ? $event_section['monitor']
              : '',
        ));
        $section_info_printed = true;
      }
      fputcsv($file, array(' '));
    }

    fclose($file);

    return ABSPATH.'/wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_competiton_overview.csv';
  }

  /**
   * This function will allow the files to be downloaded from the website.
   *
   * When the festival chairman clicks 'Submit', there will be a multitude of
   * files that are downloaded from ARIA. This function makes that download
   * process possible.
   *
   * @param   $file_name  String  The name of the file to be downloaded.
   *
   * @author KREW
   * @since 1.0.0
   */
  private static function download_documents($event_name, $files) {
    $zipname = ABSPATH.'/wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_generated_documents.zip';
    $zip = new ZipArchive;
    $zip->open($zipname, file_exists ($zipname) ? ZipArchive::OVERWRITE : ZipArchive::CREATE);
    foreach($files as $file) {
      $zip->addFile($file['path'], $file['new_name']);
    }
    $zip->close();
    /*
      foreach ($files as $file_name) {
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
        else {
          wp_die("Inside download_document: no such file exists.");
        }
      }

      wp_die();
    */

    header('Content-Description: File Transfer');
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename='.$zipname);
    header('Content-Length: ' . filesize($zipname));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    readfile($zipname);
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
