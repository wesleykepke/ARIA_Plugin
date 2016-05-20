<?php

require_once("class-aria-scheduler.php");
define(ABSPATH, $_SERVER['DOCUMENT_ROOT'].'/');

function determine_function_call() {
  switch ($_POST['funcToCall']) {
    case "update_scores":
      update_scores();
    break;

    case "get_trophy_list":
      get_trophy_list();
    break;

    case "get_command_students":
      get_command_students();
    break;
  }
}

/**
 * Function for updating the scores of students in the scheduler.
 */
function update_scores() {
  // determine the file path of the associated competition
  $title = str_replace(' ', '_', $_POST['compName']);
  $file_path = dirname(__FILE__);
  $parsed_file_path = explode('/', $file_path);
  $file_path = "";
  $parsed_file_path_index = 0;
  while ($parsed_file_path[$parsed_file_path_index] != "plugins") {
    $file_path .= $parsed_file_path[$parsed_file_path_index] . "/";
    $parsed_file_path_index++;
  }
  $file_path .= "uploads/$title.txt";

  // read the serialized Scheduler object from file
  if (file_exists($file_path)) {
    $scheduler = file_get_contents($file_path);
    $scheduler = unserialize($scheduler);

    // update all of the scores in the scheduler
    $scheduler->update_student_scores($_POST['students']);

    // get the new HTML to display to the user
    $new_html = $scheduler->get_score_input_string(true);

    // write the scheduler object back out to file
    $scheduler_data = serialize($scheduler);
    $fp = fopen($file_path, 'w+');
    if ($fp) {
      fwrite($fp, $scheduler_data);
      fclose($fp);
      echo $new_html;
    }
    else {
      echo "Did not save correctly.";
    }
  }
  else {
    echo "Scores have not been updated because scheduler file doesn't exist.";
  }
}

/**
 * Function for downloading a trophy list (all students with "SD" or "S").
 */
function get_trophy_list() {
  // determine the file path of the associated competition
  $title = str_replace(' ', '_', $_POST['compName']);
  $file_path = dirname(__FILE__);
  $parsed_file_path = explode('/', $file_path);
  $file_path = "";
  $parsed_file_path_index = 0;
  while ($parsed_file_path[$parsed_file_path_index] != "plugins") {
    $file_path .= $parsed_file_path[$parsed_file_path_index] . "/";
    $parsed_file_path_index++;
  }
  $trophy_list_file = $file_path;
  $file_path .= "uploads/$title.txt";

  // read the serialized Scheduler object from file
  if (file_exists($file_path)) {
    $scheduler = file_get_contents($file_path);
    $scheduler = unserialize($scheduler);

    // create the trophy list file and download it
    $trophy_list_file = ABSPATH . "wp-content/uploads/$title" . "_Trophy_List.txt";
    $scheduler->create_trophy_list($trophy_list_file);

    // create a zip file
    $zipname = ABSPATH . "wp-content/uploads/$title" . "_Trophy_List.zip";
    $zip = new ZipArchive;
    if ($zip->open($zipname, file_exists ($zipname) ? ZipArchive::OVERWRITE : ZipArchive::CREATE)) {
      $zip->addFile($trophy_list_file, $title . "_Trophy_List.txt");
      $zip->close();
    }
    else {
      echo "Zip failed to open.";
    }

    // force download the zip file from the browser
    echo "wp-content/uploads/$title" . "_Trophy_List.zip";

    // write the scheduler object back out to file
    $scheduler_data = serialize($scheduler);
    $fp = fopen($file_path, 'w+');
    if ($fp) {
      fwrite($fp, $scheduler_data);
      fclose($fp);
    }
    else {
      echo "Did not save correctly.";
    }
  }
  else {
    echo "Trophy list was not downloaded because scheduler file doesn't exist.";
  }
}

/**
 * Function for downloading a list of all students who will be playing
 * in command performance.
 */
function get_command_students() {
  // determine the file path of the associated competition
  $title = str_replace(' ', '_', $_POST['compName']);
  $file_path = dirname(__FILE__);
  $parsed_file_path = explode('/', $file_path);
  $file_path = "";
  $parsed_file_path_index = 0;
  while ($parsed_file_path[$parsed_file_path_index] != "plugins") {
    $file_path .= $parsed_file_path[$parsed_file_path_index] . "/";
    $parsed_file_path_index++;
  }
  $file_path .= "uploads/$title.txt";

  // read the serialized Scheduler object from file
  if (file_exists($file_path)) {
    $scheduler = file_get_contents($file_path);
    $scheduler = unserialize($scheduler);

    // update all of the scores in the scheduler
    $command_student_list_file = ABSPATH . "wp-content/uploads/$title" . "_Command_Performance_List.txt";
    $scheduler->get_command_students($command_student_list_file);

    // create a zip file
    $zipname = ABSPATH . "wp-content/uploads/$title" . "_Command_Performance_List.zip";
    $zip = new ZipArchive;
    if ($zip->open($zipname, file_exists ($zipname) ? ZipArchive::OVERWRITE : ZipArchive::CREATE)) {
      $zip->addFile($command_student_list_file, $title . "_Command_Performance_List.txt");
      $zip->close();
    }
    else {
      echo "Zip failed to open.";
    }

    // force download the zip file from the browser
    echo "wp-content/uploads/$title" . "_Command_Performance_List.zip";

    // write the scheduler object back out to file
    $scheduler_data = serialize($scheduler);
    $fp = fopen($file_path, 'w+');
    if ($fp) {
      fwrite($fp, $scheduler_data);
      fclose($fp);
    }
    else {
      echo "Did not save correctly.";
    }
  }
  else {
    echo "List of command students was not downloaded because scheduler file doesn't exist.";
  }
}

determine_function_call();
