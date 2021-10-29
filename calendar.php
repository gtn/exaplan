<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Terminplaner");
$PAGE->set_heading("Terminplaner");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/calendar.php');

block_exaplan_init_js_css();

require_login();

$action = optional_param("action", "", PARAM_TEXT);
$modulepartid = optional_param("mpid", 0, PARAM_INT);
$dateId = optional_param("dateid", 0, PARAM_INT);
$date = optional_param("date", "", PARAM_TEXT);
$timeslot = optional_param("timeslot", 0, PARAM_INT);
$isadmin = block_exaplan_is_admin();
$userid = optional_param("userid", 0, PARAM_INT);


switch ($action) {
    case 'registerToDate':
        if ($dateId > 0) {
            addPUserToDate($dateId, getPuser($userid)['id']);
            $url = new moodle_url('/blocks/exaplan/dateDetails.php', array('mpid' => $modulepartid, 'dateid' => $dateId));
            redirect($url, 'You were registered');
        }
        break;
}

echo $OUTPUT->header();

echo '<div id="exaplan">';

echo '<div class="UserCalendarCard">';

if (!$modulepartid || $isadmin) {
    // only moduleparts overview
    echo printUser($userid, $isadmin, $modulepartid, false);
} else {
    if ($modulepartid && $dateId) {
        // details of fixed modulepart
        // TODO: delete? moved to dateDetails.php
        echo printUser($userid, $isadmin, $modulepartid, false, $dateId, true);
    } else {
        // overview with calendar
        echo printUser($userid, $isadmin, $modulepartid, true);
    }
}

echo '<br>';
echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/dashboard.php" role="button" class="btn btn-info"> back to dashboard </a>';

echo '</div>';

echo '</div>';

echo $OUTPUT->footer();