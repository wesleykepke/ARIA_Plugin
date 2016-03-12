<?php

/**
 * The song object used for scheduling.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

/**
 * The song object used for scheduling.
 *
 * This class defines a song object, which will be used throughout the
 * scheduling process. This object will represent a single song that a
 * student will be performing in a music competition and therefore will
 * include all attributes required to uniquely identify a song.
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 * @author     KREW
 */
class Song {

  /**
   * The name of the song.
   *
   * @since 1.0.0
   * @access private
   * @var 	string 	$name 	The name of the song.
   */
  private $name;

  /**
   * The duration of the song (with respect to the student playing).
   *
   * @since 1.0.0
   * @access private
   * @var 	float 	$duration 	The duration of the song.
   */
  private $duration;

  /**
   * The constructor used to instantiate a new song object.
   *
   * @since 1.0.0
   * @param	string	$name 	The name of the song.
   * @param	string 	$duration 	The duration of the song.
   */
  function __construct($name, $duration) {
    $this->name = $name;
    $this->duration = $duration;
  }

  /**
   * The function used to retrieve the song name.
   *
   * @since 1.0.0
   */
  public function get_song_name() {
    return $this->name;
  }

  /**
   * The function used to retrieve the song duration.
   *
   * @since 1.0.0
   */
  public function get_song_duration() {
    return $this->duration;
  }

  /**
   * The destructor used when a song object is destroyed.
   *
   * @since 1.0.0
   */
  function __destruct() {
    unset($this->name);
    unset($this->duration);
  }
}
