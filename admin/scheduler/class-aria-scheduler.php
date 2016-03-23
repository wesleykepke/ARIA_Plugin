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
   * @param	int	$time_block_duration	The amount of time allocated to each timeblock.
   * @param	int	$num_concurrent_sections_sat	The number of sections/timeblock on saturday.
   * @param	int	$num_concurrent_sections_sun	The number of sections/timeblock on sunday.
   * @param	int	$num_master_sections_sat	The number of master-class sections on saturday.
   * @param	int	$num_master_sections_sun	The number of master-class sections on sunday.
   *
   * @since 1.0.0
   * @author KREW
   */
  public function create_normal_competition($num_time_blocks_sat,
                                            $num_time_blocks_sun,
                                            $time_block_duration,
                                            $num_concurrent_sections_sat,
                                            $num_concurrent_sections_sun,
                                            $num_master_sections_sat,
                                            $num_master_sections_sun) {
    // ensure the current scheduler object is for a regular competition
    if ($this->competition_type !== REGULAR_COMP) {
      return;
    }

    // create the time blocks with their concurrent sections for saturday
    $this->days[SAT] = new SplFixedArray($num_time_blocks_sat);
    for ($i = 0; $i < $num_time_blocks_sat; $i++) {
      $this->days[SAT][$i] = new TimeBlock($num_concurrent_sections_sat, $time_block_duration);
    }

    // designate some of the sections on saturday for master-class students
    while ($num_master_sections_sat > 0) {
      for ($i = ($num_time_blocks_sat - 1); $i >= ($num_time_blocks_sat / 2); $i--) {
        if ($num_master_sections_sat > 0 && $this->days[SAT][$i]->assign_section_to_master()) {
          $num_master_sections_sat--;
        }
      }
    }

    // create the time blocks with their concurrent sections for sunday
    $this->days[SUN] = new SplFixedArray($num_time_blocks_sun);
    for ($i = 0; $i < $num_time_blocks_sun; $i++) {
      $this->days[SUN][$i] = new TimeBlock($num_concurrent_sections_sun, $time_block_duration);
    }

    // designate some of the sections on sunday for master-class students
    while ($num_master_sections_sun > 0) {
      for ($i = ($num_time_blocks_sun - 1); $i >= ($num_time_blocks_sun / 2); $i--) {
        if ($num_master_sections_sun > 0 && $this->days[SUN][$i]->assign_section_to_master()) {
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
    for ($i = 0; $i < $num_time_blocks_sat; $i++) {
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
