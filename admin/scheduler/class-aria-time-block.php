<?php

/**
 * The time block object used for scheduling.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ARIA_ROOT . "/includes/class-aria-api.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-section.php");

/**
 * The time block object used for scheduling.
 *
 * This class defines a time block object, which will be used throughout the
 * scheduling process. This object will represent a given time block in a
 * competition, which can be considered to be a block of time (9:00 - 9:45)
 * that students will be scheduled in. For each time block, there will be a
 * an arbitrary number of concurrent sections that will be occuring
 * simultaneously (the number of concurrent sections is determined by the
 * festival chairman).
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 * @author     KREW
 */
class TimeBlock {

  /**
   * The number of concurrent sections per time block (determined by the
   * festival chairman).
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$num_concurrent_sections 	The number of concurrent sections.
   */
  private $num_concurrent_sections;

  /**
   * The array of section objects per time block.
   *
   * @since 1.0.0
   * @access private
   * @var 	array 	$sections 	The concurrent section objects.
   */
  private $sections;

  /**
   * The constructor used to instantiate a new time block object.
   *
   * @since 1.0.0
   * @param	int 	$num_concurrent_sections 	The number of concurrent sections.
   */
  function __construct($num_concurrent_sections) {
    $this->num_concurrent_sections = $num_concurrent_sections;
    $this->sections = new SplFixedArray($this->num_concurrent_sections);
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i] = new Section();
    }

    /*
    echo "Just created new TimeBlock object..";
    wp_die(print_r($this->sections));
    */
  }

  /**
   * The function used to check if a given time block is full.
   *
   * This function will iterate over all of the section objects in the current
   * time block and will return true if all of the sections are full.
   *
   * @since 1.0.0
   */
  public function is_full() {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      if ( !($this->sections[$i]->is_full()) ) {
        return false;
      }
    }

    return true;
  }

  /**
   * The function will attempt to schedule a student in the current time block.
   *
   * This function will iterate over all of the section objects in the current
   * time block and attempt to add the incoming student to one of the sections.
   * This function will return true if the given student object was added to
   * a section in the current time block and false otherwise.
   *
   * @since 1.0.0
   * @param	Student	$student	The student that needs to be scheduled.
   */
  public function schedule_student($student) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {

      if ($this->student_can_be_added($student, $this->sections[$i])) {
        if ($this->sections[$i]->add_student($student)) {
          return true;
        } 
      }

      //echo 'scheduling in time block: ' . $this->num_concurrent_sections; 
      //wp_die('checking out of range index'); 

    }


    //wp_die('could not schedule in time block'); 
    return false;
  }

  /**
   * This function is used to check if a student can be added to a section.
   *
   * This function is solely meant to simplify the condition that checks to see
   * if a student can be added to a section in the function schedule_student.
   *
   * @since 1.0.0
   * @param	Student	$student	The student that needs to be scheduled.
   * @param	Section	$section	The section that the student is trying to be added to.
   */
  private function student_can_be_added($student, $section) {
    if ( !($section->is_full()) ) {
      if ($section->is_empty() || $section->get_type() === $student->get_type()) {
        return true;
        //echo 'student can be added!';
        //wp_die(); 
      }
    }

    //wp_die('student cannot be added'); 

    return false;
  }

  /**
   * This function will print the sections in a given time block object. 
   */
  public function print_schedule() {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      echo 'Section # ' . $i . '<br>'; 
      $this->sections[$i]->print_schedule();
      echo '<br>';  
    }
  } 

  /**
   * The destructor used when a time block object is destroyed.
   *
   * @since 1.0.0
   */
  function __destruct() {
    unset($this->num_concurrent_sections);
    unset($this->sections);
  }
}
