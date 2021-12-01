<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Terminplaner");
$PAGE->set_heading("Terminplaner");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/admin.php');

block_exaplan_init_js_css();

require_login();

$action = optional_param("action", "", PARAM_TEXT);
$modulepartid = optional_param("mpid", "", PARAM_INT);
$region = optional_param("region", "", PARAM_TEXT);
$dashboardType = optional_param("dashboardType", '', PARAM_TEXT);
$dateId = optional_param("dateId", 0, PARAM_INT);

$isadmin = block_exaplan_is_admin();

$userid = $USER->id;

switch ($action) {
    case 'saveFixedDates':
        // create 'mdl_block_exaplandates' record and relate selected students to it
        $pUserId = getPuser($userid)['id'];
//        $middayType1 = optional_param_array("middayType".BLOCK_EXAPLAN_MIDDATE_BEFORE, [], PARAM_INT);
//        $middayType1 = array_keys($middayType1);
//        $middayType2 = optional_param_array("middayType".BLOCK_EXAPLAN_MIDDATE_AFTER, [], PARAM_INT);
//        $middayType2 = array_keys($middayType2);
        $date = required_param("date", PARAM_TEXT);
        $dateTS = DateTime::createFromFormat('Y-m-d', $date)->getTimestamp();
        $dateTSstart = strtotime("today", $dateTS); //same tstamp for whole day
        $students = optional_param_array('fixedPuser', [], PARAM_INT);
        $students = array_keys($students);
        $moodleid = optional_param('moodleid', '', PARAM_INT);
        $region = optional_param('region', '', PARAM_TEXT);
        $isonline = optional_param('isonline', '', PARAM_TEXT);
        $location = optional_param('location', '', PARAM_TEXT);
        $eventTime = optional_param('time', '', PARAM_TEXT);
        $duration = optional_param('duration', '', PARAM_TEXT);
        $description = optional_param('description', '', PARAM_TEXT);
//        $trainerId = optional_param('trainer', 0, PARAM_INT);
//        $pTrainer = getPuser($trainerId)['id'];
        $pTrainer = optional_param('trainer', 0, PARAM_INT);
        $region = optional_param('region', 'all', PARAM_TEXT);

        // fixed date or blocked date
        $state = BLOCK_EXAPLAN_DATE_PROPOSED; // never accessible?
        $sendNotificationToStudent = true;
        if (optional_param('date_save', '', PARAM_TEXT)) {
            $state = BLOCK_EXAPLAN_DATE_CONFIRMED;
        }
        if (optional_param('date_block', '', PARAM_TEXT)) {
            $state = BLOCK_EXAPLAN_DATE_BLOCKED;
            $sendNotificationToStudent = false;
        }


        $modulepart = getModulepartByModulepartid($modulepartid);
        $moduleset = getModulesetByModulesetid($modulepart["modulesetid"]);
        $absents = optional_param_array('absentPuser', [], PARAM_INT);
        $absents = array_keys($absents);

        // get timeslot for fixed date
        // get from selected (1, 2 or both)
        // selected types are from database. not from html form
        $useFormMiddateTypes = false;
        $middayTypes = [];
        foreach ($students as $student) {
            $desiredDate = getDesiredDates($student, $modulepartid, $dateTSstart);
            if ($middayTypes) { // if this user already has not desired dates for this modulepart - it is fix date editing.
                $useFormMiddateTypes = true;
                $middayTypes[] = $desiredDate[0]['timeslot'];
            }
        }
        if ($useFormMiddateTypes) {
            // found at least one timeslot
            $middayTypes = array_unique($middayTypes);
            if (!$middayTypes || count($middayTypes) > 1) {
                $middayType = BLOCK_EXAPLAN_MIDDATE_ALL;
            } else {
                $middayType = reset($middayTypes);
            }
        } else {
            // get from existing date
            $tempDate = getPrefferedDate($modulepartid, $dateTS);
            if ($tempDate) {
                // use existing timeslot
                $middayType = $tempDate['timeslot'];
            } else {
                // new date. use default
                $middayType = BLOCK_EXAPLAN_MIDDATE_ALL;
            }
        }

        $dateId = setPrefferedDate(true, $modulepartid, $pUserId, $dateTS, $middayType, $location, $pTrainer, $eventTime, $description, $region, $moodleid, $isonline, $duration, $state);

        // register / unregister students
        $registeredUsers = getFixedPUsersForDate($dateId);
        $registeredUsersIds = array_map(function($u) {return $u['puserid'];}, $registeredUsers);
        foreach ($students as $student) {
            if (($key = array_search($student, $registeredUsersIds)) !== false) {
                unset($registeredUsersIds[$key]);
            }
            $absent = 0;
            if (in_array($student, $absents)) {
                $absent = 1;
            }
            addPUserToDate($dateId, $student, $absent, $pUserId, $date, $moduleset, $modulepart, true, $sendNotificationToStudent);
            // delete ALL other desired dates
            if ($state != BLOCK_EXAPLAN_DATE_BLOCKED) {
                removeDesiredDate($modulepartid, $student);
            }
        }
        // unregister if it was unchecked
        if ($registeredUsersIds && count($registeredUsersIds) > 0) {
            foreach ($registeredUsersIds as $puserid) {
                removePUserFromDate($dateId, $puserid, $modulepartid);
            }
        }
        break;
}


echo $OUTPUT->header();

echo '<div id="exaplan">';

if ($isadmin) {
    $dateGP = optional_param('date', '', PARAM_TEXT);
    echo printAdminModulepartView($modulepartid, $dateGP, $region, $dateId);
}

echo '<a href="'.$CFG->wwwroot.'/my/'.($dashboardType ? '?dashboardType='.$dashboardType : '').'" role="button" class="btn btn-info"> zurück zum Dashboard </a>';

echo '</div>';

echo $OUTPUT->footer();
