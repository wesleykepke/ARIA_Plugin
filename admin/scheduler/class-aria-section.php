<?php

/**
 * The section object used for scheduling.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ARIA_ROOT . "/admin/scheduler/class-aria-scheduler.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-section.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-student.php");

/**
 * The section object used for scheduling.
 *
 * This class defines a section object, which will be used throughout the
 * scheduling process. This object will represent one of the concurrent
 * sections that a student can compete in for a given competition. In a
 * nutshell, it will store all of the students that are competing in the
 * given section, the type of section that it is (traditional, master-class,
 * non-competitive, or command performance), the current time that it would
 * take to allow all students to play their pieces in a given section, and
 * finally, functionality with determining whether the given section is full
 * or can accomodate more students.
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 * @author     KREW
 */
class Section {

  /**
   * The time limit per section.
   */
  const SECTION_TIME_LIMIT = 35;

  /**
   * The type of section (either traditional, master-class, non-competitive, or
   * command performance).
   *
   * @since 1.0.0
   * @access private
   * @var 	string 	$type 	The type of section for the given section.
   */
  private $type;

  /**
   * The list of students that are scheduled to play in the current section.
   *
   * This will be an array of student objects.
   *
   * @since 1.0.0
   * @access private
   * @var 	array 	$students 	The list of students in the current section.
   */
  private $students;

  /**
   * The total time that it would take for all students currently registered
   * for the given section to play the musical pieces they have signed up for.
   *
   * Since the sections are 45 minutes and judges need time to score students
   * after they compete, this value should not exceed 35.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$current_time 	The current time for all songs to be played.
   */
  private $current_time;

  /**
   * The constructor used to instantiate a new section object.
   *
   * @since 1.0.0
   */
  function __construct() {
    $this->type = null;
    $this->students = array();
    $this->current_time = 0;
  }

  /**
   * The function used to determine if a section is full.
   *
   * This function will compare the current playing time with the section time
   * limit and determine if there is still enough time to add another student.
   *
   * @return true if section is full, false otherwise
   */
  public function is_full() {
    return ($this->current_time > self::SECTION_TIME_LIMIT);
  }

  /**
   * The function used to determine if a section is empty.
   *
   * This function will return true if the current section is empty and false
   * otherwise.
   *
   * @return true if section is empty, false otherwise
   */
  public function is_empty() {
    return (empty($this->students));
  }

  /**
   * The function used to determine the type of section.
   *
   * @return integer Represents type of section
   */
  public function get_type() {
    return $this->type;
  }

  /**
   * The function used to add a student to the current section.
   *
   * If the current section matches the type of student competing (traditional,
   * master-class, non-competitive, or command performance) and the current
   * section is not full, then the incoming student object passed as a
   * parameter will be added to the list of students competing in the current
   * section.
   *
   * @param Student 	$student 	The student that is being added to the section.
   *
   * @return true if student was added, false otherwise
   */
  public function add_student($student) {
    //echo('trying to add student in section object');    
 
    // Section type not yet determined (section is empty)
    if (is_null($this->type)) {
      $this->type = $student->get_type();
      //echo 'assigned type'; 
    }

   // Section is full or incoming student's type doesn't match section type
    if ($this->is_full() || ($this->type !== $student->get_type())) {
      //echo 'is full'; 
      return false;
    }

    // Add student to this section
    $this->students[] = $student;
    $this->current_time += $student->get_total_play_time();
    
    //wp_die(print_r($this->students)); 
  
    return true;
  }
 
  /**
   * This function will print all the students in given section. 
   */
  public function print_schedule() {
    for ($i = 0; $i < count($this->students); $i++) {
      $student_info = $this->students[$i]->get_student_info(); 
      for ($j = 0; $j < count($student_info); $j++) {
        if (is_array($student_info[$j])) {
          for ($k = 0; $k < count($student_info[$j]); $k++) {
            echo $student_info[$j][$k]->get_song_name() . "<br>";
            echo $student_info[$j][$k]->get_song_duration() . "<br>";
          }
        }
        else {
          echo $student_info[$j] . "<br>"; 
        }
      }
    }
  }

  /**
   * The destructor used when a section object is destroyed.
   *
   * @since 1.0.0
   */
  function __destruct() {
    unset($this->type);
    unset($this->students);
    unset($this->current_time);
  }
}
