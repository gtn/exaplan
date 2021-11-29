<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;

$content = '';

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Terminplaner");
$PAGE->set_heading("Terminplaner");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/calendar.php');

block_exaplan_init_js_css();

require_login();

$modulepartid = optional_param("mpid", 0, PARAM_INT);
$dateId = required_param("dateid", PARAM_INT);
$isadmin = block_exaplan_is_admin();

$userid = block_exaplan_get_current_user();

$content .= $OUTPUT->header();

$content .= '<div id="exaplan">';

$content .= '<div class="UserCalendarCard">';

$redirectToCalendar = false;
$dateDetailsExisting = false;

// details of modulepart.
$fixedDatesExisting = getFixedDatesAdvanced(getPuser($userid)['id'], $modulepartid);
if ($modulepartid && $fixedDatesExisting) {
    foreach($fixedDatesExisting as $dateData) {
        if ($dateId == $dateData['id']) {
            $dateDetailsExisting = true;
            break;
        }
    }
}

if (!$dateDetailsExisting) {
    // If the user is seeing details of date in modulepart and in this time admin disable/activate his to some other modulepart - current links are wrong. So - redirect the student to correct link.
    $url = new moodle_url('/blocks/exaplan/calendar.php', array('mpid' => $modulepartid, 'userid' => $userid, 'pagehash' => block_exaplan_hash_current_userid($userid)));
    redirect($url, 'You have not fixed dates');
}

$content .= printUser($userid, $isadmin, $modulepartid, false, $dateId, true);

$content .= '<br>';
$content .= '<a href="'.$CFG->wwwroot.'/my/" role="button" class="btn btn-info"> zurück zum Dashboard </a>';

$content .= '</div>';

$content .= '</div>';

$content .= $OUTPUT->footer();

echo $content;
