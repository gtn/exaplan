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
        $PAGE->requires->jquery();
        $PAGE->requires->js("/blocks/exaplan/javascript/block_exaplan.js", true);

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
            foreach ($courses as $course){
                $enrolled = get_enrolled_users(block_exaplan_get_context_from_courseid($course->id), 'block/exaplan:student', 0, 'u.*', 'lastname');
                $students = array_merge($students, $enrolled);
            }

            // add data for grouping
            foreach ($students as $stId => $studentData) {
                $pUserId = getPuser($studentData->id)['id'];
                $students[$stId]->standort = getTableData('block_exaplanpusers', $pUserId, 'standort');
                $students[$stId]->jahrgang = getTableData('block_exaplanpusers', $pUserId, 'jahrgang');
            }
            // sort students by 'standort', 'jahrgang' for grouping later and by lastname
            // NOTE: students without value in 'standort' - will be first
            usort($students, function($s1, $s2) {
                if ($s1->standort == $s2->standort) {
                    if ($s1->jahrgang == $s2->jahrgang) {
                        return strcmp($s1->lastname, $s2->lastname);
                    }
                    return strcmp($s1->jahrgang, $s2->jahrgang);
                }
                return strcmp($s1->standort, $s2->standort);
            });

            // generate groups content
            $groups = [];
            $studentids = array();
            foreach ($students as $student){
                if (!in_array($student->id, $studentids)) {
                    $groupStandortKey = trim((string)$student->standort);
                    if (!$groupStandortKey) {
                        $groupStandortKey = 'Benutzer*innen ohne Standort';
                    }
                    $groupJahrgangKey = trim((string)$student->jahrgang);
                    if (!$groupJahrgangKey) {
                        $groupJahrgangKey = 'Benutzer*innen ohne Jahrgang';
                    }
                    if (!array_key_exists($groupStandortKey, $groups)) {
                        $groups[$groupStandortKey] = [];
                    }
                    if (!array_key_exists($groupJahrgangKey, $groups[$groupStandortKey])) {
                        $groups[$groupStandortKey][$groupJahrgangKey] = [];
                    }
                    $groups[$groupStandortKey][$groupJahrgangKey][] = printUser($student->id, $isadmin, $modulepartid, false);
                    $studentids[] = $student->id;
                }
            }
            // display groups
            $groupNumber = 1;
            $content .= '<div class="exaplan-standort-groups-container">';
            $content .= '<div class="exaplan-standort-groups-service">';
            $content .= '<a href="#" class="exaplan-standort-groups-allcollapse" data-collapsed="0">alle anzeigen|verstecken</a>';
            $content .= '</div>';
            foreach ($groups as $groupTitle => $subGroupsJahrgangData) {
                $groupStandortId = 'groupitem_'.$groupNumber;
                $groupJahrgangNumber = 1;

                // custom collapsible html
                $content .= '<div class="exaplan-standort-groupitem">';
                $content .= '<div class="exaplan-standort-title">';
                $content .= '<a href="#'.$groupStandortId.'" class="" data-toggle="collapse" aria-expanded="true" aria-controls="'.$groupStandortId.'">';
                $content .= '<h3>Standort: '.$groupTitle.'</h3><i class="fa fa-chevron-down"></i>';
                $content .= '</div>';
                $content .= '</a>';
                $content .= '<div class="collapse exaplan-standort-usersdata show" id="'.$groupStandortId.'">';
                foreach ($subGroupsJahrgangData as $groupJahrgangTitle => $students) {
//                    $content .= $studentDataContent;
                    $groupJahrgangId = 'groupitem_'.$groupNumber.'_'.$groupJahrgangNumber;
                    $content .= '<div class="exaplan-jahrgang-groupitem">';
                    $content .= '<div class="exaplan-jahrgang-title">';
                    $content .= '<a href="#'.$groupJahrgangId.'" class="collapsed" data-toggle="collapse" aria-expanded="false" aria-controls="'.$groupJahrgangId.'">';
                    $content .= '<h3>Jahrgang: '.$groupJahrgangTitle.'</h3><i class="fa fa-chevron-down"></i>';
                    $content .= '</div>';
                    $content .= '</a>';
                    $content .= '<div class="collapse exaplan-jahrgang-usersdata" id="'.$groupJahrgangId.'">';
                    foreach ($students as $studentDataContent) {
                        $content .= $studentDataContent;
                    }
                    $content .= '</div>';
                    $content .= '</div>';
                    $groupJahrgangNumber++;

                }
                $content .= '</div>';
                $content .= '</div>';
                $groupNumber++;
            }
            $content .= '</div>';

            // exacomp dashboard
            /*require_once ($CFG->dirroot . '/blocks/exacomp/renderer.php');
            $exacompOutput = new \block_exacomp_renderer($PAGE, false);
            echo get_string('choosestudent', 'block_exacomp');
            echo $output->studentselector($coursestudents, ($student) ? $student->id : @$groupidForSelector, 2, $groups);*/



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