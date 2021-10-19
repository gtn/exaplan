<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Übersicht");
$PAGE->set_heading("Übersicht");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/dashboard.php');

require_login();



echo $OUTPUT->header();

echo '<div id="exaplan">';

function printUser($userid){
    $ajaxAddUserDateUrl = new moodle_url('/blocks/exaplan/ajax.php',
        array('action' => 'addUserDate',
            'sesskey' => sesskey(),
        )
    );


    $modulesets = getModulesOfUser($userid);
    $user = getPuser($userid);
    echo '<script>var ajaxAddUserDateUrl = "'.html_entity_decode($ajaxAddUserDateUrl).'";</script>';
    echo '<script>var calendarData = '.block_exaplan_get_calendar_data(getPuser($userid)).';</script>';
    echo '<div class="UserBlock">';
    echo '<div class="BlockHeader">';
    echo '<b>'.$user["firstname"].' '.$user["lastname"].'</b>';
    echo '<button type="button" class="btn btn-outline-danger"> Planung Präsenztermine </button>';
    echo '</div>';
    echo '<div class="BlockBody">';
    echo '<table class="ModuleTable">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Meine gebuchten Module</th>';
    echo '<th>Termine</th>';
    echo '<th> !!! CALENDARS HEAD !!!</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach($modulesets as $moduleKey => $moduleset){
        echo '<tr> <td valign="top">'.$moduleset->set["title"].'</td>';
        echo '<td valign="top">';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        foreach($moduleset->parts as $part) {
            echo '<th>'.$part["title"].'</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach($moduleset->parts as $part) {
            echo '<td>';
            if ($part['date'] == null || $part['date'][0]['state'] != 2){
                echo '<button type="button" class="btn btn-danger"> offen </button>';
            } else {
                echo '<span class="exaplan-selectable-date" data-dateId="'.$part['date'][0]['id'].'">'.date('d.m.Y', $part['date'][0]['date']).'</span>';
            }
            echo '</td>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</td>';
        if ($moduleKey == 0) {
            echo '<td rowspan="' . count($modulesets) . '">' . block_exaplan_select_period_view() . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo'<br>';


}

// just for developing on different servers!!!
if ($USER->id == 11) { // @Fabio - or use own rule for your userid: 11 :-)
    printUser(11);
    printUser(11);
    printUser(11);
} else {
    printUser($USER->id);
//    printUser($USER->id);
}
echo '</div>';

echo $OUTPUT->footer();
