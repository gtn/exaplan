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
function printUser($userid, $mode = 0, $withCalendar = false){
    global $CFG;


    if ($mode == 1) {
        $modulesets = getAllModules();
    } else{
        $modulesets = getModulesOfUser($userid);
        $user = getPuser($userid);
    }


    $content = '<div class="UserBlock">';
    $content .= '<div class="BlockHeader">';
    if ($mode == 1){

    } else {
        $content .= '<b>'.$user["firstname"].' '.$user["lastname"].'</b>';
        $content .= '<button type="button" class="btn btn-outline-danger"> Planung Präsenztermine </button>';
    }
    $content .= '</div>';
    $content .= '<div class="BlockBody">';
    $content .= '<table class="ModuleTable">';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th>Meine gebuchten Module</th>';
    $content .= '<th>Termine</th>';
    if ($withCalendar) {
        $content .= '<th>' . block_exaplan_calendars_header_view() . '</th>';
    }
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
            if ($mode == 1){
                $content .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/admin.php" role="button" class="btn btn-danger"> Anfragen </a>';
            } else {
                if ($part['date'] == null || $part['date'][0]['state'] != 2){
                    $content .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/calendar.php?mpid='.$part["id"].'" role="button" class="btn btn-danger exaplan-selectable-modulepart" data-modulepartId="'.$part['id'].'"> offen </a>';
                } else {
                    $content .= '<span class="exaplan-selectable-date" data-dateId="'.$part['date'][0]['id'].'" data-modulepartId="'.$part['id'].'">'.date('d.m.Y', strtotime($part['date'][0]['date'])).'</span>';
                }
            }

            $content .= '</td>';
        }
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</td>';
        if ($withCalendar && $moduleKey == 0) {
            $content .= '<td valign="top" rowspan="' . count($modulesets) . '">';
            if ($mode == 1) {
                $content .= '<span class="alert alert-danger">   CALENDAR FOR ADMIN IS NOT WORKING YET!!!   </span>';
            }
            $content .= block_exaplan_calendars_view($userid, 2);
            $content .= '</td>';
        }
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
 * @param int $userid
 * @param int $monthsCount
 * @param bool $withHeader
 * @return string
 */
function block_exaplan_calendars_view($userid, $monthsCount = 2, $withHeader = false) {
    $content = '<div id="block_exaplan_dashboard_calendar">';
    $ajaxAddUserDateUrl = new moodle_url('/blocks/exaplan/ajax.php',
        array('action' => 'addUserDisiredDate',
            'sesskey' => sesskey(),
        )
    );
    $content .= '<script>var ajaxAddUserDateUrl = "'.html_entity_decode($ajaxAddUserDateUrl).'";</script>';
    $content .= '<script>var calendarData = '.block_exaplan_get_calendar_data(getPuser($userid)).';</script>';
    $content .= '<table>';
    if ($withHeader) {
        $content .= '<tr>';
        $content .= '<td colspan="' . $monthsCount . '">';
        $content .= block_exaplan_calendars_header_view();
        $content .= '</td>';
        $content .= '</tr>';
    };
    $content .= '<tr>';
    for ($i = 1; $i <= $monthsCount; $i++) {
        $content .= '<td width="350" valign="top"><div class="calendar-month-item" id="month' . $i . '"></div></td>';
    }
    $content .= '</tr>';
    $content .= '</table>';
    $content .= '</div>';

    return $content;
}

/**
 * Just template of calendars
 * @param int $userid
 * @param int $monthsCount
 * @return string
 */
function block_exaplan_calendars_header_view() {
    $content = '';
    $content .= '<div class="calendar_options">';
    $content .= '<h4>Sie planen: MODULENAME | PARTNAME</h4>';
    $content .= '<div class="midday-type">
                    <label class="midday-type-radio">
                        <input type="radio" name="midday_type" value="' . BLOCK_EXAPLAN_MIDDATE_BEFORE . '"> vormittags (8-12 Uhr)
                    </label>
                    <label class="midday-type-radio">
                        <input type="radio" name="midday_type" value="' . BLOCK_EXAPLAN_MIDDATE_AFTER . '"> nachmittags (13-17 Uhr)
                    </label>
                    <label class="midday-type-radio">
                        <input type="radio" name="midday_type" value="' . BLOCK_EXAPLAN_MIDDATE_ALL . '"> ganztags möglich
                    </label>  
                </div>';
    $content .= '<p>Bitte markieren Sie im Kalender jeweils Ihren Wunschzeitraum</p>';
    $content .= '</div>';
    return $content;
}
