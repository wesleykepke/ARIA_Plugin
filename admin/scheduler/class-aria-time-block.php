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

/*
require_once(ARIA_ROOT . "/includes/class-aria-api.php");
require_once(ARIA_ROOT . "/admin/scheduler/class-aria-section.php");
*/

require_once("class-aria-section.php");

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
   * The location for this timeblock.
   *
   * @since 1.0.0
   * @access private
   * @var   string   $location   The location specified by the festival chair.
   */
  private $location;

  /**
   * The date of this timeblock.
   *
   * @since 1.0.0
   * @access private
   * @var   string   $date   The date specified by the festival chair.
   */
  private $date;

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
   * @param string  $location The location for this timeblock.
   * @param string  $date   The date of this timeblock.
   */
  function __construct($num_concurrent_sections, $time_block_duration,
                       $song_threshold, $group_by_level, $start_time,
                       $day, $rooms, $location, $date) {
    $this->num_concurrent_sections = $num_concurrent_sections;
    $this->sections = new SplFixedArray($num_concurrent_sections);
    $this->start_time = $start_time;
    $this->day = $day;
    $this->rooms = $rooms;
    $this->location = $location;
    $this->date = $date;
    for ($i = 0; $i < $num_concurrent_sections; $i++) {
      $this->sections[$i] = new Section($time_block_duration, $song_threshold,
                                        $group_by_level, $start_time, $day,
                                        $rooms[$i], $location, $date);
    }
  }

  /**
   * This function will return the number of concurrent sections in the current timeblock.
   *
   * @return Integer designating the number of concurrent sections within the timeblock.
   */
  public function get_num_concurrent_sections() {
    return $this->num_concurrent_sections;
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
   * DELETE THIS FUNCTION
   */
  public function print_schedule() {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      echo '<b>Section # ' . $i . '</b><br>';
      $this->sections[$i]->print_schedule();
      echo '<br>';
    }
  }

  /**
   * This function will add students and teachers into an array.
   *
   * This function will iterate through all of the students in the section
   * and add them as a value under their teacher's email (the key).
   *
   * @param   $teacher_emails_to_students   The array that maps teacher emails to students
   *
   * @return void
   */
  public function group_students_by_teacher_email(&$teacher_emails_to_students) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->group_students_by_teacher_email($teacher_emails_to_students);
    }
  }

  /**
   * This function will help add to the schedule for the competition using HTML.
   *
   * Since the schedule is best demonstrated using HTML tables and lists, this
   * function is responsible for adding onto the previously created HTML. The
   * creation of the inner HTML will be abstracted away to the section objects.
   *
   * @param   Integer   $day  The integer constant for a given day
   *
   * @return	string	The generated HTML output
   */
  public function get_schedule_string($day) {
    $schedule = '';
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $schedule .= '<tr><th id="section-info" class="section">';
      $schedule .= 'Section #';
      $schedule .= strval($i + 1);
      $schedule .= $this->sections[$i]->get_section_info();
      $schedule .= $this->sections[$i]->get_schedule_string($day);
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
   * @param   Int   $num_judges_per_section   The number of judges that should be assigned to a section.
   */
  public function assign_judges($judges, $judge_count, $num_judges_per_section) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      for ($j = 0; $j < $num_judges_per_section; $j++) {
        $this->sections[$i]->assign_judge($judges[$judge_count % count($judges)]);
        $judge_count++;
      }
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
   * This function will update the sections with new information.
   *
   * Once the festival chairman has created a schedule for a competition and has
   * specified who will be the proctor, judge, etc. of a section, that information
   * will need to be added back into the scheduler. This function is responsible
   * for accepting that new information and helping place it in the right place
   * within a scheduler object.
   *
   * @param   Array   $new_section_data   The array of new section information.
   */
  public function update_section_data($new_section_data) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->update_section_data($new_section_data[$i]);
    }
  }

  /**
   * This function will update the current timeblock object with the new sections
   * that students are participating under.
   *
   * This function will accept as input an array of student objects. Next, this
   * function will iterate through all of it's concurrent sections and update
   * those sections with all of the new students that will be participating in
   * that section.
   *
   * @param   Array   $student_data   The array of student objects to update the timeblock.
   */
  public function update_section_students($student_data) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->update_section_students($student_data[$i]);
    }
  }

  /**
   * This function will search through the current timeblock object and locate
   * the student entry.
   *
   * Given an array of student information (name, skill level, song #1, and song #2),
   * this function will iterate through the given timeblock object and return the
   * student object that the incoming information associates with (if this student
   * object exists within the current timeblock).
   *
   * @param   $student_to_find  Array   Contains name, skill level, and both songs
   *
   * @return  Student Object  The actual student object that the information associates with.
   */
  public function find_student_entry($student_to_find) {
    $student_object = null;
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $student_object = $this->sections[$i]->find_student_entry($student_to_find);
      if (!is_null($student_object)) {
        return $student_object;
      }
    }

    return $student_object;
  }

  /**
   * Function for sending emails to all parents of students within a section.
   */
  public function send_parents_competition_info($headers, $fc_email) {
    for ($i = 0; $i < $this->num_concurrent_sections; $i++) {
      $this->sections[$i]->send_parents_competition_info($headers, $fc_email);
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
