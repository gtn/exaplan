<?php
// This file is part of Exabis Planning Tool
//
// (c) 2021 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!
/**
 * Creats the overview of dates for a User
 * @return string
 */
function printUser($userid){
    global $CFG;
    $ajaxAddUserDateUrl = new moodle_url('/blocks/exaplan/ajax.php',
        array('action' => 'addUserDate',
            'sesskey' => sesskey(),
        )
    );


    $modulesets = getModulesOfUser($userid);
    $user = getPuser($userid);
    $content = '<script>var ajaxAddUserDateUrl = "'.html_entity_decode($ajaxAddUserDateUrl).'";</script>';
    $content .= '<script>var calendarData = '.block_exaplan_get_calendar_data(getPuser($userid)).';</script>';
    $content .= '<div class="UserBlock">';
    $content .= '<div class="BlockHeader">';
    $content .= '<b>'.$user["firstname"].' '.$user["lastname"].'</b>';
    $content .= '<button type="button" class="btn btn-outline-danger"> Planung Präsenztermine </button>';
    $content .= '</div>';
    $content .= '<div class="BlockBody">';
    $content .= '<table class="ModuleTable">';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th>Meine gebuchten Module</th>';
    $content .= '<th>Termine</th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody>';
    foreach($modulesets as $moduleKey => $moduleset){
        $content .= '<tr> <td valign="top">'.$moduleset->set["title"].'</td>';
        $content .= '<td valign="top">';
        $content .= '<table>';
        $content .= '<thead>';
        $content .= '<tr>';
        foreach($moduleset->parts as $part) {
            $content .= '<th>'.$part["title"].'</th>';
        }
        $content .= '</tr>';
        $content .= '</thead>';
        $content .= '<tbody>';
        foreach($moduleset->parts as $part) {
            $content .= '<td>';
            if ($part['date'] == null || $part['date'][0]['state'] != 2){
                $content .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php" role="button" class="btn btn-danger"> offen </a>';
            } else {
                $content .= '<span class="exaplan-selectable-date" data-dateId="'.$part['date'][0]['id'].'">'.date('d.m.Y', strtotime($part['date'][0]['date'])).'</span>';
            }
            $content .= '</td>';
        }
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</td>';
//        if ($moduleKey == 0) {
//            $content .= '<td rowspan="' . count($modulesets) . '">' . block_exaplan_select_period_view() . '</td>';
//        }
        $content .= '</tr>';
    }

    $content .= '</tbody>';
    $content .= '</table>';
    $content .= '</div>';
    $content .= '</div>';

    return $content;
}


/**
 * Just template of calendars
 * @return string
 */
function block_exaplan_select_period_view() {
    $content = '<div id="block_exaplan_dashboard_calendar">';
    $content .= '<table>';
    $content .= '<tr>';
    $content .= '<td width="350" valign="top"><div id="month1"></div></td>';
    $content .= '<td width="350" valign="top"><div id="month2"></div></td>';
    $content .= '</tr>';
    $content .= '</table>';
//    $content .= '<div id="month3"></div>';
    $content .= '</div>';

    return $content;
}

