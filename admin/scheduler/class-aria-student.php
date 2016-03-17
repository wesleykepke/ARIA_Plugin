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
   * The constructor used to instantiate a new student object.
   *
   * @since 1.0.0
   * @param	string	$first_name 	The first name of the student.
   * @param	string 	$last_name 	The last name of the student.
   * @param	int 	$type 	The type of section the student registered as.
   */
  function __construct($first_name, $last_name, $type, $day_preference) {
    $this->first_name = $first_name;
    $this->last_name = $last_name;
    $this->type = $type;
    $this->songs = array();
    $this->day_preference = $day_preference;
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
  public function add_song($song_name, $song_duration) {
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
    return $this->day_preference;
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
