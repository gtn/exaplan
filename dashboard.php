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
$isteacher = block_exaplan_is_teacher_in_any_course();

$userid = $USER->id;

block_exaplan_init_js_css();

require_login();


echo $OUTPUT->header();

echo '<div id="exaplan">';

//getOrCreatePuser();

if ($isteacher) {
    $students = array();
    $enrolled = array();
    $courses = block_exaplan_get_courses();
    foreach( $courses as $course){
        $enrolled = get_enrolled_users(block_exaplan_get_context_from_courseid($course->id), 'block/exaplan:student' );
        $students = array_merge($students, $enrolled);
    }
    $studentids = array();
    foreach($students as $student){
        if(!in_array($student->id,$studentids)){
            echo printUser($student->id, $isadmin, $modulepartid, false);
            $studentids[] = $student->id;
        }
    }
} else if(!$modulepartid || $isadmin) {
    // only moduleparts
    echo printUser($userid, $isadmin, $modulepartid, false);

} else {
    // with calendar
    echo printUser($userid, $isadmin, $modulepartid, true);
}

echo '</div>';

echo $OUTPUT->footer();
