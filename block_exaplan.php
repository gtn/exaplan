<?php
require __DIR__.'/inc.php';

class block_exaplan extends block_base {
    public function init() {
        $this->title = get_string('exaplan', 'block_exaplan');
    }

    public function get_content() {

        global $CFG, $PAGE, $OUTPUT, $USER;
        

//        $PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/dashboard.php');
        $PAGE->requires->css('/blocks/exaplan/css/block_exaplan.css', true);

        $modulepartid = optional_param("mpid", 0, PARAM_INT);
        $dashboardType = optional_param("dashboardType", BLOCK_EXAPLAN_DASHBOARD_DEFAULT, PARAM_TEXT);
        $isadmin = block_exaplan_is_admin();
        $isteacher = block_exaplan_is_teacher_in_any_course();

        $userid = $USER->id;

        require_login();

        $content="";
        //$content.=  $OUTPUT->header();
        $content.= '<div id="exaplan">';

        if ($isteacher && !$isadmin) {
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
                    $content.= printUser($student->id, $isadmin, $modulepartid, false);
                    $studentids[] = $student->id;
                }
            }
        } else if(!$modulepartid || $isadmin) {
            // only moduleparts
            if ($isadmin) {
                $content .= printAdminDashboard($dashboardType);
            } else {
                $content .= printUser($userid, $isadmin, $modulepartid, false);
            }

        } else {
            // with calendar
            $content.= printUser($userid, $isadmin, $modulepartid, true);
        }

        $content.= '</div>';

        //$content.= $OUTPUT->footer();

        //echo $content;
        
        
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content         =  new stdClass;
        $this->content->items = array();
        //$this->content = '<a title="dashboard" href="'.$CFG->wwwroot.'/blocks/exaplan/dashboard.php">Dashboard</a>';
				$this->content->text = $content;
        return $this->content; 
    }

    function has_config() {return true;}
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.
}