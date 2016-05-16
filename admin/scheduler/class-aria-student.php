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
   * @var 	string 	$last_name 	The last name of the student.
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
   * The composers of the songs that the student is performing.
   *
   * @since 1.0.0
   * @access private
   * @var   array   $composers  The composers of the songs that the student is performing.
   */
  private $composers;

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
   * The skill level of the student (0-11).
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$skill_level 	The skill level that the student identifies as.
   */
  private $skill_level;

  /**
   * The play time duration of the student.
   *
   * This will represent the total amount of time (in minutes) that the student
   * needs to play his/her songs in the competition.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$play_time 	The total amount of playing time.
   */
  private $play_time;

  /**
   * The email of the student's teacher.
   *
   * This will represent the email of the teacher that the student registered
   * under.
   *
   * @since 1.0.0
   * @access private
   * @var 	string 	$teacher_email 	The student's teacher's email address.
   */
  private $teacher_email;

  /**
   * The email of the student's parent.
   *
   * This will represent the email of the parent that the student registered
   * under.
   *
   * @since 1.0.0
   * @access private
   * @var 	string 	$parent_email 	The student's parent's email address.
   */
  private $parent_email;

  /**
   * The start time of the current time block.
   *
   * @since 1.0.0
   * @access private
   * @var   string   $start_time   The start time of the current time block.
   */
  private $start_time;

  /**
   * The day of the current time block.
   *
   * @since 1.0.0
   * @access private
   * @var   string   $day   The day that the current time block is on.
   */
  private $day;

  /**
   * The name of the student's teacher.
   *
   * @since 1.0.0
   * @access private
   * @var   string   $teacher_name   The student's teacher's name.
   */
  private $teacher_name;

  /**
   * The room where the student is performing.
   *
   * @since 1.0.0
   * @access private
   * @var   string   $room   The room name/number.
   */
  private $room;

  private $date;

  /**
   * The constructor used to instantiate a new student object.
   *
   * @since 1.0.0
   * @param	string	$first_name 	The first name of the student.
   * @param	string	$last_name 	The last name of the student.
   * @param	int $type 	The type of section the student registered as.
   * @param	int $day_preference 	The day that the student would like to compete.
   * @param	int $skill_level 	The skill level that the student identifies as.
   * @param int	$play_time	The total play time that the student requires.
   */
  function __construct($first_name, $last_name, $type, $day_preference,
                       $skill_level, $play_time, $teacher_email, $parent_email,
                       $teacher_name) {
    $this->first_name = $first_name;
    $this->last_name = $last_name;
    $this->type = $type;
    $this->songs = array();
    $this->composers = array();
    $this->day_preference = $day_preference;
    $this->skill_level = $skill_level;
    $this->play_time = $play_time;
    $this->teacher_email = $teacher_email;
    $this->parent_email = $parent_email;
    $this->start_time = null;
    $this->day = null;
    $this->room = null;
    $this->teacher_name = $teacher_name;
    $this->date = null;
  }

  /**
   * The function used to add a song to the student's list of songs.
   *
   * This function will simply append the song that is passed as a parameter
   * to the students list of songs that he/she is playing in the competition.
   *
   * @since 1.0.0
   * @param	string	$song_name 	The name of the song.
   */
  public function add_song($song_name) {
    $this->songs[] = $song_name;
  }

  public function add_date($date) {
    $this->date = $date;
  }

  /**
   * The function used to add a composer to the student's list of composers.
   *
   * This function will simply append the composer that is passed as a parameter
   * to the students list of composers.
   *
   * @since 1.0.0
   * @param string  $composer_name  The name of the composer.
   */
  public function add_composer($composer_name) {
    $this->composers[] = $composer_name;
  }

  /**
   * The function will set the start time of the when the student is registered
   * to perform.
   *
   * @since 1.0.0
   * @param string   $start_time   The start time of the current time block.
   */
  public function set_start_time($start_time) {
    $this->start_time = $start_time;
  }

  /**
   * The function will set the day of the when the student is registered
   * to perform.
   *
   * @since 1.0.0
   * @param string   $start_time   The start time of the current time block.
   */
  public function set_day($day) {
    $this->day = $day;
  }

  /**
   * The function will set the room of the where the student is registered
   * to perform.
   *
   * @since 1.0.0
   * @param string   $room  The room of where the student is playing.
   */
  public function set_room($room) {
    $this->room = $room;
  }

  /**
   * The function will return the type of competition that the student
   * registered for.
   *
   * @since 1.0.0
   * @return int Represents type of section that the student registered for.
   */
  public function get_type() {
    return $this->type;
  }

  /**
   * The function will return the name of the student.
   *
   * @since 1.0.0
   * @return string Represents name of student.
   */
  public function get_name() {
    return $this->first_name . ' ' . $this->last_name;
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
   * The function will return the skill level of the student.
   *
   * @since 1.0.0
   * @return integer Represents the student's skill level (0-11)
   */
  public function get_skill_level() {
    return $this->skill_level;
  }

  /**
   * The function will return an array of songs (strings).
   *
   * @since 1.0.0
   * @return	array 	The array of songs that the student is performing
   */
  public function get_songs() {
    return $this->songs;
  }

  /**
   * The function will return the total amount of time for the student to play
   * his/her songs.
   *
   * @since 1.0.0
   * @return integer Represents the student's total song time.
   */
  public function get_total_play_time() {
    return $this->play_time;
  }

  /**
   * The function will return the email address of the student's teacher.
   *
   * @since 1.0.0
   * @return string Represents the student's teacher's email address.
   */
  public function get_teacher_email() {
    return $this->teacher_email;
  }

  /**
   * The function will return the email address of the student's parent.
   *
   * @since 1.0.0
   * @return string Represents the student's parent's email address.
   */
  public function get_parent_email() {
    return $this->parent_email;
  }

  /**
   * This function will construct the competition info as a sentence on a per-student basis.
   *
   * Using the information that is stored for each student, this function will
   * construct and return a sentence that describes all of the stored information
   * about a student for an upcoming competition.
   *
   * @since 1.0.0
   * @return  string  Represents the student's competition information.
   */
  public function get_info_for_email() {
    // process the student's songs
    $songs = null;
    if (count($this->songs) > 2) {
      for ($i = 0; $i < count($this->songs); $i++) {
        if ($i == (count($this->songs) - 1)) {
          $songs .= 'and ' . $this->songs[$i] . ' by ' . $this->composers[$i];
        }
        else {
          $songs .= $this->songs[$i] . ' by ' . $this->composers[$i]. ', ';
        }
      }
    }
    else if (count($this->songs) == 2) {
      $songs = $this->songs[0] . ' by ' . $this->composers[0] . ' and '
      . $this->songs[1] . ' by ' . $this->composers[1];
    }

    // construct and return the sentence of student information
    $first_name = ucwords(strtolower($this->first_name));
    $last_name = ucwords(strtolower($this->last_name));
    $message = "$first_name $last_name will be playing $songs on $this->day ($this->date) at $this->start_time in room: $this->room.\n\n";
    return $message;
  }

  /**
   * The function will return an associative array that contains all of the
   * information associated with a student.
   *
   * The associative array can be iterated over in a print function and will
   * return a formatted output that displays all information that is stored
   * about a particular student.
   *
   * @since 1.0.0
   * @return array Represents all information stored about a student.
   */
  public function get_student_info() {
    // student type will be returned as a string
    $type = null;
    switch ($this->type) {
      case SECTION_OTHER:
        $type = "Traditional/Non-Competitive/Command";
      break;

      case SECTION_MASTER:
        $type = "Master";
      break;
    }

    // student day preference will be returned as a string
    $day_preference = null;
    switch ($this->day_preference) {
      case SAT:
        $day_preference = "Saturday";
      break;

      case SUN:
        $day_preference = "Sunday";
      break;

      case COMMAND:
        $day_preference = "Command";
      break;
    }

    return array(
      '<b>Student Name</b>' => $this->first_name . ' ' . $this->last_name,
      '<b>Student Type</b>' => $type,
      '<b>Student Day Preference</b>' => $day_preference,
      '<b>Student Songs</b>' => $this->songs,
      '<b>Student Skill Level</b>' => $this->skill_level,
      '<b>Student Playing Duration</b>' => $this->play_time
    );
  }

  public function get_schedule_string() {
    return array(
      'Student Name' => $this->first_name . ' ' . $this->last_name,
      'Student Skill Level' => $this->skill_level,
      'Student Play Time' => $this->play_time . ' minutes',
      'Song #1' => $this->songs[0],
      'Song #2' => $this->songs[1]
    );
  }

  /**
   * This function will consolidate all scheduling data (of students) into a format
   * suitable for the document generator.
   *
   * This function will prepare all data for a single student that is required of the
   * document generator.
   *
   * @return   Array   An associative array of all student data in doc. gen. compatible form.
   */
  public function get_section_info_for_doc_gen() {
    $str_type = null;
    if ($this->type == 0) {
      $str_type = "Masterclass";
    }
    else if ($this->type == 1) {
      $str_type = "Traditional";
    }
    else {
      $str_type = "Non-Competitive";
    }

    return array(
      'name' => ucwords(strtolower($this->first_name)) . ' ' . ucwords(strtolower($this->last_name)),
      'teacher' => $this->teacher_name,
      'level' => $this->skill_level,
      'format' => $str_type,
      'song_one' => array(
        'composer' => $this->composers[0],
        'song' => $this->songs[0]
      ),
      'song_two' => array(
        'composer' => $this->composers[1],
        'song' => $this->songs[1]
      )
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
    unset($this->skill_level);
    unset($this->play_time);
  }
}
