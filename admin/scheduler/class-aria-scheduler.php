<?php

/**
 * The scheduler object used for scheduling.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ARIA_ROOT . "/includes/aria-constants.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-time-block.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-student.php");

/**
 * The scheduler object used for scheduling.
 *
 * This class defines a scheduler object, which is the main object that is used
 * throughout the scheduling process. This object is responsible for taking a
 * student object as input and scheduling the student.
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 * @author     KREW
 */
class Scheduler {

  /**
   * The days of the music competition.
   *
   * The precise amount of days for a music competition will depend on whether
   * or not the scheduler is for a regular competition or for a command
   * performance.
   *
   * @since 1.0.0
   * @access private
   * @var	array $days	The days of the music competition.
   */
  private $days;

  /**
   * The type of the music competition (either a regular competition or
   * command performance).
   *
   * @since 1.0.0
   * @access private
   * @var	int $competition_type	The type of the competition.
   */
  private $competition_type;

  /**
   * The constructor used to instantiate a new scheduler object.
   *
   * Using the parameters passed to the constructor, a new scheduler object will
   * be created.
   *
   * @param	int	$competition_type	The type of the competition.
   *
   * @since 1.0.0
   * @author KREW
   */
  function __construct($competition_type) {
    // create the base structure depending on the type of competition
    switch($competition_type) {
      case REGULAR_COMP:
        $this->competition_type = $competition_type;
        $this->days = new SplFixedArray(REGULAR_COMP_NUM_DAYS);
      break;

      case COMMAND_COMP:
        $this->competition_type = $competition_type;
        $this->days = new SplFixedArray(COMMAND_COMP_NUM_DAYS);
      break;

      default:
        $this->competition_type = null;
      break;
    }
  }

