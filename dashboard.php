<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Übersicht");
$PAGE->set_heading("Übersicht");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/dashboard.php');
$isadmin = block_exaplan_is_admin();

block_exaplan_init_js_css();

require_login();



echo $OUTPUT->header();

echo '<div id="exaplan">';

getOrCreatePuser();

echo printUser($USER->id, $isadmin, true);

echo '</div>';

echo $OUTPUT->footer();
