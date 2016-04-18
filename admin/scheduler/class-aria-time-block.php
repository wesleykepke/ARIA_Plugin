<?php

/**
 * The time block object used for scheduling.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 */

require_once(ARIA_ROOT . "/includes/class-aria-api.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-section.php");

/**
 * The time block object used for scheduling.
 *
 * This class defines a time block object, which will be used throughout the
 * scheduling process. This object will represent a given time block in a
 * competition, which can be considered to be a block of time (9:00 - 9:45, for
 * example) that students will be scheduled in. For each time block, there will
 * be an arbitrary number of concurrent sections that will be occuring
 * simultaneously (the number of concurrent sections is determined by the
 * festival chairman). These concurrent sections may be of different types
 * (master, traditional, etc.) and even of different skill levels (1-11).
 *
 * @package    ARIA
 * @subpackage ARIA/admin
 * @author     KREW
 */
class TimeBlock {

  /**
   * The number of concurrent sections per time block (determined by the
   * festival chairman).
   *
   * @since 1.0.0
   * @access private
   * @var 	int 	$num_concurrent_sections 	The number of concurrent sections.
   */
  private $num_concurrent_sections;

  /**
   * The array of section objects per time block.
   *
   * @since 1.0.0
   * @access private
   * @var 	array 	$sections 	The concurrent section objects.
   */
  private $sections;

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
   * The array of room titles for the time blocks's sections. 
   *
   * @since 1.0.0
   * @access private
   * @var   array   $rooms   The room names for each section of the time block.
   */
  private $rooms;

  /**
   * The constructor used to instantiate a new time block object.
   *
   * @since 1.0.0
   * @param	int 	$num_concurrent_sections 	The number of concurrent sections.
   * @param	int 	$time_block_duration 	The length of the concurrent sections.
   * @param int 	$song_threshold 	The amount of times a song can be played in this section.
   * @param boolean 	$group_by_level 	True if single level only, false otherwise. 
   * @param string  $start_time   The start time of the current time block.
   * @param string  $day  The day of the current time block.
   * @param array   $rooms  The array of room names/numbers. 
   */
  function __construct($num_concurrent_sections, $time_block_duration,
                       $song_threshold, $group_by_level, $start_time, 
                       $day, $rooms) {
    $this->num_concurrent_sections = $num_concurrent_sections;
    $this->sections = new SplFixedArray($num_concurrent_sections);
    $this->start_time = $start_time;
    $this->day = $day;
    $this->rooms = $rooms;   
    for ($i = 0; $i < $num_concurrent_sections; $i++) {
      $this->sections[$i] = new Section($time_block_duration, $song_threshold, 
                                        $group_by_level, $start_time, $day,
                                        $rooms[$i]);
    }
  }

  /**
   * The function will attempt to schedule a student in the current time block.
   *
   * This function will iterate over all of the section objects in the current
   * time block and attempt to add the incoming student to one of the sections.
   * This function will return true if the given student object was added to
   * a section in the current time block and false otherwise.
   *
   * @since 1.0.0
   * @param	Student	$student	The student that needs to be scheduled.
   *
   * @return true if the student was added, false otherwise
   */
  public function schedule_student($student) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      if ($this->sections[$i]->add_student($student)) {
        return true;
      }
    }

    return false;
  }

  /**
   * This function will assign a section within the current time block to be a
   * master-class section.
   *
   * @param	int 	$master_class_instructor_duration 	The time that each judge has to spend with students.
   *
   * @return true if section was designated as a master-class section, false otherwise
   */
  public function assign_section_to_master($master_class_instructor_duration) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      if ($this->sections[$i]->assign_section_to_master($master_class_instructor_duration)) {
        return true;
      }
    }

    return false;
  }

  /**
   * This function will print the sections in a given time block object.
   */
  public function print_schedule() {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      echo '<b>Section # ' . $i . '</b><br>';
      $this->sections[$i]->print_schedule();
      echo '<br>';
    }
  }

  /**
   * This function will help add to the schedule for the competition using HTML.
   *
   * Since the schedule is best demonstrated using HTML tables and lists, this
   * function is responsible for adding onto the previously created HTML. The
   * creation of the inner HTML will be abstracted away to the section objects.
   *
   * @return	string	The generated HTML output
   */
  public function get_schedule_string() {
    $schedule = '';
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $schedule .= '<tr><th>';
      $schedule .= 'Section #';
      $schedule .= strval($i + 1) . ' -- ';
      $schedule .= $this->rooms[$i];
      $schedule .= ', ' . $this->sections[$i]->get_section_info();
      $schedule .= $this->sections[$i]->get_schedule_string();
      $schedule .= '</th></tr>';
    }
    return $schedule;
  }

  /**
   * This function will find all of the students participating in a timeblock
   * and group them by teacher email.
   *
   * This function will accept a teacher's email as a parameter. Using this value,
   * the timeblock will then iterate through all of it's sections and find all 
   * of the students scheduled in the competition that had registered under the 
   * teacher's email that was passed as a parameter.
   *
   * @param 	String	$teacher_email	The email of the teacher to group students by.
   * @param	Array	$students	The array of students that registered under the teacher. 
   */
  public function group_all_students_by_teacher_email($teacher_email, &$students) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->group_all_students_by_teacher_email($teacher_email, $students);
    }
  }

  /**
   * This function will consolidate all scheduling data into a format suitable for
   * the document generator. 
   *
   * This function will iterate through all section objects of a given timeblock
   * object. For each section, all student data will be added in a format that is 
   * compatible with that required by the document generator.
   *
   * @param   Array   $doc_gen_section_daya An associative array of all student data in doc. gen. compatible form. 
   */
  public function get_section_info_for_doc_gen(&$doc_gen_section_data) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->get_section_info_for_doc_gen($doc_gen_section_data); 
    } 
  }

  /**
   * This function will assign judges to the current competition. 
   *
   * Using an array of names (for judges) that is passed as a parameter, this
   * function will assign the judges in the competition to the sections within
   * each of the timeblocks. 
   *
   * @param   Array   $judges   The array of judges in the current competition.
   * @param   Integer   $judge_count  An integer to help offset into $judges.  
   */
  public function assign_judges($judges, $judge_count) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->assign_judge($judges[$judge_count % count($judges)]);
      $judge_count++; 
    }    
  }

  /**
   * This function will assign proctors to the current competition. 
   *
   * Using an array of names (for proctors) that is passed as a parameter, this
   * function will assign the proctors in the competition to the sections within
   * each of the timeblocks. 
   *
   * @param   Array   $proctors   The array of proctors in the current competition.
   * @param   Integer   $proctor_count  An integer to help offset into $proctors.   
   */
  public function assign_proctors($proctors, $proctor_count) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->assign_proctor($proctors[$proctor_count % count($proctors)]);
      $proctor_count++; 
    }   
  }

  /**
   * The destructor used when a time block object is destroyed.
   *
   * @since 1.0.0
   */
  function __destruct() {
    unset($this->num_concurrent_sections);
    unset($this->sections);
  }
}
