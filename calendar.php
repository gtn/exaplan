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

$action = optional_param("action", "", PARAM_TEXT);
$modulepartid = optional_param("mpid", 0, PARAM_INT);
$dateId = optional_param("dateid", 0, PARAM_INT);
$date = optional_param("date", "", PARAM_TEXT);
$timeslot = optional_param("timeslot", 0, PARAM_INT);
$isadmin = block_exaplan_is_admin();
$userid = block_exaplan_get_current_user();

switch ($action) {
    case 'registerToDate':
        if ($dateId > 0) {
            addPUserToDate($dateId, getPuser($userid)['id']);
            $url = new moodle_url('/blocks/exaplan/dateDetails.php', array('mpid' => $modulepartid, 'dateid' => $dateId, 'userid' => $userid, 'pagehash' => block_exaplan_hash_current_userid($userid)));
            redirect($url);
        }
        break;
}

$content .= $OUTPUT->header();

$content .= '<div id="exaplan">';

$content .= '<div class="UserCalendarCard">';

if (!$modulepartid || $isadmin) {
    // only moduleparts overview
    $content .= printUser($userid, $isadmin, $modulepartid, false);
} else {
    // details of modulepart.
    $fixedDatesExisting = getFixedDatesAdvanced(getPuser($userid)['id'], $modulepartid);
    if ($modulepartid && $fixedDatesExisting) {
        // If the user is seeing modulepart and in this time admin activate his to some modulepart - current links are wrong. So - redirect the student to correct link.

        // if no seleced dateId and fixedDate is 'absent' - no redirecting!
        if (!$dateId && $fixedDatesExisting[0]['absent']) {
            $content .= printUser($userid, $isadmin, $modulepartid, true);
        } else {
            $url = new moodle_url('/blocks/exaplan/dateDetails.php', array('mpid' => $modulepartid, 'userid' => $userid, 'dateid' => $fixedDatesExisting[0]['id'], 'pagehash' => block_exaplan_hash_current_userid($userid)));
            redirect($url, 'You already have fixed date');
        }
    } else {
        // overview with calendar
        $content .= printUser($userid, $isadmin, $modulepartid, true);
    }
}

$content .= '<br>';
$content .= '<a href="'.$CFG->wwwroot.'/my/" role="button" class="btn btn-info btn-to-dashboard"> zurück zum Dashboard </a>';

$content .= '</div>';

$content .= '</div>';

$content .= $OUTPUT->footer();

echo $content;