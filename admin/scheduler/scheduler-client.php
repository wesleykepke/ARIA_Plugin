<?php

require_once("../../includes/aria-constants.php");
require_once("class-aria-scheduler.php");

function parse_scheduler_html() {
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

  //echo print_r($_POST);
  //echo print_r($_POST['modifiableData']);
  //echo count($_POST['modifiableData']);

  // read the serialized Scheduler object from file
  if (file_exists($file_path)) {
    $scheduler = file_get_contents($file_path);
    $scheduler = unserialize($scheduler);

    // iterate through the scheduler object and update the information with the new data
    $scheduler->update_section_data($_POST['modifiableData']);

    // iterate through the scheduler object and update the student sections
    $scheduler->update_section_students($_POST['studentData']);

    // get the new HTML to display to the user
    $new_html = $scheduler->get_schedule_string(true);

    // write the scheduler object back out to file
    $scheduler_data = serialize($scheduler);
    $fp = fopen($file_path, 'w+');
    if ($fp) {
      fwrite($fp, $scheduler_data);
      fclose($fp);
    }

    echo $new_html;
  }
}

parse_scheduler_html();