  /**
   * This function will create the structure for a normal competition.
   *
   * Using the parameters passed to this function, the current scheduler object
   * will be created using the structure of a regular competition.
   *
   * @param	int	$num_time_blocks_sat	The number of time blocks on saturday.
   * @param	int	$num_time_blocks_sun	The number of time blocks on sunday.
   * @param Array   $both_start_times   The array of start times for both days. 
   * @param	int	$time_block_duration	The amount of time allocated to each timeblock.
   * @param	int	$num_concurrent_sections_sat	The number of sections/timeblock on saturday.
   * @param	int	$num_concurrent_sections_sun	The number of sections/timeblock on sunday.
   * @param	int	$num_master_sections_sat	The number of master-class sections on saturday.
   * @param	int	$num_master_sections_sun	The number of master-class sections on sunday.
   * @param int 	$song_threshold 	The amount of times a song can be played in this section.
   * @param boolean $group_by_level 	True if single level only, false otherwise.
   * @param	int 	$master_class_instructor_duration 	The time that each judge has to spend with students.
   * @param array   $saturday_rooms  The array of room assignments for saturday.
   * @param array   $sunday_rooms  The array of room assignments for sunday.
   *
   * @since 1.0.0
   * @author KREW
   */
  public function create_normal_competition($num_time_blocks_sat,
                                            $num_time_blocks_sun,
                                            $time_block_duration,
                                            $both_start_times,
                                            $num_concurrent_sections_sat,
                                            $num_concurrent_sections_sun,
                                            $num_master_sections_sat,
                                            $num_master_sections_sun,
                                            $song_threshold,
                                            $group_by_level,
                                            $master_class_instructor_duration,
                                            $saturday_rooms,
                                            $sunday_rooms) {
    // ensure the current scheduler object is for a regular competition
    if ($this->competition_type !== REGULAR_COMP) {
      return;
    }

    /*
    echo 'in create_normal_competition()' . "<br>";
    echo 'time_block_duration: ' . $time_block_duration . "<br>";
    echo 'num_time_blocks_sat: ' . $num_time_blocks_sat . "<br>";
    echo 'num_time_blocks_sun: ' . $num_time_blocks_sun . "<br>";
    echo 'num_concurrent_sections_sat: ' . $num_concurrent_sections_sat . "<br>";
    echo 'num_concurrent_sections_sun: ' . $num_concurrent_sections_sun . "<br>";
    echo 'num_master_sections_sat: ' . $num_master_sections_sat . "<br>";
    echo 'num_master_sections_sun: ' . $num_master_sections_sun . "<br>";
    echo 'song_threshold: ' . $song_threshold . "<br>";
    echo 'group_by_level: ' . $group_by_level . "<br>";
    echo 'master_class_instructor_duration: ' . $master_class_instructor_duration . "<br>";
    wp_die();
    //*/

    // preprocess the rooms for saturday
    for ($i = 0; $i < $num_time_blocks_sat; $i++) {
      if ($saturday_rooms != false && array_key_exists($i, $saturday_rooms)) {
        $saturday_rooms[$i] = 'Room: ' . $saturday_rooms[$i];
      }
      else {
        $saturday_rooms[$i] = 'Room: ' . strval($i + 1);
      }   
    }

    // preprocess the rooms for sunday 
    for ($i = 0; $i < $num_time_blocks_sun; $i++) {
      if ($sunday_rooms != false && array_key_exists($i, $sunday_rooms)) {
        $sunday_rooms[$i] = 'Room: ' . $sunday_rooms[$i];
      }
      else {
        $sunday_rooms[$i] = 'Room: ' . strval($i + 1);
      }   
    }

    $start_time_index = 0; 

    // create the time blocks with their concurrent sections for saturday
    $this->days[SAT] = new SplFixedArray($num_time_blocks_sat);
    for ($i = 0; $i < $num_time_blocks_sat; $i++) {
      $this->days[SAT][$i] = new TimeBlock($num_concurrent_sections_sat, $time_block_duration,
                                           $song_threshold, $group_by_level, 
                                           $both_start_times[$start_time_index], 
                                           'Saturday', $saturday_rooms);
    }

    // designate some of the sections on saturday for master-class students
    //echo 'num_master_sections_sat: ' . $num_master_sections_sat . "<br>";
    while ($num_master_sections_sat > 0) {
      for ($i = ($num_time_blocks_sat - 1); $i >= ($num_time_blocks_sat / 2); $i--) {
        if ($num_master_sections_sat > 0 && $this->days[SAT][$i]->assign_section_to_master($master_class_instructor_duration)) {
          $num_master_sections_sat--;
        }
      }
    }

    // create the time blocks with their concurrent sections for sunday
    $this->days[SUN] = new SplFixedArray($num_time_blocks_sun);
    for ($i = 0; $i < $num_time_blocks_sun; $i++) {
      $this->days[SUN][$i] = new TimeBlock($num_concurrent_sections_sun, $time_block_duration,
                                           $song_threshold, $group_by_level, 
                                           $both_start_times[$start_time_index],
                                           'Sunday', $sunday_rooms);
    }

    // designate some of the sections on sunday for master-class students
    while ($num_master_sections_sun > 0) {
      for ($i = ($num_time_blocks_sun - 1); $i >= ($num_time_blocks_sun / 2); $i--) {
        if ($num_master_sections_sun > 0 && $this->days[SUN][$i]->assign_section_to_master($master_class_instructor_duration)) {
          $num_master_sections_sun--;
        }
      }
    }
  }

  /**
   * This function wil create the structure for the command performance.
   *
   * Using the parameters passed to this function, a new scheduler object will
   * be created for a command performance.
   *
   * @param	int	$num_time_blocks	The number of time blocks for command performance.
   * @param	int	$time_block_duration	The amount of time allocated to each timeblock.
   *
   * @since 1.0.0
   * @author KREW
   */
  public function create_command_performance($num_time_blocks, $time_block_duration) {
    // ensure the current scheduler object is for a regular competition
    if ($this->competition_type !== COMMAND_COMP) {
      return;
    }

    // create the time blocks with their concurrent sections (one) for command performance
    $this->days[COMMAND] = new SplFixedArray($num_time_blocks);
    for ($i = 0; $i < $num_time_blocks; $i++) {
      $this->days[COMMAND][$i] = new TimeBlock(1, $time_block_duration);
    }
  }

