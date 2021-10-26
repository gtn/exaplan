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
                $content .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$part["id"].'" role="button" class="btn btn-danger"> Anfragen </a>';
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
 * @param int $modulepartId
 * @return string
 */
function block_exaplan_calendars_view($userid, $monthsCount = 2, $withHeader = false, $modulepartId = null) {
    $isAdmin = block_exaplan_is_admin();
    $content = '<div id="block_exaplan_dashboard_calendar">';

    if ($userid) {
        // for students
        $calendarAjaxUrl = new moodle_url('/blocks/exaplan/ajax.php',
            array('action' => 'addUserDisiredDate',
                'sesskey' => sesskey(),
            )
        );
        $content .= '<script>var calendarAjaxUrl = "'.html_entity_decode($calendarAjaxUrl).'";</script>';
        $content .= '<script>var calendarData = ' . block_exaplan_get_data_for_calendar(getPuser($userid)['id'], 'all', null) . ';</script>';
    } else {
        if ($isAdmin) {
            // for adminview
            $content .= '<script>var isExaplanAdmin = true;</script>';
            $calendarAjaxUrl = new moodle_url('/blocks/exaplan/ajax.php',
                array('action' => 'adminViewModulepartDate',
                    'mpid' => $modulepartId,
                    'sesskey' => sesskey(),
                )
            );
            $content .= '<script>var calendarAjaxUrl = "'.html_entity_decode($calendarAjaxUrl).'";</script>';
            if ($modulepartId) {
//                $content .= '<script>var calendarsFrozen = true; </script>';
                $content .= '<script>var calendarData = ' . block_exaplan_get_data_for_calendar(null, 'all', $modulepartId) . ';</script>';
            }
        }
    }
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

/**
 * @param int $modulepartid
 * @param string $date
 */
function printAdminModulepartView($modulepartid, $date = '') {
    $content = '';
    $content .= '<div class="adminModuleplanView">';
    $content .= '<table class="moduleplanView-header">';
    $moduleSetId = getTableData(BLOCK_EXAPLAN_DB_MODULEPARTS, $modulepartid, 'modulesetid');
    // header
    $content .= '<tr>';
    $content .= '<td width="25%" valign="top">'.getTableData(BLOCK_EXAPLAN_DB_MODULEPARTS, $modulepartid, 'title');
    $location = getTableData(BLOCK_EXAPLAN_DB_MODULESETS, $moduleSetId, 'location');
    if ($location) {
        $content .= '&nbsp;|&nbsp;' . $location;
    }
    $content .= '</td>';
    $content .= '<td width="25%" valign="top">Gesamt Teilnehmer angefragt: </td>';
    $content .= '<td width="25%" valign="top">Rest:</td>';
    $content .= '</tr>';
    $content .= '</table>';
    // calendars
    $content .= block_exaplan_calendars_view(0, 4, false, $modulepartid);
    // day ajax reloaded data (just HTML container)
    $content .= '<div id="modulepart-date-data"></div>';


    $content .= '</div>';

    return $content;

}

/**
 * @param int $modulepartId
 * @param string $date
 * @return string
 */
function modulepartAdminViewByDate($modulepartId, $date) {
    global $CFG;
    $content = '';

//    $actionUrl = $CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$modulepartId.'&date='.$date.'&timeslot=';
//    $content .= '<form class="small" action="'.$actionUrl.BLOCK_EXAPLAN_MIDDATE_BEFORE.'" name="form'.$modulepartId.'1"></form>'; // needed for valid HTML. Be careful with jQuery of this form!
//    $content .= '<form class="small" action="'.$actionUrl.BLOCK_EXAPLAN_MIDDATE_AFTER.'" name="form'.$modulepartId.'2"></form>'; // needed for valid HTML. Be careful with jQuery of this form!
//    $content .= '<form class="small" action="'.$actionUrl.BLOCK_EXAPLAN_MIDDATE_ALL.'" name="form'.$modulepartId.'3"></form>'; // needed for valid HTML. Be careful with jQuery of this form!

    $tableStartTemplate = '<table class="table table-sm exaplan-adminModulepartView">';

    $content .= $tableStartTemplate;
    // header
    $content .= '<thead class="thead-light">';
    $content .= '<tr>';
    $content .= '<th>Angefragte TN: '.$date.'</th>';
    $content .= '<th></th>';
    $content .= '<th>Organization</th>';
    $content .= '<th>TN gefehit?</th>';
    $content .= '<th>Bewertung o.ä.?</th>';
    $content .= '<th></th>';
    $content .= '</tr>';
    $content .= '</thead>';

    $content .= '<tbody>';

    $timeslotView = function($timeslot, $title) use ($modulepartId, $date, $CFG, $tableStartTemplate) {
        $cont = '';
        $rowsCount = 0;
        $mergedData = block_exaplan_get_admindata_for_modulepartid_and_date($modulepartId, $date, $timeslot);
        if (count($mergedData) > 0) {
            $cont .= '</table>'; // we need to start new table for correct <form> working
            $actionUrl = $CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$modulepartId.'&date='.$date.'&timeslot='.$timeslot;
            $cont .= '<form class="small" action="'.$actionUrl.BLOCK_EXAPLAN_MIDDATE_BEFORE.'" method="post">';
            $cont .= '<input type="hidden" name="action" value="saveFixedDates" />';
            $cont .= $tableStartTemplate;

            $cont .= '<tr><td colspan="6"><h5 class="p-1 mb-1 bg-secondary text-dark">'.$title.'</h5></td></tr>';
            $cont .= '</tr>';
            foreach ($mergedData as $dateData) {
                $rowsCount++;
                $cont .= '<tr>';
                $cont .= '<td valign="top">'.@$dateData['pUserData']['firstname'].' '.@$dateData['pUserData']['lastname'].'</td>';
                $cont .= '<td valign="top">'./*buttons*/'</td>';
                $companyName = getTableData('mdl_block_exaplanmoodles', $dateData['pUserData']['moodleid'], 'companyname');
                $cont .= '<td valign="top">'.$companyName.'</td>';
                // fixed or desired
                $cont .= '<td valign="top">
                            <input type="checkbox" 
                                    value="1"                                     
                                    name="fixed['.$dateData['pUserData']['id'].']" 
                                    '.($dateData['dateType'] == 'fixed' ? 'checked = "checked"' : '').'/></td>';
                $cont .= '<td valign="top"><!--Bewertung--></td>';
                if ($rowsCount == 1) {
                    $cont .= '<td rowspan="###FORM_ROWSPAN###"  valign="top">'.formAdminDateFixing($modulepartId, $date, $timeslot).'</td>';
                }
                $cont .= '</tr>';
                $cont .= '</table>';
                $cont .= '</form>';
                $cont .= $tableStartTemplate;
            }
        }
        $cont = str_replace('###FORM_ROWSPAN###', $rowsCount, $cont);
        return $cont;
    };

    // before midday
    $content .= $timeslotView(BLOCK_EXAPLAN_MIDDATE_BEFORE, 'vormittags (8-12 uhr)');

    // after midday
    $content .= $timeslotView(BLOCK_EXAPLAN_MIDDATE_AFTER, 'nachmittags (13-17 Uhr)');

    // all day
    $content .= $timeslotView(BLOCK_EXAPLAN_MIDDATE_ALL, 'ganztags möglich');

    $content .= '</tbody>';
    $content .= '</table>';

    return $content;
}

function formAdminDateFixing($modulepartId, $date, $timeslot) {
    global $CFG;
    $content = '';
    $dateTs = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0)->getTimestamp();
    $instanceKey = $modulepartId.'_'.$dateTs.'_'.$timeslot;

    $content .= '<table class="table table-sm table-borderless">';

    $content .= '<tr>';
    $content .= '<td colspan="2"><label for="trainer_'.$instanceKey.'">Trainer:</label></td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td>';
    $trainers = block_exaplan_get_users_from_cohort();
    $content .= '<select id="trainer_'.$instanceKey.'" class="form-control" >';
    foreach ($trainers as $trainer) {
        $content .= '<option value="'.$trainer['id'].'">'.fullname($trainer).'</option>'; // original ID (not pUser), because it is on MAIN moodle
    }
    $content .= '</select>';
    $content .= '</td>';
    $content .= '<td>';
    $content .= '</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td><label for="location_'.$instanceKey.'">Location:</label></td>';
    $content .= '<td><label for="time_'.$instanceKey.'">Uhrzeit:</label></td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td><input type="text" name="location" value="" class="form-control" id="location_'.$instanceKey.'" /></td>';
    $content .= '<td><input type="text" name="time" value="" class="form-control" id="time_'.$instanceKey.'" >';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td colspan="2">';
    $content .= '<textarea name="description" id="description_'.$instanceKey.'" class="form-control" placeholder="Notiz" ></textarea>';
    $content .= '</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td align="left"><button name="date_block" class="btn btn-info" disabled="disabled" type="submit" >Termin blocken</button></td>';
    $content .= '<td align="right"><button name="date_save" class="btn btn-success" type="submit" >Kurs fixieren</button></td>';
    $content .= '</tr>';

    $content .= '</table>';

    return $content;
}