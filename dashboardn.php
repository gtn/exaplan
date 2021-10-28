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
echo '
<div class="exaplan-result-item">
<div class="result-item-header">
<div class="result-item-header-cnt">
	
<div class="icon">
<img src="pix/teilnehmer.svg" height="90" width="90">
</div>
<h5 class="item-header-title">Max Musterteilnehmer</h5>   
	<button type="button" class="btn btn-outline-danger">
			Planung Präsenztermine  
	</button>
	<h4>Symbols, Notifications,...etc.</h4>	
	</div>
</div>
';
//getOrCreatePuser();

if (!$modulepartid || $isadmin) {
    // only moduleparts
    echo printUser($userid, $isadmin, $modulepartid, false);
} else {
    // with calendar
    echo printUser($userid, $isadmin, $modulepartid, true);
}
echo '</div><!-- / exaplan-result-item --->';
echo '</div>';

echo $OUTPUT->footer();
