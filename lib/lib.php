<?php
// This file is part of Exabis Planning Tool
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Competence Grid is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

defined('MOODLE_INTERNAL') || die();

/**
 * DATABSE TABLE NAMES
 */
const BLOCK_EXAPLAN_DB_MODULESETS = 'block_exaplanmodulesets';
const BLOCK_EXAPLAN_DB_MODULEPARTS = 'block_exaplanmoduleparts';
const BLOCK_EXAPLAN_DB_DATES = 'block_exaplandates';

/**
 * DATE STATES
 */
const BLOCK_EXAPLAN_DATE_PROPOSED = 1;
const BLOCK_EXAPLAN_DATE_CONFIRMED = 2;


/**
 *
 * @param courseid or context $context
 */
function block_exaplan_is_admin($context = null)
{
    $context = block_exaplan_get_context_from_courseid($context);
    return has_capability('block/exaplan:admin', $context);
}

/**
 *
 * @param courseid or context $context
 * @param userid $userid
 */
function block_exaplan_is_teacher($context = null, $userid = null)
{
    $context = block_exaplan_get_context_from_courseid($context);
    return has_capability('block/exaplan:teacher', $context, $userid);
}

function block_exaplan_is_teacher_in_any_course()
{
    global $USER;

    $courses = block_exaplan_get_courses($USER->id);

    foreach ($courses as $course) {
        $context = context_course::instance($course["courseid"]);

        $isTeacher = block_exaplan_is_teacher($context);

        if ($isTeacher) {
            return true;
        }
    }

    return false;
}

function block_exaplan_get_courses($userid = null)
{
    global $CFG, $DB, $USER;
    require_once("$CFG->dirroot/lib/enrollib.php");

    static::validate_parameters(static::get_courses_parameters(), array(
        'userid' => $userid,
    ));

    if (!$userid) {
        $userid = $USER->id;
    }

    static::require_can_access_user($userid);

    $mycourses = enrol_get_users_courses($userid, true);
    $courses = array();

    $time = time();

    foreach ($mycourses as $mycourse) {
        if ($mycourse->visible == 0 || $mycourse->enddate < $time && $mycourse->enddate != 0) { //enddate is a smaller number than today ==> NOT visible, since it is over already
            continue;
        }

        $context = context_course::instance($mycourse->id);
        if ($DB->record_exists("block_instances", array(
            "blockname" => "exacomp",
            "parentcontextid" => $context->id,
        ))
        ) {

            if (block_exacomp_is_teacher($mycourse->id, $userid)) {
                $exarole = BLOCK_EXACOMP_WS_ROLE_TEACHER;

                $teachercanedit = block_exacomp_is_editingteacher($mycourse->id, $userid);
            } else {
                $exarole = BLOCK_EXACOMP_ROLE_STUDENT;
                $teachercanedit = false;
            }

            $course = array(
                "courseid" => $mycourse->id,
                "fullname" => $mycourse->fullname,
                "shortname" => $mycourse->shortname,
                "assessment_config" => $DB->get_field('block_exacompsettings', 'assessmentconfiguration', ['courseid' => $mycourse->id]),
                "exarole" => $exarole,
                "teachercanedit" => $teachercanedit,
            );
            $courses[] = $course;
        }
    }

    return $courses;
}

function block_exaplan_get_context_from_courseid($courseid)
{
    global $COURSE;

    if ($courseid instanceof context) {
        // already context
        return $courseid;
    } else if (is_numeric($courseid)) { // don't use is_int, because eg. moodle $COURSE->id is a string!
        return context_course::instance($courseid);
    } else if ($courseid === null) {
        return context_course::instance($COURSE->id);
    } else {
        throw new \moodle_exception('wrong courseid type ' . gettype($courseid));
    }
}

class block_exaplan_permission_exception extends moodle_exception
{
    function __construct($errorcode = 'Not allowed', $module = '', $link = '', $a = null, $debuginfo = null)
    {
        return parent::__construct($errorcode, $module, $link, $a, $debuginfo);
    }
}


