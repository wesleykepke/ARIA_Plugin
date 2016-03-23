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
 * finally, functionality for determining whether the given section is full
 * or can accomodate more students.
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 * @author     KREW
 */
class Section {

  /**
   * The type of section (either traditional, master-class, non-competitive, or
   * command performance).
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$type 	The type of the current section.
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
   * The time limit for each section.
   *
   * This represents how long each section will last. Typically, these sections
   * are 45 minutes in length, but the festival chairman has the ability to
   * assign sections to a different time length if he/she would like to.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$section_time_limit 	The total duration of each section.
   */
  private $section_time_limit;

  /**
   * The total time that it would take for all students currently registered
   * for the given section to play the musical pieces they have signed up for.
   *
   * Since judges need time to score students in a section, this value should
   * not exceed ($section_time_limit * 0.8). In other words, 20% of the given
   * time for a section will be allocated to judging. This value will be given
   * it's own class variable (see $music_time_limit below).
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$current_time 	The current time for all songs to be played.
   */
  private $current_time;

  /**
   * The music time limit for each section.
   *
   * This represents how much time in a given section can be allocated to the
   * students performing music. As mentioned in the documentation for
   * $current_time, this value will be 80% of the $section_time_limit, which is
   * the total amount of time that is allocated for a given section.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$music_time_limit 	The total play duration of each section.
   */
  private $music_time_limit;

  /**
   * The skill level of the students in this section.
   *
   * This will be an integer value in the range of 0-11. Typically, each section
   * will only consist of students from a single level.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$skill_level 	The skill level of students in this section.
   */
  private $skill_level;

  /**
   * The constructor used to instantiate a new section object. The default
   * play time for a section will be 45 minutes unless otherwise specified.
   *
   * @since 1.0.0
   */
  function __construct($section_time_limit = 45) {
    $this->type = null;
    $this->students = array();
    $this->section_time_limit = $section_time_limit;
    $this->current_time = 0;
    $this->music_time_limit = ceil($section_time_limit * 0.8);
    $this->skill_level = null;
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
    return (empty($this->students) && ($this->current_time === 0));
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
   * The function used to predefine the type of a current section as master.
   *
   * @return void
   */
  public function assign_section_to_master() {
    $assigned_to_master = false;
    if (is_null($this->type)) {
      $this->type = SECTION_MASTER;
      $assigned_to_master = true;
    }

    return $assigned_to_master;
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
    // check if adding student will cause time section to overflow
    if (($student->get_total_play_time() + $this->current_time) > $this->music_time_limit) {
      return false;
    }

    // check if the incoming student doesn't meet criteria of section
    /*
    come back to this.. may relax the restriction of one skill level per section
    */
    if (($this->type !== $student->get_type()) || ($this->skill_level !== $student->get_skill_level())) {
      return false;
    }

    // check if the section is empty
    if ($this->is_empty()) {
      $this->type = $student->get_type();
      $this->skill_level = $student->get_skill_level();
    }

    // add student to this section
    $this->students[] = $student;
    $this->current_time += $student->get_total_play_time();
    return true;
  }

  /**
   * This function will print all the students in given section.
   *
   * This function will iterate through all of the students that are registered
   * for the given section, obtain their information, and print out all of this
   * information.
   *
   * @return void
   */
  public function print_schedule() {
    for ($i = 0; $i < count($this->students); $i++) {
      $student_info = $this->students[$i]->get_student_info();
      foreach ($student_info as $key => $value) {
        echo $key . "<br>";
        if (is_array($value)) {
          for ($j = 0; $j < count($value); $j++) {
            echo $value[$j]->get_song_name() . "<br>";
          }
        }
        else {
          echo $value . "<br>";
        }
      }
      unset($student_info);
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
    unset($this->section_time_limit);
    unset($this->current_time);
    unset($this->music_time_limit);
    unset($this->skill_level);
  }
}
