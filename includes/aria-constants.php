<?php

/**
 * The file holds all of the constants that are used throughout ARIA.
 *
 * This file lists various constants and what they represent. The primary
 * purpose of this file is to be a single source of truth for all constants
 * used throughout the program.
 *
 * @link       http://wesleykepke.github.io/ARIA/
 * @since      1.0.0
 *
 * @package    ARIA
 * @subpackage ARIA/includes
 */

// ARIA plugin file location
define("ARIA_ROOT", "/var/www/html/wp-content/plugins/ARIA");

// Competitions
define("CREATE_COMPETITION_FORM_NAME", "ARIA: Create a Competition");
define("MUSIC_UPLOAD_FORM_NAME", "ARIA: Upload Music");
define("NNMTA_MUSIC_DATABASE_NAME", "NNMTA: Music Database");
define("TEACHER_UPLOAD_FORM_NAME", "ARIA: Upload Teacher");
define("SCHEDULER_FORM_NAME", "ARIA: Schedule a Competition");
define("CSV_UPLOAD_FIELD_NAME", "CSV Music File");
define("CSV_TEACHER_FIELD_NAME", "CSV Teacher File");
define("CSV_JUDGE_FIELD_NAME", "CSV Judge File");
define("STUDENT_REG", "Student Registration");
define("STUDENT_MAS", "Student Master");
define("TEACHER_REG", "Teacher Registration");
define("TEACHER_MAS", "Teacher Master");

// Default password
define("CHAIRMAN_PASS", "collectrocks");

// Enumerating constants for competitions
const STUDENT_FORM = 1;
const STUDENT_MASTER = 2;
const TEACHER_FORM = 3;
const TEACHER_MASTER = 4;

/**
 * These constants are used to determine the type of section (traditional,
 * master-class, non-competitive, or command performance).
 */
const SECTION_MASTER = 0;
const SECTION_OTHER = 1;

// Constants used to help offset into the scheduler object
const SAT = 0;
const SUN = 1;
const COMMAND = 0;
const EITHER = 2;

// Constants used for student level
const LOW_LEVEL = 1;
const HIGH_LEVEL = 11;

// Constants used to create scheduler object
const REGULAR_COMP = 0;
const REGULAR_COMP_NUM_DAYS = 2;
const COMMAND_COMP = 1;
const COMMAND_COMP_NUM_DAYS = 1;
const PLAY_TIME_FACTOR = 0.8;
const DEFAULT_SECTION_TIME = 45;
const NO_SONG_THRESHOLD = 999999;
