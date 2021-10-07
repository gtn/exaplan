<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT;


$PAGE->set_pagelayout('admin');
$PAGE->set_title("Übersicht");
$PAGE->set_heading("Übersicht");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/dashboard.php');

echo $OUTPUT->header();

echo '<div id="exaplan">';
echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th>Meine gebuchten Module</th>';
echo '<th>Termine</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';
    echo '<tr> <td>Module2</td> <td>26.05.2021</td></tr>';
echo '</tbody>';
echo '</table>';
echo '</div>';

echo $OUTPUT->footer();
