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

var_dump(getOrCreatePuser($USER->id));
$modulesets = getModulesOfUser(11);


echo '<div id="exaplan">';
echo '<table>';
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
        echo '<td>';
        echo '<ul>';
        foreach($part['dates'] as $date){
            echo '<li>'.$date['date'].'</li>';
        }
        echo '</ul>';
        echo '</td>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';

echo $OUTPUT->footer();
