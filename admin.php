<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Terminplaner");
$PAGE->set_heading("Terminplaner");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/admin.php');

block_exaplan_init_js_css();

require_login();

$action = optional_param("action", "", PARAM_TEXT);
$modulepartid = optional_param("mpid", "", PARAM_INT);
$isadmin = block_exaplan_is_admin();

$userid = $USER->id;


echo $OUTPUT->header();

echo '<div id="exaplan">';

if ($isadmin) {
    echo printAdminModulepartView($modulepartid);
}

echo '<a href="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" role="button" class="btn btn-default"> back </a>';

echo '</div>';

echo $OUTPUT->footer();