/**
 * @param $title
 * @param $description
 * @param null $trainerpuserid
 * @param null $location
 * @param null $courseidnumber
 * @return bool|int
 * @throws dml_exception
 */
function block_exaplan_create_moduleset($title, $description, $trainerpuserid = null, $location = null, $courseidnumber = null)
{
    global $DB;

    // Only the admin can create modulesets
//    if (!block_exaplan_is_admin()) {
//        throw new block_exaplan_permission_exception("User must be admin!");
//    }

    $moduleset = new stdClass();
    $moduleset->title = $title;
    $moduleset->description = $description;
    $moduleset->trainerpuserid = $trainerpuserid;
    $moduleset->location = $location;
    $moduleset->courseidnumber = $courseidnumber;

    return $DB->insert_record(BLOCK_EXAPLAN_DB_MODULESETS, $moduleset);
}

/**
 * @param $modulesetid
 * @param null $title
 * @param null $description
 * @param null $trainerpuserid
 * @param null $location
 * @param null $courseidnumber
 * @return bool
 * @throws dml_exception
 */
function block_exaplan_update_moduleset($modulesetid, $title = null, $description = null, $trainerpuserid = null, $location = null)
{
    global $DB;

    // Only the admin can update modulesets
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    $moduleset = $DB->get_record(BLOCK_EXAPLAN_DB_MODULESETS, array('id' => $modulesetid));
    if ($title) $moduleset->title = $title;
    if ($description) $moduleset->description = $description;
    if ($trainerpuserid) $moduleset->trainerpuserid = $trainerpuserid;
    if ($location) $moduleset->location = $location;
//    if ($courseidnumber) $moduleset->courseidnumber = $courseidnumber; should not be updated

    return $DB->update_record(BLOCK_EXAPLAN_DB_MODULESETS, $moduleset);
}

function block_exaplan_delete_moduleset($modulesetid)
{
    global $DB;

    // Only the admin can edit modulesets
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    //delete moduleset
    $DB->delete_records(BLOCK_EXAPLAN_DB_MODULESETS, array('id' => $modulesetid));
}


/**
 * @param $title
 * @param $description
 * @param null $trainerpuserid
 * @param null $location
 * @param null $courseidnumber
 * @return bool|int
 * @throws dml_exception
 */
function block_exaplan_create_modulepart($modulesetid, $title, $duration)
{
    global $DB;

    // Only the admin can create moduleparts
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    $modulepart = new stdClass();
    $modulepart->title = $title;
    $modulepart->modulesetid = $modulesetid;
    $modulepart->duration = $duration;

    return $DB->insert_record(BLOCK_EXAPLAN_DB_MODULEPARTS, $modulepart);
}

/**
 * @param $modulepartid
 * @param null $title
 * @param null $description
 * @param null $trainerpuserid
 * @param null $location
 * @param null $courseidnumber
 * @return bool
 * @throws dml_exception
 */
function block_exaplan_update_modulepart($modulepartid, $title = null, $duration = null)
{
    global $DB;

    // Only the admin can update moduleparts
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    $modulepart = $DB->get_record(BLOCK_EXAPLAN_DB_MODULEPARTS, array('id' => $modulepartid));
//    if ($modulesetid) $modulepart->modulesetid = $modulesetid; there is no situation where this could be updated
    if ($title) $modulepart->title = $title;
    if ($duration) $modulepart->duration = $duration;

    return $DB->update_record(BLOCK_EXAPLAN_DB_MODULEPARTS, $modulepart);
}


function block_exaplan_delete_modulepart($modulepartid)
{
    global $DB;

    // Only the admin can delete moduleparts
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    //delete modulepart
    $DB->delete_records(BLOCK_EXAPLAN_DB_MODULEPARTS, array('id' => $modulepartid));
}


