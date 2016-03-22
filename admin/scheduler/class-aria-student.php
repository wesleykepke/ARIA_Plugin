<?php

/**
 * The student object used for scheduling.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ARIA_ROOT . "/admin/scheduler/class-aria-song.php");

/**
 * The student object used for scheduling.
 *
 * This class defines a student object, which will be used throughout the
 * scheduling process. This object will represent a student competing in a
 * competition and will therefore contain all attributes that are necessary to
 * completely identify a student that is competing in a particular session of
 * a particular time block in a competition.
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 * @author     KREW
 */
class Student {

  /**
   * The first name of the student.
   *
   * @since 1.0.0
   * @access private
   * @var 	string 	$first_name 	The first name of the student.
   */
  private $first_name;

  /**
   * The last name of the student.
   *
   * @since 1.0.0
   * @access private
   * @var 	string 	$last_name 	The first name of the student.
   */
  private $last_name;

  /**
   * The songs that the student is performing.
   *
   * @since 1.0.0
   * @access private
   * @var 	array 	$songs 	The songs that the student is performing.
   */
  private $songs;

  /**
   * The type of section the student will be competing in (traditional,
   * master-class, non-competitive, or command performance).
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$type 	The type of section the student registered as.
   */
  private $type;

  /**
   * The day preference that the student requested.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$day_preference 	The day that the student would like to compete.
   */
  private $day_preference;

  /**
   * The skill level of thr student (0-11).
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$skill_level 	The skill level that the student identifies as.
   */
  private $skill_level;

  /**
   * The constructor used to instantiate a new student object.
   *
   * @since 1.0.0
   * @param	string	$first_name 	The first name of the student.
   * @param	string 	$last_name 	The last name of the student.
   * @param	int 	$type 	The type of section the student registered as.
   * @param	int 	$day_preference 	The day that the student would like to compete.
   * @param	int 	$skill_level 	The skill level that the student identifies as.
   */
  function __construct($first_name, $last_name, $type, $day_preference, $skill_level) {
    $this->first_name = $first_name;
    $this->last_name = $last_name;
    $this->type = $type;
    $this->songs = array();
    $this->day_preference = $day_preference;
    $this->skill_level = $skill_level;
  }

  /**
   * The function used to add a song to the student's list of songs.
   *
   * This function will create a new song object according to the paramaters
   * passed to this function and add this new song object to the list of songs
   * that the student will be performing during the competition.
   *
   * @since 1.0.0
   * @param	string	$song_name 	The name of the song.
   * @param	string 	$song_duration 	The duration of the song.
   */
  public function add_song($song_name, $song_duration = 0) {
    $song = new Song($song_name, $song_duration);
    $this->songs[] = $song;
  }

  /**
   * The function will return the type of competition that the student
   * registered for.
   *
   * @since 1.0.0
   * @return integer Represents type of section that the student registered for.
   */
  public function get_type() {
    return $this->type;
  }

  /**
   * The function will return the requested competition day for the student.
   *
   * @since 1.0.0
   * @return integer Represents the student's requested competition day.
   */
  public function get_day_preference() {
    if (strcmp($this->day_preference, "Saturday") == 0) {
      return SAT;
    }
    else if (strcmp($this->day_preference, "Sunday") == 0) {
      return SUN;
    }
    else {
      return COMMAND;
    }
  }

  /**
   * The function will skill level of the student.
   *
   * @since 1.0.0
   * @return integer Represents the student's skill level (0-11)
   */
  public function get_skill_level() {
    return $this->skill_level;
  }

  /**
   * The function will return the  total amount of time for the student to play
   * his/her songs.
   *
   * @since 1.0.0
   * @return integer Represents the student's total song time.
   */
  public function get_total_play_time() {
    $total_time = 0;
    for ($i = 0; $i < count($this->songs); $i++) {
      $total_time += $this->songs[$i]->get_song_duration();
    }
    return $total_time;
  }

  public function get_student_info() {
    $type = null;
    switch ($this->type) {
      case SECTION_COMMAND_PERFORMANCE:
        $type = "Command";
      break;

      case SECTION_TRADITIONAL:
        $type = "Traditional";
      break;

      case SECTION_NON_COMPETITIVE:
        $type = "Non-competitive";
      break;

      case SECTION_MASTER:
        $type = "Master";
      break;
    }

    return array(
      '<b>Student Name</b>' => $this->first_name . ' ' . $this->last_name,
      '<b>Student Type</b>' => $type,
      '<b>Student Day Preference</b>' => $this->day_preference,
      '<b>Student Songs</b>' => $this->songs,
      '<b>Student Skill Level</b>' => $this->skill_level
    );
  }

  /**
   * The destructor used when a student object is destroyed.
   *
   * @since 1.0.0
   */
  function __destruct() {
    unset($this->first_name);
    unset($this->last_name);
    unset($this->type);
    unset($this->songs);
  }
}