  /**
   * The function will schedule a student.
   *
   * This function will schedule a student depending on which day they had
   * requested when they registered for a competition.
   *
   * @since 1.0.0
   * @param	Student	$student	The student that needs to be scheduled.
   */
  public function schedule_student($student) {
    $scheduled = false;
    $current_time_block = 0;

    // get the student's day preference
    $day_preference = $student->get_day_preference();
    $preferred_day_num_time_blocks = 0;
    switch ($day_preference) {
      case SAT:
        $preferred_day_num_time_blocks = $this->days[SAT]->getSize();
      break;

      case SUN:
        $preferred_day_num_time_blocks = $this->days[SUN]->getSize();
      break;

      case COMMAND:
        $preferred_day_num_time_blocks = $this->days[COMMAND]->getSize();
      break;
    }

    // continue to try and schedule student until he/she is successfully registered
    while (!$scheduled && $current_time_block < $preferred_day_num_time_blocks) {
      if ($this->days[$day_preference][$current_time_block]->schedule_student($student)) {
        $scheduled = true;
      }
      $current_time_block++;
    }

    // Student was unable to be scheduled for their requested date
    if ($current_time_block > $preferred_day_num_time_blocks && !$scheduled) {
      // might want to try adding them on another competition day?
      wp_die('Errored to line 209 -- student did not get scheduled in their day preference.');
      return false;
    }

    return true;
  }

  /**
   * This function will print the schedule in a human-readable format.
   */
  public function print_schedule() {
    echo "<br>";
    for ($i = 0; $i < count($this->days); $i++) {
      switch ($i) {
        case SAT:
          echo 'SATURDAY' . "<br>";
        break;

        case SUN:
          echo 'SUNDAY' . "<br>";
        break;
      }

      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        echo 'Time Block # ' . $j . "<br>";
        $this->days[$i][$j]->print_schedule();
      }

      echo "<br>";
    }

    echo "<br>";
    //wp_die('schedule complete');
  }

  /**
   * This function will create the schedule for the competition using HTML.
   *
   * Since the schedule is best demonstrated using HTML tables and lists, this
   * function is responsible for creating the basic HTML structure. The creation
   * of the inner HTML will be abstracted away to the timeblocks and sections.
   * 
   * @return	string	The generated HTML output
   */
  public function get_schedule_string() {
    $schedule = '';
    for ($i = 0; $i < count($this->days); $i++) {
      switch ($i) {
        case SAT:
          $schedule .= '<table style="float: left; width: 50%;">';
          $schedule .= '<tr><th>Saturday</th></tr>';
          for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
            $schedule .= '<tr><td>';
            $schedule .= '<tr><th>';
            $schedule .= 'Timeblock # ' . strval($j + 1);
            $schedule .= $this->days[$i][$j]->get_schedule_string();
            $schedule .= '</th></tr>';
            $schedule .= '</td></tr>';
          }
        break;

        case SUN:
          $schedule .= '<tr><table style="float: right; width: 50%;">';
          $schedule .= '<tr><th>Sunday</th></tr>';
          for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
            $schedule .= '<tr><td>';
            $schedule .= '<tr><th>';
            $schedule .= 'Timeblock # ' . strval($j + 1);
            $schedule .= $this->days[$i][$j]->get_schedule_string();
            $schedule .= '</th></tr>';
            $schedule .= '</td></tr>';
          }
        break;
      }

      $schedule .= '</table>';
    }

    return $schedule;
  }

  /**
   * This function will find all of the students participating in a competition
   * and group them by teacher email.
   *
   * This function will accept a teacher's email as a parameter. Using this value,
   * the scheduler will then iterate through all of it's timeblocks and find all 
   * of the students scheduled in the competition that had registered under the 
   * teacher's email that was passed as a parameter.
   *
   * @param 	String	$teacher_email	The email of the teacher to group students by.
   * @param	Array	$students	The array of students that registered under the teacher. 
   */
  public function group_all_students_by_teacher_email($teacher_email, &$students) {
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $this->days[$i][$j]->group_all_students_by_teacher_email($teacher_email, $students);
      }
    }
  }

  /**
   * This function will consolidate all scheduling data into a format suitable for
   * the document generator. 
   *
   * This function will iterate through all timeblock objects of all days of the 
   * competition. For each timeblock, the associated sections will be parsed
   * and the data will come back returned in a format that is compatible with that
   * required by the document generator.
   *
   * @return  An associative array of all student data in doc. gen. compatible form. 
   */
  public function get_section_info_for_doc_gen() {
    $doc_gen_section_data = array();
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $this->days[$i][$j]->get_section_info_for_doc_gen($doc_gen_section_data); 
      }
    }
    return $doc_gen_section_data; 
  }

  /**
   * The destructor used when a scheduler object is destroyed.
   *
   * @since 1.0.0
   */
  function __destruct() {
    unset($this->days);
    unset($this->num_days);
    unset($this->num_time_blocks_per_day);
  }
}
