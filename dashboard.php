<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;



$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Übersicht");
$PAGE->set_heading("Übersicht");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/dashboard.php');

$modulepartid = optional_param("mpid", 0, PARAM_INT);
$isadmin = block_exaplan_is_admin();

$userid = $USER->id;

block_exaplan_init_js_css();

require_login();


echo $OUTPUT->header();

echo '<div id="exaplan">';

//getOrCreatePuser();

if (!$modulepartid || $isadmin) {
    // only moduleparts
    echo printUser($userid, $isadmin, $modulepartid, false);
} else {
    // with calendar
    echo printUser($userid, $isadmin, $modulepartid, true);
}

echo '</div>';

echo $OUTPUT->footer();
