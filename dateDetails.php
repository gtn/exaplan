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

$modulepartid = optional_param("mpid", 0, PARAM_INT);
$dateId = required_param("dateid", PARAM_INT);
$isadmin = block_exaplan_is_admin();

$userid = $USER->id;

echo $OUTPUT->header();

echo '<div id="exaplan">';

echo '<div class="UserCalendarCard">';

echo printUser($userid, $isadmin, $modulepartid, false, $dateId, true);

echo '<br>';
echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/dashboard.php" role="button" class="btn btn-info"> back to dashboard </a>';

echo '</div>';

echo '</div>';

echo $OUTPUT->footer();