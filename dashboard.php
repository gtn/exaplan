<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Übersicht");
$PAGE->set_heading("Übersicht");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/dashboard.php');
$isadmin = block_exaplan_is_admin();
require_login();



echo $OUTPUT->header();

echo '<div id="exaplan">';

getOrCreatePuser();


// just for developing on different servers!!!
if (false) { // @Fabio - or use own rule for your userid: 11 :-)
    echo '<div>';
    echo printUser(11);
    echo '</div>';
    echo '<br>';
    echo '<div>';
    echo printUser(11);
    echo '</div>';
    echo '<br>';
    echo '<div>';
    echo printUser(11);
    echo '</div>';


} else {
    echo printUser($USER->id,$isadmin);
//    echo printUser($USER->id);
}
echo '</div>';

echo $OUTPUT->footer();
