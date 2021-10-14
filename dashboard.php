<?php

require __DIR__.'/inc.php';


global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Übersicht");
$PAGE->set_heading("Übersicht");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/dashboard.php');
$PAGE->requires->css('/blocks/exaplan/css/dashboard.css');

require_login();



echo $OUTPUT->header();

//var_dump(getOrCreatePuser($USER->id));



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
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach($modulesets as $moduleset){
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
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo'<br>';


}

printUser(11);
printUser(11);
printUser(11);
echo '</div>';

echo $OUTPUT->footer();
