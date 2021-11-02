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
const BLOCK_EXAPLAN_DB_PLANNOTIFICATIONS = 'block_EXAPLANNOTIFICATIONS';

/**
 * DATE STATES
 */
const BLOCK_EXAPLAN_DATE_PROPOSED = 1;
const BLOCK_EXAPLAN_DATE_CONFIRMED = 2;

/**
 * MIDDATE TYPES
 */
const BLOCK_EXAPLAN_MIDDATE_BEFORE = 1; // before midday
const BLOCK_EXAPLAN_MIDDATE_AFTER = 2; // after midday
const BLOCK_EXAPLAN_MIDDATE_ALL = 3; // all day


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
    global $CFG, $DB, $USER;

    $courses = $DB->get_records("course");
    foreach($courses as $course) {
        if($course->idnumber != 0){
            if (block_exaplan_is_teacher(block_exaplan_get_context_from_courseid($course->id))) {
                return true;
            }
        }
    }
    return false;
}

function block_exaplan_get_courses_where_isteacher()
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

function block_exaplan_get_courses()
{
    global $CFG, $DB, $USER;

    $ret = array();
    $courses = $DB->get_records("course");
    foreach($courses as $course) {
        if($course->idnumber != 0){
            $ret[] = $course;
        }
    }
    return $ret;
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

//nur der admin im centermoodle
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


// bleibt auch im centermoodle
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

// sollte sowohl der admin vorschlagen könne, als auch die teilnehmer wünschen, right?
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

// das passiert im centermoodle
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

/**
 *
 * Includes all neccessary JavaScript files
 */
function block_exaplan_init_js_css($courseid = 0)
{
    global $PAGE, $CFG;

    // only allowed to be called once
    static $js_inited = false;
    if ($js_inited) {
        return;
    }
    $js_inited = true;

    $PAGE->requires->jquery();
//    $PAGE->requires->jquery_plugin('ui');
//    $PAGE->requires->jquery_plugin('ui-css');

    // Moment.js
    $PAGE->requires->js("/blocks/exaplan/javascript/moment.js", true);
    $PAGE->requires->js("/blocks/exaplan/javascript/locale/moment/de.js", true);
    // TavoCalendar
    $PAGE->requires->js("/blocks/exaplan/javascript/TavoCalendar.js", true);
    $PAGE->requires->js("/blocks/exaplan/javascript/gtnTavoCalendar.js", true);
    $PAGE->requires->css('/blocks/exaplan/css/tavo-calendar.css');
    // Tooltip
    $PAGE->requires->js("/blocks/exaplan/javascript/tooltipster.bundle.min.js", true);
    $PAGE->requires->css('/blocks/exaplan/css/tooltipster.bundle.min.css');
    $PAGE->requires->css('/blocks/exaplan/css/tooltipster-sideTip-light.min.css');
    // preloadinator
//    $PAGE->requires->js("/blocks/exaplan/javascript/jquery.preloadinator.js", true);
//    $PAGE->requires->css('/blocks/exaplan/css/jquery.preloadinator.css');
    // threedots
//    $PAGE->requires->css('/blocks/exaplan/css/three-dots.min.css');
    // loader
    $PAGE->requires->css('/blocks/exaplan/css/loader.css');

    // jsCalendar
//    $PAGE->requires->css('/blocks/exaplan/css/jsCalendar.css');
//    $PAGE->requires->js("/blocks/exaplan/javascript/jsCalendar.js", true);
//    $PAGE->requires->js("/blocks/exaplan/javascript/locale/jsCalendar/jsCalendar.lang.de.js", true);

    // main block JS
    $PAGE->requires->js("/blocks/exaplan/javascript/block_exaplan.js", true);

    // main block CSS
    $PAGE->requires->css('/blocks/exaplan/css/block_exaplan.css');

    // page specific js/css
    $scriptName = preg_replace('!\.[^\.]+$!', '', basename($_SERVER['PHP_SELF']));
    if (file_exists($CFG->dirroot . '/blocks/exaplan/css/' . $scriptName . '.css')) {
        $PAGE->requires->css('/blocks/exaplan/css/' . $scriptName . '.css');
    }
    if (file_exists($CFG->dirroot . '/blocks/exaplan/javascript/' . $scriptName . '.js')) {
        $PAGE->requires->js('/blocks/exaplan/javascript/' . $scriptName . '.js', false);
    }
}

function block_exaplan_get_middate_string_key($keyIndex)
{
    switch ($keyIndex) {
        case BLOCK_EXAPLAN_MIDDATE_AFTER:
            return 'after';
        case BLOCK_EXAPLAN_MIDDATE_BEFORE:
            return 'before';
        case BLOCK_EXAPLAN_MIDDATE_ALL:
        default:
            return 'all';
            break;
    }
}

/**
 * gwt JSON calendar data for single user (pUser!)
 * @param $userid
 */
function block_exaplan_get_calendar_data($userid)
{
    global $USER;
    $data = [
        'selectedDates' => []
    ];


    $userModules = getModulesOfUser($USER->id, 1);
    $dateCounts = [];
    $selectedDates = [];
    foreach ($userModules as $module) {
        foreach ($module->parts as $part) {
            foreach ($part['date'] as $date) {
                $dateIndex = $date['date'];
                $dateIndex = date('Y-m-d', $dateIndex);
                if (!array_key_exists($dateIndex, $selectedDates)) {
                    $selectedDates[$dateIndex] = [
                        'date' => $dateIndex,
                        'type' => BLOCK_EXAPLAN_MIDDATE_ALL, // TODO: midday is needed????? they can be different for different module parts
                        'usedItems' => 0,   // TODO: possible different counters: dates/moduleparts
                    ];
                }
                $selectedDates[$dateIndex]['usedItems'] += 1;
            }
        }
    }
    $selectedDates = array_values($selectedDates); // clean keys. needed for correct JS function later

    $data['selectedDates'] = $selectedDates;

    return json_encode($data);
}

/**
 * @param int $puserid (null if needed daya about whole modulepart)
 * @param string $dataType desired | fixed | all
 * @param int $modulepartId (null if needed data about all moduleparts)
 * @param bool $readonly whole calendar is readonly
 * @param string $region filter by region
 * @return false|string
 */
function block_exaplan_get_data_for_calendar($puserid = null, $dataType = 'desired', $modulepartId = null, $readonly = false, $region = '')
{

    $data = [
        'selectedDates' => []
    ];

    switch ($dataType) {
        case 'desired': // only self desired dates
            $dates = getDesiredDates($puserid, $modulepartId, null, null, $region);
            break;
        case 'fixed': // dates, which were fixed by admin
            $dates = getFixedDates($puserid, $modulepartId);
            break;
        case 'all': // mix of dates. needed for fill the calendar
        default:
            $dates1 = getDesiredDates($puserid, $modulepartId, null, null, $region);
            $dates2 = getFixedDates($puserid, $modulepartId);
            $dates = array_merge($dates1, $dates2);
            break;
    }

    // calendar used dates
    $selectedDates = [];
    foreach ($dates as $date) {
        $dateIndex = $date['date'];
        $dateIndex = date('Y-m-d', $dateIndex);
        if (!array_key_exists($dateIndex, $selectedDates)) { // TODO: Check it if the user has fixed and desitrred the same date
            $selectedDates[$dateIndex] = [
                'date' => $dateIndex,
                'middayType' => $date['timeslot'],
                'usedItems' => 0,   // TODO: possible different counters: dates/moduleparts
                'dateType' => $date['dateType'], // needed?
                'desired' => false,
                'fixed' => false,
            ];
        }
        $selectedDates[$dateIndex]['usedItems'] += 1;
        $selectedDates[$dateIndex]['moduleparts'][] = $date['modulepartid'];
        $selectedDates[$dateIndex]['readonly'] = $readonly; // TODO: another rules for readonly days?
        $selectedDates[$dateIndex][$date['dateType']] = true;
    }

    $selectedDates = array_values($selectedDates); // clean keys. needed for correct JS function later
    $data['selectedDates'] = $selectedDates;
    return json_encode($data);
}


function block_exaplan_send_moodle_notification($notificationtype, $userfrom, $userto, $subject, $message, $context, $contexturl = null, $dakoramessage = false, $courseid = 0, $customdata = null)
{
    global $CFG, $DB;

    require_once($CFG->dirroot . '/message/lib.php');

    $eventdata = new core\message\message();

    $eventdata->modulename = 'block_exaplan';
    $eventdata->userfrom = $userfrom;
    $eventdata->userto = $userto;
    $eventdata->fullmessage = $message;
    $eventdata->name = $notificationtype;
    $eventdata->subject = $subject;
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml = $message;
    $eventdata->smallmessage = $subject;
    $eventdata->component = 'block_exaplan';
    $eventdata->notification = 1;
    $eventdata->contexturl = $contexturl;
    $eventdata->contexturlname = $context;
    $eventdata->courseid = $courseid;
    $eventdata->customdata = $customdata;    // version must be 3.7 or higher, otherwise this field does not yet exist

    message_send($eventdata);
}

/**
 * @param int $modulepartId
 * @param string $date
 * @param int $timeslot
 * @return array
 */
function block_exaplan_get_admindata_for_modulepartid_and_date($modulepartId, $date, $timeslot = null)
{

    $dates1 = getFixedDates(null, $modulepartId, $date, $timeslot);
    $dates2 = getDesiredDates(null, $modulepartId, $date, $timeslot);
    $dates = array_merge($dates1, $dates2);

    foreach ($dates as $k => $dateData) {
        $pUserData = getTableData('mdl_block_exaplanpusers', $dateData['relatedUserId']);
        $dates[$k]['pUserData'] = $pUserData;
    }

    return $dates;
}

function block_exaplan_get_users_from_cohort($cohortidnumber = "SW_Trainer")
{
    global $DB;

    $sql = 'SELECT u.*
              FROM {cohort} c
              JOIN {cohort_members} cm ON c.id = cm.cohortid
              JOIN {user} u ON cm.userid=u.id
              WHERE c.idnumber = "' . $cohortidnumber . '" AND c.visible = 1';
    return $DB->get_records_sql($sql);
}

function block_exaplan_get_user_regioncohort($userid)
{
    global $DB;
    $sql = 'SELECT u.id,c.idnumber
              FROM {cohort} c
              JOIN {cohort_members} cm ON c.id = cm.cohortid
              JOIN {user} u ON cm.userid=u.id
              WHERE (c.idnumber = "RegionOst" OR c.idnumber = "RegionWest") AND c.visible = 1 AND cm.userid=?';

    if ($records = $DB->get_records_sql($sql,array($userid))) {
        foreach ($records as $record) {
            return $record->idnumber;
            break;
        }

    } else {
        return "";
    }
}

function block_exaplan_create_plannotification($puseridfrom = null, $puseridto = null, $notificationtext = "")
{
    global $DB;

    $plannotification = new stdClass();
    $plannotification->puseridfrom = $puseridfrom;
    $plannotification->puseridto = $puseridto;
    $plannotification->notificationtext = $notificationtext;
    $plannotification->moodlenotificationcreated = 0;

    return $DB->insert_record(BLOCK_EXAPLAN_DB_PLANNOTIFICATIONS, $plannotification);
}

function block_exaplan_get_current_user(){
	global $saltuserstring;
	$userid = optional_param("userid", 0, PARAM_INT);
	if ($userid>0){
		$pagehash=optional_param("pagehash", 0, PARAM_ALPHANUMEXT);
		if (md5($userid."_".$saltuserstring)==$pagehash) return $userid;
		else return 0;
	}else{
		return 0;
	}
}

function block_exaplan_hash_current_userid($userid){
		global $saltuserstring;
		return md5($userid."_".$saltuserstring);
}



