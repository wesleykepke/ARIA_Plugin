<?php

require_once("class-aria-scheduler.php");

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

  ////echo print_r($_POST);
  ////echo "File path: $file_path <br>";

  // read the serialized Scheduler object from file
  if (file_exists($file_path)) {
    $scheduler = file_get_contents($file_path);
    $scheduler = unserialize($scheduler);

    // update all of the scores in the scheduler
    
    echo "here";
  }
  else {
    //echo "Documents have not been generated because file doesn't exist";
  }
}

update_scores();
