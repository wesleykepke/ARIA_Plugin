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

require_once("class-aria-time-block.php");
require_once("class-aria-student.php");
require_once("class-aria-field-ids.php");
require_once("scheduler-constants.php");

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
   * The first location of the music competition.
   *
   * @since 1.0.0
   * @access private
   * @var	string $first_location	The main (and possibly first) location of a competition.
   */
  private $first_location;

  /**
   * The second location of the music competition.
   *
   * @since 1.0.0
   * @access private
   * @var	string $second_location	The second (optional) location of a competition.
   */
  private $second_location;

  /**
   * The related information of the music competition.
   *
   * This is an array that stores the following infomration regarding a competition:
   * student_form_id
   * teacher_form_id
   * student_master_form_id
   * teacher_master_form_id
   * student_public_form_url
   * teacher_public_form_url
   * festival_chairman_email
   *
   * @since 1.0.0
   * @access private
   * @var	array $related_comp_info	An array that stored various info associated with a competition.
   */
  private $related_comp_info;

  /**
   * The first date of the music competition.
   *
   * @since 1.0.0
   * @access private
   * @var	string $first_date	The beginning of the competition.
   */
  private $first_date;

  /**
   * The second date of the music competition.
   *
   * @since 1.0.0
   * @access private
   * @var	string $second_date	The ending of the competition.
   */
  private $second_date;

  /**
   * The constructor used to instantiate a new scheduler object.
   *
   * A new scheduler object will be created depending on the type of competition
   * specified through the parameter.
   *
   * @param   int   $competition_type   The type of the competition.
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

    // provide default values for the rest of the competition attributes
    $this->first_location = null;
    $this->second_location = null;
    $this->related_comp_info = null;
    $this->first_date = null;
    $this->second_date = null;
  }

  /**
   * This function will create the structure for a normal competition.
   *
   * Using the parameters passed to this function, the current scheduler object
   * will be created using the structure of a regular competition.
   *
   * @param int	$num_time_blocks_sat	The number of time blocks on saturday.
   * @param	int	$num_time_blocks_sun	The number of time blocks on sunday.
   * @param	int	$time_block_duration	The amount of time allocated to each timeblock.
   * @param array   $sat_start_times   The array of start times for saturday.
   * @param array   $sun_start_times   The array of start times for sunday.
   * @param	int	$num_concurrent_sections_sat	The number of sections per timeblock on saturday.
   * @param	int	$num_concurrent_sections_sun	The number of sections per timeblock on sunday.
   * @param	int	$num_master_sections_sat	The number of master-class sections on saturday.
   * @param	int	$num_master_sections_sun	The number of master-class sections on sunday.
   * @param int 	$song_threshold 	The amount of times a song can be played in this section.
   * @param boolean $group_by_level 	True if single level only, false otherwise.
   * @param	int 	$master_class_instructor_duration 	The time that each judge has to spend with students.
   * @param array   $saturday_rooms  The array of room assignments for saturday.
   * @param array   $sunday_rooms  The array of room assignments for sunday.
   * @param string  $first_location   The first location of the competition.
   * @param string  $second_location  The second location of the competition (optional).
   * @param array   $related_comp_info  An array that stores various info about the competition.
   * @param string  $first_date   The first day of competition.
   * @param string  $second_date  The second day of competition.
   *
   * @since 1.0.0
   * @author KREW
   */
  public function create_normal_competition($num_time_blocks_sat,
                                            $num_time_blocks_sun,
                                            $time_block_duration,
                                            $sat_start_times,
                                            $sun_start_times,
                                            $num_concurrent_sections_sat,
                                            $num_concurrent_sections_sun,
                                            $num_master_sections_sat,
                                            $num_master_sections_sun,
                                            $song_threshold,
                                            $group_by_level,
                                            $master_class_instructor_duration,
                                            $saturday_rooms,
                                            $sunday_rooms,
                                            $first_location,
                                            $second_location,
                                            $related_comp_info,
                                            $first_date,
                                            $second_date) {
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

    // assign the location of the competition
    $this->first_location = $first_location;
    if (is_null($second_location)) {
      $this->second_location = $first_location;
    }
    else {
      $this->second_location = $second_location;
    }

    // assign the related competition info
    $this->related_comp_info = $related_comp_info;

    // assign the dates of the competition
    $this->first_date = $first_date;
    $this->second_date = $second_date;

    // preprocess the rooms for saturday
    for ($i = 0; $i < $num_concurrent_sections_sat; $i++) {
      if ($saturday_rooms != false && array_key_exists($i, $saturday_rooms)) {
        $saturday_rooms[$i] = $saturday_rooms[$i];
      }
      else {
        $saturday_rooms[$i] = strval($i + 1);
      }
    }

    // preprocess the rooms for sunday
    for ($i = 0; $i < $num_concurrent_sections_sun; $i++) {
      if ($sunday_rooms != false && array_key_exists($i, $sunday_rooms)) {
        $sunday_rooms[$i] = $sunday_rooms[$i];
      }
      else {
        $sunday_rooms[$i] = strval($i + 1);
      }
    }

    // preprocess the start times for saturday
    for ($i = 0; $i < $num_time_blocks_sat; $i++) {
      if ($sat_start_times != false && array_key_exists($i, $sat_start_times)) {
        $sat_start_times[$i] = $sat_start_times[$i];
      }
      else {
        $sat_start_times[$i] = "TYPE IN START TIME";
      }
    }

    // preprocess the start times for sunday
    for ($i = 0; $i < $num_time_blocks_sun; $i++) {
      if ($sun_start_times != false && array_key_exists($i, $sun_start_times)) {
        $sun_start_times[$i] = $sun_start_times[$i];
      }
      else {
        $sun_start_times[$i] = "TYPE IN START TIME";
      }
    }

    // create the time blocks with their concurrent sections for saturday
    $this->days[SAT] = new SplFixedArray($num_time_blocks_sat);
    for ($i = 0; $i < $num_time_blocks_sat; $i++) {
      $this->days[SAT][$i] = new TimeBlock($num_concurrent_sections_sat, $time_block_duration,
                                           $song_threshold, $group_by_level,
                                           $sat_start_times[$i],
                                           'Saturday', $saturday_rooms,
                                           $this->first_location,
                                           $this->first_date);
    }

    // designate some of the sections on saturday for master-class students
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
                                           $sun_start_times[$i],
                                           'Sunday', $sunday_rooms,
                                           $this->second_location,
                                           $this->second_date);
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
        $student->set_date($this->first_date);
      break;

      case SUN:
        $preferred_day_num_time_blocks = $this->days[SUN]->getSize();
        $student->set_date($this->second_date);
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
      wp_die("Student did not get scheduled in their day preference. Please readjust schedule paramaters.");
      return false;
    }

    return true;
  }

  /**
   * This function will print the schedule in a human-readable format.
   * DELETE THIS FUNCTION
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
   * @param   $forRerendering   Boolean   Determines if this HTML is for rerendering purposes.
   *
   * @return	string	The generated HTML output
   */
  public function get_schedule_string($forRerendering) {
    // if used for rerendering purposes, we do not need to include outermost <div id="schedule"> tag
    if ($forRerendering) {
      $schedule = '<div id="schedule-table">';
    }
    else {
      $schedule = '<div id="schedule"><div id="schedule-table">';
    }

    // iterate through the internal structure of the scheduler and generate the HTML
    for ($i = 0; $i < count($this->days); $i++) {
      switch ($i) {
        case SAT:
          $schedule .= '<table style="float: left; width: 50%;">';
          $schedule .= '<tr><th>Saturday (' . $this->first_date . ')</th></tr>';
          for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
            $schedule .= '<tr><td>';
            $schedule .= '<tr><th>';
            $schedule .= 'Timeblock # ' . strval($j + 1);
            $schedule .= $this->days[$i][$j]->get_schedule_string(SAT);
            $schedule .= '</th></tr>';
            $schedule .= '</td></tr>';
          }
        break;

        case SUN:
          $schedule .= '<tr><table style="float: right; width: 50%;">';
          $schedule .= '<tr><th>Sunday (' . $this->second_date . ')</th></tr>';
          for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
            $schedule .= '<tr><td>';
            $schedule .= '<tr><th>';
            $schedule .= 'Timeblock # ' . strval($j + 1);
            $schedule .= $this->days[$i][$j]->get_schedule_string(SUN);
            $schedule .= '</th></tr>';
            $schedule .= '</td></tr>';
          }
        break;
      }

      $schedule .= '</table>';
    }

    // properly close the HTML
    if ($forRerendering) {
      $schedule .= "</div>";
    }
    else {
      $schedule .= "</div></div>";
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
   * This function will assign judges to the current competition.
   *
   * Using an array of names (for judges) that is passed as a parameter, this
   * function will assign the judges in the competition to timeblocks, which
   * will then have the responsibility of assigning the judges to the sections
   * within the timeblocks.
   *
   * @param   Array   $judges   The array of judges in the current competition.
   * @param   Int   $num_judges_per_section   The number of judges that should be assigned to a section.
   */
  public function assign_judges($judges, $num_judges_per_section) {
    $judge_count = 0;
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $this->days[$i][$j]->assign_judges($judges, $judge_count, $num_judges_per_section);
      }
    }
  }

  /**
   * This function will assign proctors to the current competition.
   *
   * Using an array of names (for proctors) that is passed as a parameter, this
   * function will assign the proctors in the competition to timeblocks, which
   * will then have the responsibility of assigning the proctors to the sections
   * within the timeblocks.
   *
   * @param   Array   $proctors   The array of proctors in the current competition.
   */
  public function assign_proctors($proctors) {
    $proctor_count = 0;
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $this->days[$i][$j]->assign_proctors($proctors, $proctor_count);
      }
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
   * @param   Array   $modifiable_data The array of new section information.
   */
  public function update_section_data($modifiable_data) {
    $modifiable_data_index = 0;
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $new_timeblock_data = array();
        for ($k = 0; $k < $this->days[$i][$j]->get_num_concurrent_sections(); $k++) {
          $new_timeblock_data[] = $modifiable_data[$modifiable_data_index]['data'];
          $modifiable_data_index++;
        }
        $this->days[$i][$j]->update_section_data($new_timeblock_data);
      }
    }
  }

  /**
   * This function will update the current scheduler object with the new sections
   * that students are participating under.
   *
   * This function will accept as input an array of student information (one element
   * of the array contains all students that are performing in that section)
   * that will contain students from the schedule (name, skill level, and the songs that
   * they are playing). Using that information the function will search through
   * the current scheduler object and locate the associated student entry. Using
   * that student entry (and all of the information that comes with it), the function
   * will place that student in their new location in the competition.
   *
   * @param   Array   $student_data   The array of student information to use in the search process.
   */
  public function update_section_students($student_data) {
    // create a new 2D array containing the student entry objects
    $new_section_data = array();
    $section_index = 0;
    for ($i = 0; $i < count($student_data); $i++) {
      if (is_array($student_data[$i])){
        $new_section_data[$section_index] = array();
        for ($j = 0; $j < count($student_data[$i]); $j++) {
          $student = $this->find_student_entry($student_data[$i][$j]);
          if (!is_null($student)) {
            array_push($new_section_data[$section_index], $student);
          }
        }
      }
      else {
        $new_section_data[$section_index] = "EMPTY";
      }

      $section_index++;
    }

    // iterate through all sections of the scheduler and update the students
    // that are assigned to each section
    $section_index = 0;
    $new_timeblock_data = array();
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $new_timeblock_students = array();
        for ($k = 0; $k < $this->days[$i][$j]->get_num_concurrent_sections(); $k++) {
          array_push($new_timeblock_students, $new_section_data[$section_index]);
          $section_index++;
        }

        array_push($new_timeblock_data, $new_timeblock_students);
        $this->days[$i][$j]->update_section_students($new_timeblock_students);
      }
    }
  }

  /**
   * This function will search through the current scheduler object and locate
   * the student entry.
   *
   * Given an array of student information (name, skill level, song #1, and song #2),
   * this function will iterate through the given scheduler object and return the
   * student object that the incoming information associates with.
   *
   * @param   $student_to_find  Array   Contains name, skill level, and both songs
   *
   * @return  Student Object  The actual student object that the information associates with.
   */
  private function find_student_entry($student_to_find) {
    $student_object = null;
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $student_object = $this->days[$i][$j]->find_student_entry($student_to_find);
        if (!is_null($student_object)) {
          return $student_object;
        }
      }
    }

    return $student_object;
  }

  /**
   * This function will send an email to each of the teachers in the competition.
   *
   * Once registration is complete, the teachers in the competition need to be
   * emailed information regarding when each of their students is competing and
   * about their volunteer duties. This function is responsible for generating
   * and sending that email.
   *
   * @param 	String 		$comp_name 		The name of the competition.
   *
   * @return	void
   *
   * @since 1.0.0
   * @author KREW
   */
  public function send_teachers_competition_info($comp_name) {
    // find the associated teacher master form
    $teacher_master_form_id = $this->related_comp_info['teacher_master_form_id'];
    $fc_email = $this->related_comp_info['festival_chairman_email'];
    $SAT = 0;
    $SUN = 1;

    // group all of the students in the scheduler object by teacher
    $teacher_emails_to_students = array();
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $this->days[$i][$j]->group_students_by_teacher_email($teacher_emails_to_students);
      }
    }

    // for each of the email-student relationships that were found, generate an email message
    foreach ($teacher_emails_to_students as $key => $value) {
      if (strpos($key, '@') !== false) {
        // handle the location for the emails
        $sat_email_message = "Saturday Location: $this->first_location\n";
        if (is_null($this->second_location)) {
          $sun_email_message = "Sunday Location: $this->first_location\n";
        }
        else {
          $sun_email_message = "Sunday Location: $this->second_location\n";
        }

        // iterate through all of the students for a given email
        foreach ($value as $student) {
          // students who requested saturday
          if ($student->get_day_preference() === $SAT) {
            $sat_email_message .= $student->get_info_for_email();
          }

          // students who requested sunday
          else if ($student->get_day_preference() === $SUN) {
            $sun_email_message .= $student->get_info_for_email();
          }
        }

        // once the message has been generated, send the email to the teachers (per teacher basis)
        $email_message = $sat_email_message . "\n\n" . $sun_email_message;
        $email_message .= "\n\nIf you have any questions, please contact the festival chair at $fc_email.";
        if (!is_null($email_message)) {
          $subject = "Student Assignments for " . $comp_name;
          $headers = "From: " . $this->related_comp_info['festival_chairman_email'];
          if (!mail($key, $subject, $email_message, $headers)) {
            die("<h1>Emails to teachers regarding competition info failed to send.
          	  Please try again.</h1>");
          }
        }
      }
    }
  }

 /**
  * This function will send comperition info to the parents.
  *
  * This function will initiate the the process of sending emails to all of the
  * parents who have children participating in the competition.
  *
  * @param  String  $comp_name  The name of the competition.
  *
  * @return	void
  *
  * @since 1.0.0
  * @author KREW
  */
  public function send_parents_competition_info($comp_name) {
    // obtain data that will need to be sent to the timeblocks and sections
    $student_master_form_id = $this->related_comp_info['student_master_form_id'];
    $headers = "From: " . $this->related_comp_info['festival_chairman_email'];
    $fc_email = $this->related_comp_info['festival_chairman_email'];

    // iterate through all of the student entries
    for ($i = 0; $i < count($this->days); $i++) {
      for ($j = 0; $j < $this->days[$i]->getSize(); $j++) {
        $this->days[$i][$j]->send_parents_competition_info($headers, $fc_email,
                                                           $this->first_location, $this->second_location);
      }
    }
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
