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
   * The skill level of the students in this section.
   *
   * This will be an integer value in the range of 0-11.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$skill_level 	The skill level of students in this section.
   */
  private $skill_level;

  /**
   * The constructor used to instantiate a new section object.
   *
   * @since 1.0.0
   */
  function __construct() {
    $this->type = null;
    $this->students = array();
    $this->current_time = 0;
    $this->skill_level = null;
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
    return ($this->current_time > SECTION_TIME_LEN);
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
   * The function used to find the skill level of the current section.
   *
   * @return integer Represents the skill level of students in the section.
   */
  public function get_skill_level() {
    return $this->skill_level;
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
    // Section type not yet determined (section is empty)
    if (is_null($this->type)) {
      $this->type = $student->get_type();
    }

    // Section skill level not yet determined (section is empty)
    if (is_null($this->skill_level)) {
      $this->skill_level = $student->get_skill_level();
    }

    if ($this->is_full()) {
      return false;
    }

    // Check if adding student will cause time section to overflow
    if (($student->get_total_play_time() + $this->current_time) > SECTION_TIME_LEN + SECTION_TIME_BUFFER) {
      return false;
    }

    // Incoming student doesn't meet criteria of section
    if (($this->type !== $student->get_type()) || ($this->skill_level !== $student->get_skill_level())) {
      return false;
    }

    // Add student to this section
    $this->students[] = $student;
    $this->current_time += $student->get_total_play_time();
    return true;
  }

  /**
   * This function will print all the students in given section.
   */
  public function print_schedule() {
    for ($i = 0; $i < count($this->students); $i++) {
      $student_info = $this->students[$i]->get_student_info();
      foreach ($student_info as $key => $value) {
        echo $key . "<br>";
        if (is_array($value)) {
          for ($j = 0; $j < count($value); $j++) {
            echo $value[$j]->get_song_name() . "<br>";
            echo $value[$j]->get_song_duration() . "<br>";
          }
        }
        else {
          echo $value . "<br>";
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
