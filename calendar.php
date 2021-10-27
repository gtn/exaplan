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
$date = optional_param("date", "", PARAM_TEXT);
$timeslot = optional_param("timeslot", 0, PARAM_INT);
$isadmin = block_exaplan_is_admin();

$userid = $USER->id;

/*if($action == "save"){
    // DO NOT WORK
    $middayType = optional_param("calMidday", 3, PARAM_INT);
    $calSelectedDates = optional_param("calSelectedDates", '', PARAM_TEXT);
    $calSelectedDates = json_decode($calSelectedDates);
    foreach ($calSelectedDates as $calDate) {
        $dateTS = DateTime::createFromFormat('Y-m-d', $calDate)->getTimestamp();
        setPrefferedDate(1, getPuser($userid)['id'], $dateTS, $middayType);
    }
}*/


echo $OUTPUT->header();

echo '<div id="exaplan">';

echo '<div class="UserCalendarCard">';

if (!$modulepartid || $isadmin) {
    // only moduleparts
    echo printUser($userid, $isadmin, $modulepartid, false);
} else {
    // with calendar
    echo printUser($userid, $isadmin, $modulepartid, true);
//    echo block_exaplan_calendars_view($userid, 2);
}

echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" role="button" class="btn btn-danger"> offen </a>';
/*echo '<form action="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" method="post">';
echo '<input name="action" value="save" />';
echo '<button type="submit" class="save_calendar-data">Klicken</button>';
echo '</form>';*/

echo '</div>';


echo '</div>';

echo $OUTPUT->footer();