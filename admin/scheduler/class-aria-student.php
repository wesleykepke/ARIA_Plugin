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
   * The constructor used to instantiate a new student object.
   *
   * @since 1.0.0
   * @param	string	$first_name 	The first name of the student.
   * @param	string 	$last_name 	The last name of the student.
   */
  function __construct($first_name, $last_name) {
    $this->first_name = $first_name;
    $this->last_name = $last_name;
    $this->songs = array();
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
   * The destructor used when a student object is destroyed.
   *
   * @since 1.0.0
   */
  function __destruct() {
    unset($this->first_name);
    unset($this->last_name);
    unset($this->songs);
  }
}
