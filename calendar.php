<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Terminplaner");
$PAGE->set_heading("Terminplaner");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/calendar.php');

require_login();



echo $OUTPUT->header();

echo '<div id="exaplan">';

echo '<div class="UserCalendarCard">';
//echo printUser(11);
echo printUser($USER->id);
echo printUser($USER->id);
echo block_exaplan_select_period_view();
echo '</div>';


echo '</div>';

echo $OUTPUT->footer();