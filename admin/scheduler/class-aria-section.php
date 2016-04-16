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
   * time for a section will be allocated to judging. This value can be found
   * in the ARIA constants file and is called PLAY_TIME_FACTOR.
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
   * The song threshold for this section.
   *
   * This will be an integer value that indicates how many times a song can be
   * played in a particular section.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$song_threshold 	The amount of times a song can be played in this section.
   */
  private $song_threshold;

  /**
   * The indicator for seperating based on student level.
   *
   * This will be an boolean value. If set to true, then sections will only
   * contain a single level of students. However, if set to false, then sections
   * will not be limited to a single level of students.
   *
   * @since 1.0.0
   * @access private
   * @var 	boolean 	$group_by_level 	True if single level only, false otherwise
   */
  private $group_by_level;

  /**
   * The time that the master class instructor has to spend with each student.
   *
   * This is an integer value (minutes). After each masterclass student performs,
   * he/she will be instructed by the masterclass instructor for $master_class_instructor_duration
   * minutes.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$master_class_instructor_duration 	Masterclass section length
   */
  private $master_class_instructor_duration;

  /**
   * The start time of the current time block. 
   *
   * @since 1.0.0
   * @access private
   * @var   string   $start_time   The start time of the current time block.
   */
  private $start_time;

  /**
   * The day of the current time block (and section). 
   *
   * @since 1.0.0
   * @access private
   * @var   string   $day   The day that the current time block is on.
   */
  private $day;

  /**
   * The name/number of the section's room. 
   *
   * @since 1.0.0
   * @access private
   * @var   string   $room   The name or number of the room. 
   */
  private $room;

  /**
   * The constructor used to instantiate a new section object. 
   *
   * @since 1.0.0
   * @param int   $section_time_limit  The length of the concurrent sections.
   * @param int   $song_threshold   The amount of times a song can be played in this section.
   * @param boolean   $group_by_level   True if single level only, false otherwise
   * @param string   $start_time   The start time of the current time block.
   * @param string  $day  The day of the current time block.
   * @param string  $room   The name/number of the room for the current section.  
   */
  function __construct($section_time_limit = DEFAULT_SECTION_TIME,
                       $song_threshold = NO_SONG_THRESHOLD,
                       $group_by_level = false,
                       $start_time, $day, $room) {
    $this->type = null;
    $this->students = array();
    $this->section_time_limit = $section_time_limit;
    $this->current_time = 0;
    $this->music_time_limit = ceil($section_time_limit * PLAY_TIME_FACTOR);
    $this->skill_level = null;
    if ($song_threshold == 0) {
      $this->song_threshold = NO_SONG_THRESHOLD;
    }
    else {
      $this->song_threshold = $song_threshold;
    }
    $this->group_by_level = $group_by_level;
    $this->master_class_instructor_duration = null;
    $this->start_time = $start_time;
    $this->day = $day;
    $this->room = $room; 
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
   * This function will accept $master_class_instructor_duration as a parameter
   * and assign it to the object's corresponding member variable so that when
   * masterclass students are added, $master_class_instructor_duration can be
   * used in addition to the student's playing time to see if they can be
   * assigned to a section or not.
   *
   * @param	int 	$master_class_instructor_duration 	The time that each judge has to spend with students.
   *
   * @return true if the section was assigned as a masterclass section, false otherwise
   */
  public function assign_section_to_master($master_class_instructor_duration) {
    $assigned_to_master = false;
    if (self::is_empty()) {
      $this->type = SECTION_MASTER;
      $this->master_class_instructor_duration = $master_class_instructor_duration;
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
    // check if adding student will cause time limit per section to overflow
    if (($student->get_total_play_time() + $this->current_time) > $this->music_time_limit) {
      return false;
    }

    // if the student is competing in the masterclass division, take into account instructor time
    if ($student->get_type() === SECTION_MASTER && $this->type === SECTION_MASTER) {
      if (($student->get_total_play_time() + $this->current_time + $this->master_class_instructor_duration) > $this->music_time_limit) {
        return false;
      }
    }

    // check if the song threshold would be broken by adding the new student
    $songs = $student->get_songs();
    foreach ($songs as $song) {
      $play_count = $this->get_num_times_song_played($song);
      if ($play_count >= $this->song_threshold) {
        return false;
      }
    }

    // check if the section is empty
    if ($this->is_empty()) {
      $this->type = $student->get_type();
      $this->skill_level = $student->get_skill_level();
    }

    // check if the incoming student is not of the same type of the section
    if ($this->type !== $student->get_type()) {
      return false;
    }

    // if each section can only have students of a single level, make sure
    // the incoming student matches with the section level
    if ($this->group_by_level) {
      if ($this->skill_level !== $student->get_skill_level()) {
        return false;
      }
    }

    // add student to this section
    $student->set_start_time($this->start_time);
    $student->set_day($this->day); 
    $student->set_room($this->room);
    $this->students[] = $student;
    if ($this->type === SECTION_MASTER) {
      // for masterclass sections, add the instructor duration length in addition to play time
      $this->current_time += $student->get_total_play_time() + $this->master_class_instructor_duration;
    }
    else {
      $this->current_time += $student->get_total_play_time();
    }
    return true;
  }

  /**
   * The function used check how many times a particular song is being played in a section.
   *
   * When the festival chairman specifies the amount of times a song can be
   * played in a given section, the scheduler needs to be able to check how many
   * occurances of that song are being played in a given section. This function
   * will assist in that check.
   *
   * @param string	$song_title 	The song to check
   *
   * @return	int 	The amount of times the given song is being played.
   */
  private function get_num_times_song_played($song_title) {
    $song_count = 0;
    foreach ($this->students as $student) {
      $songs = $student->get_songs();
      foreach ($songs as $song) {
        if (strcmp($song, $song_title) === 0) {
          $song_count++;
        }
      }
    }

    return $song_count;
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
      echo 'Total play time for section #' . $i . ': ' . $this->current_time . " minutes.<br>";
      foreach ($student_info as $key => $value) {
        echo $key . "<br>";
        if (is_array($value)) {
          for ($j = 0; $j < count($value); $j++) {
            echo $value[$j] . "<br>";
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
   *
   */
  public function get_schedule_string() {
    $schedule = '';
    for ($i = 0; $i < count($this->students); $i++) {
      $student_info = $this->students[$i]->get_schedule_string();
      $schedule .= '<tr><td>Student #';
      $schedule .= strval($i + 1);
      $schedule .= '<ul>';
      foreach ($student_info as $key => $value) {
        if (is_array($value)) {
          for ($j = 0; $j < count($value); $j++) {
            $schedule .= '<li>';
            $schedule .= $value[$j];
            $schedule .= '</li>';   
          }
        }
        else {
          $schedule .= '<li>';
          $schedule .= $key . ': ' . $value;
          $schedule .= '</li>';  
        }
      } 
      $schedule .= '</ul></td></tr>';
    }

    return $schedule; 
  }

  /**
   *
   */
  public function get_section_info() {
    // first, determine if there is section info
    if (self::is_empty()) {
      return 'Section is Empty';
    }

    // get start time of the section
    $section_info = 'Start Time: ' . $this->start_time . ', ';

    // determine number of students per section
    $section_info .= 'Number of Students: ' . strval(count($this->students)) . ', ';
    

    // get all skill levels in section
    $skill_levels = array();
    foreach ($this->students as $student) {
      if (!in_array($student->get_skill_level(), $skill_levels)) {
        $skill_levels[] = $student->get_skill_level(); 
      }
    }

    if (count($skill_levels) === 1) {
      $section_info .= 'Student Skill Level: ' . strval($skill_levels[0]) . ', ';
    }
    else {
      $section_info .= 'Student Skill Levels: ';
      for ($i = 0; $i < count($skill_levels); $i++) {
        $section_info .= strval($skill_levels[$i]) . ', ';
      }
    }

    // determine the type of the section
    $section_info .= 'Section Type: ';
    switch ($this->type) {
      case SECTION_OTHER:
        $section_info .= "Traditional/Non-Competitive/Command, ";
      break;

      case SECTION_MASTER:
        $section_info .= "Masterclass, ";
      break;
    }

    // include the total play time
    $section_info .= 'Total Play Time: ' . $this->current_time . ' minutes';
    return $section_info;
  }

  /**
   * This function will find all of the students participating in a section
   * and group them by teacher email.
   *
   * This function will accept a teacher's email as a parameter. Using this value,
   * the section will then iterate through all of it's students and find all 
   * of the students scheduled in the competition that had registered under the 
   * teacher's email that was passed as a parameter.
   *
   * @param 	String	$teacher_email	The email of the teacher to group students by.
   * @param	Array	$students	The array of students that registered under the teacher. 
   */
  public function group_all_students_by_teacher_email($teacher_email, &$students) {
    for ($i = 0; $i < count($this->students); $i++) {
      if ($this->students[$i]->get_teacher_email() == $teacher_email) {
        $students[] = $this->students[$i];  
      } 
    }
  }

  /**
   * This function will consolidate all scheduling data into a format suitable for
   * the document generator. 
   *
   * This function will iterate through all student objects of a given section
   * object. For each section, all student data will be added in a format that is 
   * compatible with that required by the document generator.
   *
   * @param   Array   $doc_gen_section_daya An associative array of all student data in doc. gen. compatible form.
   */
  public function get_section_info_for_doc_gen(&$doc_gen_section_data) {
    // not sure about this if statement
    if (self::is_empty()) {
      return; 
    }

    // for each student registered in the section, get their data 
    $doc_gen_single_section_data = array();
    $doc_gen_single_section_data['section_name'] = $this->room;
    $doc_gen_single_section_data['judge'] = "Don't have this data..";
    $doc_gen_single_section_data['proctor'] = "Don't have this data..";
    $doc_gen_single_section_data['monitor'] = "Don't have this data..";
    $doc_gen_single_section_data['students'] = array(); 
    for ($i = 0; $i < count($this->students); $i++) {
      $doc_gen_single_section_data['students'][] = $this->students[$i]->get_section_info_for_doc_gen();
    }
    $doc_gen_section_data[] = $doc_gen_single_section_data; 
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
