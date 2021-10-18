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
    $modulesets = getModulesOfUser($userid);
    $user = getPuser($userid);
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
        echo '<tr> <td>'.$moduleset->set["title"].'</td>';
        echo '<td>';
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
            if($part['date'] == null || $part['date'][0]['state'] != 2){
                echo '<button type="button" class="btn btn-danger"> offen </button>';
            } else {
                echo '<td>';
                    echo $part['date'][0]['date'];
                echo '</td>';
            }
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
if ($USER->id == 11) {
    printUser(11);
    printUser(11);
    printUser(11);
} else {
    printUser($USER->id);
    printUser($USER->id);
}
echo '</div>';

echo $OUTPUT->footer();
