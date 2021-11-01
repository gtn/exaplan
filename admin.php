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
$isadmin = block_exaplan_is_admin();

$userid = $USER->id;

switch ($action) {
    case 'saveFixedDates':
        // create 'mdl_block_exaplandates' record and relate selected students to it
        $pUserId = getPuser($userid)['id'];
        $middayType1 = optional_param_array("middayType".BLOCK_EXAPLAN_MIDDATE_BEFORE, [], PARAM_INT);
        $middayType1 = array_keys($middayType1);
        $middayType2 = optional_param_array("middayType".BLOCK_EXAPLAN_MIDDATE_AFTER, [], PARAM_INT);
        $middayType2 = array_keys($middayType2);
        $date = required_param("date", PARAM_TEXT);
        $dateTS = DateTime::createFromFormat('Y-m-d', $date)->getTimestamp();
        $students = optional_param_array('fixedPuser', [], PARAM_INT);
        $students = array_keys($students);
        $location = optional_param('location', '', PARAM_TEXT);
        $eventTime = optional_param('time', '', PARAM_TEXT);
        $description = optional_param('description', '', PARAM_TEXT);
        $trainerId = optional_param('trainer', 0, PARAM_INT);
        $pTrainer = getPuser($trainerId)['id'];
        $region = optional_param('region', 'all', PARAM_TEXT);

        $modulepart = getModulepartByModulepartid($modulepartid);
        $moduleset = getModulesetByModulesetid($modulepart["modulesetid"]);
        $absends = optional_param_array('absendPuser', [], PARAM_INT);
        $absends = array_keys($absends);

        // get timeslot for fixed date
        // get from selected (1, 2 or both)
        // TODO: !!!! may be - minimal timeslot?
        $middayTypes = [];
        foreach ($students as $student) {
            if (in_array($student, $middayType1)) {
                $middayTypes[] = BLOCK_EXAPLAN_MIDDATE_BEFORE;
            }
            if (in_array($student, $middayType2)) {
                $middayTypes[] = BLOCK_EXAPLAN_MIDDATE_AFTER;
            }
        }
        $middayTypes = array_unique($middayTypes);
        if (!$middayTypes || count($middayTypes) > 1) {
            $middayType = BLOCK_EXAPLAN_MIDDATE_ALL;
        } else {
            $middayType = reset($middayTypes);
        }

        $dateId = setPrefferedDate(true, $modulepartid, $pUserId, $dateTS, $middayType, $location, $pTrainer, $eventTime, $description, $region);

        // register / unregister students
        $registeredUsers = getFixedPUsersForDate($dateId);
        $registeredUsersIds = array_map(function($u) {return $u['puserid'];}, $registeredUsers);
        foreach ($students as $student) {
            if (($key = array_search($student, $registeredUsersIds)) !== false) {
                unset($registeredUsersIds[$key]);
            }
            $absend = 0;
            if (in_array($student, $absends)) {
                $absend = 1;
            }
            addPUserToDate($dateId, $student, $absend, $pUserId, $date, $moduleset, $modulepart, true);
            // delete ALL desired dates
            removeDesiredDate($modulepartid, $student);
        }
        // unregister if it was unchecked
        if ($registeredUsersIds && count($registeredUsersIds) > 0) {
            foreach ($registeredUsersIds as $puserid) {
                removePUserFromDate($dateId, $puserid);
            }
        }
        break;
}


echo $OUTPUT->header();

echo '<div id="exaplan">';

if ($isadmin) {
    $dateGP = optional_param('date', '', PARAM_TEXT);
    echo printAdminModulepartView($modulepartid, $dateGP, $region);
}

echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/dashboard.php" role="button" class="btn btn-info"> back to dashboard </a>';

echo '</div>';

echo $OUTPUT->footer();
