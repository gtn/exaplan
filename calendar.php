<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Terminplaner");
$PAGE->set_heading("Terminplaner");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/calendar.php');

require_login();

$action = optional_param("action", "", PARAM_TEXT);
$modulepartid = optional_param("modulepartid", "", PARAM_INT);
$date = optional_param("date", "", PARAM_TEXT);
$timeslot = optional_param("timeslot", "", PARAM_INT);


if (false) { // @Fabio - or use own rule for your userid: 11 :-)
    $userid = 11;

} else {
    $userid = $USER->id;
}

if($action == "save"){
    setPrefferedDate(1, getPuser($userid)['id'], '2021-10-25', 1);
}

echo $OUTPUT->header();

echo '<div id="exaplan">';

echo '<div class="UserCalendarCard">';
echo printUser($userid);
echo block_exaplan_select_period_view();

echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" role="button" class="btn btn-danger"> offen </a>';
echo '<form action="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" method="post">';
echo '<input name="action" value="save" />';
echo '<button type="submit" >Klicken</button>';
echo '</form>';

echo '</div>';


echo '</div>';

echo $OUTPUT->footer();