function block_exaplan_create_date($modulepartid, $date, $timeslot, $state, $location = null, $trainerpuserid = null, $creatorpuserid, $creatortimestamp, $modifieduserid = null, $modifiedtimestamp = null)
{
    global $DB;

    // Only the admin can create dates
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    $date = new stdClass();
    $date->modulepartid = $modulepartid;
    $date->date = $date;
    $date->timeslot = $timeslot;
    $date->state = $state;
    $date->location = $location;
    $date->trainerpuserid = $trainerpuserid;
    $date->creatorpuserid = $creatorpuserid;
    $date->creatortimestamp = $creatortimestamp;
    $date->modifieduserid = $modifieduserid;
    $date->modifiedtimestamp = $modifiedtimestamp;


    return $DB->insert_record(BLOCK_EXAPLAN_DB_DATES, $date);
}

// When will a date be updated? Actually it will only be created, deleted or confirmed --> only the state and modifieduserid and modifiedtimestamp will change
//function block_exaplan_update_date($dateid, $date, $timeslot, $state, $location=null, $trainerpuserid=null, $creatorpuserid, $creatortimestamp, $modifieduserid=null, $modifiedtimestamp=null)
//{
//    global $DB;
//
//    // Only the admin can update dates
//    if (!block_exaplan_is_admin()) {
//        throw new block_exaplan_permission_exception("User must be admin!");
//    }
//
//    $date = $DB->get_record(BLOCK_EXAPLAN_DB_DATES, array('id' => $dateid));
////    if ($modulepartid) $date->modulepartid = $modulepartid;
//    if ($date) $date->date = $date;
//    if ($date) $date->timeslot = $date;
//    if ($date) $date->date = $date;
//    if ($date) $date->date = $date;
//    if ($date) $date->date = $date;
//    if ($date) $date->date = $date;
//    if ($date) $date->date = $date;
//    if ($date) $date->date = $date;
//    if ($date) $date->date = $date;
//
//    $date->timeslot = $timeslot;
//    $date->state = $state;
//    $date->location = $location;
//    $date->trainerpuserid = $trainerpuserid;
//    $date->creatorpuserid = $creatorpuserid;
//    $date->creatortimestamp = $creatortimestamp;
//    $date->modifieduserid = $modifieduserid;
//    $date->modifiedtimestamp = $modifiedtimestamp;
//
//    return $DB->update_record(BLOCK_EXAPLAN_DB_DATES, $date);
//}
//


function block_exaplan_confirm_date($dateid)
{
    global $DB;

    // Only the admin can update dates
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    $date = $DB->get_record(BLOCK_EXAPLAN_DB_DATES, array('id' => $dateid));
    $date->state = BLOCK_EXAPLAN_DATE_CONFIRMED;

    return $DB->update_record(BLOCK_EXAPLAN_DB_DATES, $date);
}

function block_exaplan_delete_date($dateid)
{
    global $DB;

    // Only the admin can delete dates
    if (!block_exaplan_is_admin()) {
        throw new block_exaplan_permission_exception("User must be admin!");
    }

    //delete date
    $DB->delete_records(BLOCK_EXAPLAN_DB_DATES, array('id' => $dateid));
}


function block_exaplan_get_modulesets_by_puserid($puserid = null)
{
    global $DB, $USER;

    if ($puserid == null) {
        $puserid = $USER->id;
    }

    // Get all modulesets where the courseidnumber is in the courses of the user
    // Get all courses for this user (local) and get all modulesets from the main moodle where the courseidnumber equals one of the courses's coursidnumbers
    $modulesets = [];



//    $sql = 'SELECT s.stid FROM {' . BLOCK_EXAPLAN_DB_MODULESETS . '} t
//			JOIN {' . BLOCK_EXACOMP_DB_SUBJECTS . '} s ON t.subjid=s.id
//			WHERE t.id=?';
//
//    $modulesets = $DB->get_record_sql($sql, array());

    return $modulesets;

}


// TODO: mysql e

