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
    $middayType = optional_param("calMidday", 3, PARAM_INT);
    $calSelectedDates = optional_param("calSelectedDates", '', PARAM_TEXT);
    $calSelectedDates = json_decode($calSelectedDates);
    foreach ($calSelectedDates as $calDate) {
        $dateTS = DateTime::createFromFormat('Y-m-d', $calDate)->getTimestamp();
        setPrefferedDate(1, getPuser($userid)['id'], $dateTS, $middayType);
    }
}

$ajaxAddUserDateUrl = new moodle_url('/blocks/exaplan/ajax.php',
    array('action' => 'addUserDate',
        'sesskey' => sesskey(),
    )
);



echo $OUTPUT->header();

echo '<script>var ajaxAddUserDateUrl = "'.html_entity_decode($ajaxAddUserDateUrl).'";</script>';
echo '<script>var calendarData = '.block_exaplan_get_calendar_data(getPuser($userid)).';</script>';


echo '<div id="exaplan">';

echo '<div class="UserCalendarCard">';
echo printUser($userid, 1);
echo block_exaplan_select_period_view();

echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" role="button" class="btn btn-danger"> offen </a>';
echo '<form action="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" method="post">';
echo '<input name="action" value="save" />';
echo '<button type="submit" class="save_calendar-data">Klicken</button>';
echo '</form>';

echo '</div>';


echo '</div>';

echo $OUTPUT->footer();