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
   * The days of the music competition. The precise amount of days for a
   * music competition will be specified by the festival chairman.
   *
   * @since 1.0.0
   * @access private
   * @var 	array 	$days 	The days of the music competition.
   */
  private $days;

  /**
   * The number of days for a particular music competition.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$num_days 	The number of days of the music competition.
   */
  private $num_days;

  /**
   * The number of sections per day of a particular music competition.
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$num_sections_per_day 	The number of sections per day of the music competition.
   */
  private $num_time_blocks_per_day;

  /**
   * The constructor used to instantiate a new scheduler object.
   *
   * @since 1.0.0
   * @param	int 	$num_days 	The number of days for the music competition.
   * @param	int 	$num_time_blocks_per_day 	The number time blocks per day of the music competition.
   * @param	int 	$num_concurrent_sections 	The number of sections within the time blocks of the music competition.
   */
  function __construct($num_days, $num_time_blocks_per_day, $num_concurrent_sections) {
    $this->num_days = $num_days;
    $this->num_time_blocks_per_day = $num_time_blocks_per_day;
    $this->days = new SplFixedArray($this->num_days);
    for ($i = 0; $i < $this->num_days; $i++) {
      $this->days[$i] = new SplFixedArray($this->num_time_blocks_per_day);
      for ($j = 0; $j < $this->num_time_blocks_per_day; $j++) {
        $this->days[$i][$j] = new TimeBlock($num_concurrent_sections);
      }
    }

    /*
    echo "Just created a scheduler object.";
    wp_die(print_r($this->days));
    */
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
    while (!$scheduled && $current_time_block < $this->num_time_blocks_per_day) {
      if ($this->days[$student->get_day_preference()][$current_time_block]->schedule_student($student)) {
        $scheduled = true;
      }
      $current_time_block++;
    }

    // Student was unable to be scheduled for their requested date
    if ($current_time_block > $this->num_time_blocks_per_day && !$scheduled) {
      // might want to try adding them on another competition day?
      return false;
    }

    return true;
  }

  /**
   * This function will print the schedule in a human-readable format.
   */
  public function print_schedule() {
    echo "<br>";
    for ($i = 0; $i < $this->num_days; $i++) {
      switch ($i) {
        case SAT:
          echo 'SATURDAY' . "<br>";
        break;

        case SUN:
          echo 'SUNDAY' . "<br>";
        break;
      }

      for ($j = 0; $j < $this->num_time_blocks_per_day; $j++) {
        echo 'Time Block # ' . $j . "<br>";
        $this->days[$i][$j]->print_schedule();
      }

      echo "<br>";
    }

    echo "<br>";
    wp_die('schedule complete');
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
