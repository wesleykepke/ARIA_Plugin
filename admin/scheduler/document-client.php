<?php

require_once("class-aria-scheduler.php");
require_once("./PHPRtfLite/lib/PHPRtfLite.php");
const USE_HTML_TAGS = true;
define(ABSPATH, $_SERVER['DOCUMENT_ROOT'].'/');

function generate_documents() {
  // determine the file path of the associated competition
  $title = str_replace(' ', '_', $_POST['compName']);
  $file_path = dirname(__FILE__);
  //echo "FILE PATH: $file_path \n";
  //echo "SERVER: " . ABSPATH . "\n";
  $parsed_file_path = explode('/', $file_path);
  $file_path = "";
  $parsed_file_path_index = 0;
  while ($parsed_file_path[$parsed_file_path_index] != "plugins") {
    $file_path .= $parsed_file_path[$parsed_file_path_index] . "/";
    $parsed_file_path_index++;
  }
  $file_path .= "uploads/$title.txt";

  ////echo print_r($_POST);
  ////echo "File path: $file_path <br>";

  // read the serialized Scheduler object from file
  if (file_exists($file_path)) {
    $scheduler = file_get_contents($file_path);
    $scheduler = unserialize($scheduler);

    // get the section information from the scheduler
    $event_sections = $scheduler->get_section_info_for_doc_gen();
    generate_all_documents($title, $_POST['compName'], $event_sections);

    // iterate through the scheduler object and update the information with the new data
    //echo "Documents have been generated.\n\n";
  }
  else {
    //echo "Documents have not been generated because file doesn't exist";
  }
}

function generate_all_documents($non_formatted_title, $title, $event_sections) {
  $files = array();
  $files[] = array(
    'path' => create_announcing_sheets($non_formatted_title, $event_sections),
    'new_name' => strtolower(str_replace(' ', '_', $non_formatted_title).'_announcing_sheet.rtf')
  );
  $files[] = array(
    'path' => create_adjudication_forms($non_formatted_title, $event_sections),
    'new_name' => strtolower(str_replace(' ', '_', $non_formatted_title).'_adjudication_forms.rtf')
  );
  $files[] = array(
    'path' => create_results_sheets($non_formatted_title, $event_sections),
    'new_name' => strtolower(str_replace(' ', '_', $non_formatted_title).'_results_sheets.rtf')
  );
  $files[] = array(
    'path' => create_teacher_master($non_formatted_title, $event_sections),
    'new_name' => strtolower(str_replace(' ', '_', $non_formatted_title).'_teacher_master.rtf')
  );
  $files[] = array(
    'path' => create_session_assignments($non_formatted_title, array($event_sections)),
    'new_name' => strtolower(str_replace(' ', '_', $non_formatted_title).'_session_assignments.rtf')
  );
  $files[] = array(
    'path' => create_competition_csv($non_formatted_title, $event_sections),
    'new_name' => strtolower(str_replace(' ', '_', $non_formatted_title).'_competiton_overview.csv')
  );
  download_documents($title, $files);
}

function create_announcing_sheets($event_name, $event_sections) {
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

function create_adjudication_forms($event_name, $event_sections) {
  // registers PHPRtfLite autoloader (spl)
  PHPRtfLite::registerAutoloader();

  // rtf document instance
  $rtf = new PHPRtfLite();
  $rtf->setMargins(1.25, 1.25, 1.25, 1.25);

  // Get styles
  $styles = aria_styles($rtf);

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

function download_documents($event_name, $files) {
  $zipname = ABSPATH.'/wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_generated_documents.zip';
  $zip = new ZipArchive;
  if ($zip->open($zipname, file_exists ($zipname) ? ZipArchive::OVERWRITE : ZipArchive::CREATE)) {
    foreach($files as $file) {
      $zip->addFile($file['path'], $file['new_name']);
    }
    $zip->close();
  }
  else {
    echo "Zip failed to open.";
  }

  //header('Content-Description: File Transfer');
  /*
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Content-Type: application/force-download");
  header("Content-Type: application/octet-stream");
  header("Content-Type: application/download");
  header("Content-Disposition: attachment; filename=\"".$zipname."\"");
  header("Content-Transfer-Encoding: binary");
  header("Content-Length: ".filesize($zipname));
  readfile($zipname);
  */
  //$zipname = str_replace("'\'", "", $zipname);
  echo '/wp-content/uploads/'.strtolower(str_replace(' ', '_', $event_name)).'_generated_documents.zip';
}

function aria_styles($rtf) {
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

generate_documents();
