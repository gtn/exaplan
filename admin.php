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
$isadmin = block_exaplan_is_admin();

$userid = $USER->id;

switch ($action) {
    case 'saveFixedDates':
        // create 'mdl_block_exaplandates' record and relate selected students to it
        $pUserId = getPuser($userid)['id'];
        $middayType = required_param("middayType", PARAM_INT);
        $date = required_param("date", PARAM_TEXT);
        $dateTS = DateTime::createFromFormat('Y-m-d', $date)->getTimestamp();
        $students = optional_param_array('fixedPuser', [], PARAM_INT);
        $students = array_keys($students);
        $location = optional_param('location', '', PARAM_TEXT);
        $eventTime = optional_param('time', '', PARAM_TEXT);
        $description = optional_param('description', '', PARAM_TEXT);
        $trainerId = optional_param('trainer', 0, PARAM_INT);
        $pTrainer = getPuser($trainerId)['id'];
        $dateId = setPrefferedDate(true, $modulepartid, $pUserId, $dateTS, $middayType, $location, $pTrainer, $eventTime, $description);
        foreach ($students as $student) {
            addPUserToDate($dateId, $student);
            // delete desired data
            setDesiredDate($modulepartid, $student, $dateTS, $middayType);

        }
        break;
}


echo $OUTPUT->header();

echo '<div id="exaplan">';

if ($isadmin) {
    $dateGP = optional_param('date', '', PARAM_TEXT);
    echo printAdminModulepartView($modulepartid, $dateGP);
}

echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" role="button" class="btn btn-default"> back </a>';

echo '</div>';

echo $OUTPUT->footer();


