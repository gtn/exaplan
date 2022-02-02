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
function printUser($userid, $isadmin = 0, $modulepartid = 0, $withCalendar = false, $dateId = 0, $withDateDetails = false){
    global $CFG;


    if ($isadmin == 1) {
        $modulesets = getAllModules();
        $pUser = [];
        $tnname = '';
    } else{
        $modulesets = getModulesOfUser($userid);
        $pUser = getPuser($userid);
        $tnname = '<b>'.$pUser["firstname"].' '.$pUser["lastname"].'</b>';
    }
    $content = '
<div class="exaplan-result-item">

';



    $content.= '<div class="UserBlock">';
    $content .= '<div class="BlockHeader">';

    $content .= '</div>';
    $content .= '<div class="BlockBody">';
    $content .= '<table class="mainTable" border="0">';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th colspan="2"><div class="result-item-header result-item-header-user">
<div class="result-item-header-cnt">
	
<div class="icon">
<img style="" src="'.$CFG->wwwroot.'/blocks/exaplan/pix/teilnehmer.svg" height="50" width="50">
</div>
<h5 class="item-header-title">'.$tnname.'</h5>   
	<button type="button" class="btn btn-outline-danger">
			<!--Planung Präsenztermine  -->
	</button>
	<h4><!--Symbols, Notifications,...etc.--></h4>	
	</div>
</div></th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td valign="top">';

    $content .= '<table class="moduleListTable" border="0">';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th>Meine Module</th>';
    $content .= '<th>Termine</th>';
    $content .= '<th></th>';

    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody>';

    foreach ($modulesets as $moduleKey => $moduleset){
        $content .= '<tr> <td valign="top">'.$moduleset->set["title"].'</td>';
        $content .= '<td valign="top">';
        $content .= '<table  class="tbl_modulparts">';
        $content .= '<thead>';
        $content .= '<tr>';
        foreach($moduleset->parts as $part) {
            $content .= '<td>'.$part["title"].'</td>';
        }
        $content .= '</tr>';
        $content .= '</thead>';
        $content .= '<tbody>';
        foreach ($moduleset->parts as $part) {
            $absentHas = false;
            if ($isadmin == 1) {
                $content .= '<td>';
                // for admins
                $desiredDates = getDesiredDates(null, $part['id']);

                $buttonClass = '';
                if (count($desiredDates) > 0) {
                    $title = count($desiredDates).' Anfragen';
                    $buttonClass .= ' exaplan-date-desired ';
                } else {
                    $title = ' - - ';
                    $buttonClass .= ' exaplan-date-no-desired-admin ';
                }
                $content .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$part["id"].'" 
                                role="button" 
                                data-modulepartId="'.$part['id'].'"
                                class="btn exaplan-admin-modulepart-button '.$buttonClass.'"
                            > '.$title.' </a>';
                $content .= '</td>';
            } else {
                if (!$part['date']  // no any date yet
                    || !in_array($part['date'][0]['state'], [BLOCK_EXAPLAN_DATE_FIXED, BLOCK_EXAPLAN_DATE_BLOCKED]) // only desired dates
                ) {
                    $content .= '<td>';
                    // desired dates
                    $disabled = '';
                    if (getDesiredDates($pUser['id'], $part['id'], null, null, null, 'future')) {
                        $buttonTitle = 'in Planung';
                        $buttonClass = ' exaplan-date-desired ';
                        $innerButtonClass = ' btn btn-desired btn-student ';
                        if ($modulepartid == $part["id"]) {
                            $buttonClass .= ' exaplan-date-current-modulepart ';
                        }
                    } else {
                        $buttonTitle = 'offen';
                        $buttonClass = '';
                        $innerButtonClass = ' btn btn-danger btn-student btn-red';
                        if ($modulepartid == $part["id"]) {
                            $buttonClass .= ' exaplan-date-current-modulepart ';
                        }
                    }

                    $dateUrl = $CFG->wwwroot.'/blocks/exaplan/calendar.php?mpid='.$part["id"].'&userid='.$userid.'&pagehash='.block_exaplan_hash_current_userid($userid);
                    if ($moduleset->set["nodesireddates"]) {
                        // disable possibility to select desired dates (if the student already has or not)
                        $buttonTitle = ' - - ';
                        $buttonClass = ' exaplan-date-nodesireddates btn-student btn-red';
                        $innerButtonClass .= ' btn-off';
                        $dateUrl = '#';
                        $disabled = ' onClick="return false;" ';
                    }

                    $content .= '<a href="'.$dateUrl.'" 
                                    role="button" 
                                    class="btn exaplan-selectable-modulepart '.$buttonClass.' '.$innerButtonClass.'"                                     
                                    data-modulepartId="'.$part['id'].'"
                                    '.($modulepartid == $part["id"] ? 'data-modulepartselected="1"' : '').'
                                '.$disabled.'>'.$buttonTitle.'</a>';
                    $content .= '</td>';
                } else {
                    // fixed date exists
                    $datesForUser = getFixedDatesAdvanced($pUser['id'], $part['id']);

                    foreach ($datesForUser as $dateTemp) {
                        $content .= '<td>';
                        $buttonClass = '';
                        if ($dateId == $dateTemp['id'] && $modulepartid == $part["id"]) {
                            $buttonClass .= ' exaplan-date-current-modulepart ';
                        }
                        // 'absent' date
                        if (@$dateTemp['absent']) {
                            $absentHas = true;
                            $buttonClass .= ' exaplan-date-absent ';
                        }
                        $content .= '<a href="' . $CFG->wwwroot . '/blocks/exaplan/dateDetails.php?mpid=' . $part["id"] . '&userid=' . $userid . '&dateid=' . $dateTemp['id'] . '&pagehash=' . block_exaplan_hash_current_userid($userid) . '"
                                    class="btn exaplan-date-fixed exaplan-selectable-date ' . $buttonClass . '" 
                                    data-dateId="' . $dateTemp['id'] . '" 
                                    data-modulepartId="' . $part['id'] . '">
                                 ' . date('d.m.Y', $dateTemp['date']) .
                            '</a>';
                        $content .= '</td>';
                    }
                }
            }
            // add possibility to add new desired dates if the student has absent fixed date
            if ($absentHas) {
                $content .= '<td>';
                $dateUrl = $CFG->wwwroot.'/blocks/exaplan/calendar.php?mpid='.$part["id"].'&userid='.$userid.'&pagehash='.block_exaplan_hash_current_userid($userid);
                $content .= '<a href="'.$dateUrl.'" 
                                    role="button" 
                                    class="btn exaplan-selectable-modulepart exaplan-date-nodesireddates '.(!$dateId && $modulepartid == $part["id"] ? 'exaplan-date-current-modulepart' : '').'"                                     
                                    data-modulepartId="'.$part['id'].'"
                                    '.($modulepartid == $part["id"] ? 'data-modulepartselected="1"' : '').'
                                > <button type="button" class="btn btn-danger">neu planen</button> </a>';
                $content .= '</td>';
            }
        }
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</td>';
        $content .= '</tr>';
    }
    $content .= '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>'; // empty row for correct heights of module cells

    $content .= '</tbody>';
    $content .= '</table>';

    $content .= '</td>';

    if ($withCalendar) {
        $content .= '<td valign="top" >';
        $content .= block_exaplan_calendars_view($userid, 2, true, $modulepartid);
        $content .= '</td>';
    }
    if ($withDateDetails) {
        $content .= '<td valign="top" >';
        $content .= studentEventDetailsView($userid, $modulepartid, $dateId);
        $content .= '</td>';
    }

    if (!$withCalendar && !$withDateDetails) {
        $content .= '<td valign="top">';
        $content .= printStudentExistingFixedDates($pUser['id']);
        $content .= '</td>';
    }

    $content .= '</td>';
    $content .= '</tr>';
    $content .= '</tbody>';
    $content .= '</table>';

    $content .= '</div>';
    $content .= '</div>';
    $content .= '</div><!-- / exaplan-result-item --->';
    return $content;
}


/**
 * Just template of calendars
 * @param int $userid
 * @param int $monthsCount
 * @param bool $withHeader
 * @param int $modulepartId
 * @param string $region
 * @return string
 */
function block_exaplan_calendars_view($userid, $monthsCount = 2, $withHeader = false, $modulepartId = null, $region = '') {
    static $preloadinatorHtml = null;
    $content = '';
    if ($preloadinatorHtml === null) {
//        $preloadinatorHtml = '<div class="dot-windmill" id="spinner" style="1111display: none;"></div>';
        /*        $preloadinatorHtml = '<div class="preloader js-preloader flex-center">
                                          <div class="dots">
                                            <div class="dot"></div>
                                            <div class="dot"></div>
                                            <div class="dot"></div>
                                          </div>
                                        </div>'*/;
    }

    $isAdmin = block_exaplan_is_admin();

    $content .= '<div id="block_exaplan_dashboard_calendar">';
    $content .= $preloadinatorHtml;

    if ($userid) {
        // for students
        $content .= '<script>var isExaplanAdmin = false;</script>';
        $calendarAjaxUrl = new moodle_url('/blocks/exaplan/ajax.php',
            array('action' => 'addUserDisiredDate',
                'userid' => $userid,
                'pagehash' => block_exaplan_hash_current_userid($userid),
                'sesskey' => sesskey(),
            )
        );
        $content .= '<script>var calendarAjaxUrl = "'.html_entity_decode($calendarAjaxUrl).'";</script>';
        $content .= '<script>var calendarData = ' . block_exaplan_get_data_for_calendar(getPuser($userid)['id'], 'all', $modulepartId) . ';</script>';
    } else {
        if ($isAdmin) {
            // for adminview
            $content .= '<script>var isExaplanAdmin = true;</script>';
            $urlParams = array('action' => 'adminViewModulepartDate',
                'mpid' => $modulepartId,
                'sesskey' => sesskey(),
            );
            if ($region) {
                $urlParams['region'] = $region;
            }
            if ($dashboardType = optional_param('dashboardType', '', PARAM_TEXT)) {
                $urlParams['dashboardType'] = $dashboardType;
            }
            $calendarAjaxUrl = new moodle_url('/blocks/exaplan/ajax.php', $urlParams);
            $content .= '<script>var calendarAjaxUrl = "'.html_entity_decode($calendarAjaxUrl).'";</script>';
            if ($modulepartId) {
//                $content .= '<script>var calendarsFrozen = true; </script>';
                $content .= '<script>var calendarData = ' . block_exaplan_get_data_for_calendar(null, 'all', $modulepartId, false, $region) . ';</script>';
            }
        }
    }
    if ($modulepartId) {
        $content .= '<script>var currentModulepartId = "'.$modulepartId.'";</script>';
    }

    $content .= '<table>';
    if ($withHeader) {
        $content .= '<tr>';
        $content .= '<td colspan="' . $monthsCount . '">';
        $content .= block_exaplan_calendars_header_view($modulepartId);
        $content .= '</td>';
        $content .= '</tr>';
    };
    $content .= '<tr>';
    for ($i = 1; $i <= $monthsCount; $i++) {
        $content .= '<td valign="top" class="exaplan-calendarContainer"><div class="calendar-month-item" id="month' . $i . '"></div></td>';
    }
    $content .= '</tr>';
    $content .= '</table>';
    $content .= '</div>';

    return $content;
}

/**
 * Just template of header of calendar
 * @param int $modulepartId
 * @return string
 */
function block_exaplan_calendars_header_view($modulepartId = 0) {
    global $CFG;

    $userid = block_exaplan_get_current_user();

    $content = '';
    $content .= '<div class="calendar_options">';
    $modulePart = getTableData('mdl_block_exaplanmoduleparts', $modulepartId);
    $modulepartName = $modulePart['title'];
    $moduleName = getTableData('mdl_block_exaplanmodulesets', $modulePart['modulesetid'], 'title');
    $existingDates = getFixedDatesAdvanced(null, $modulepartId, null, null, true, '', 'future', [BLOCK_EXAPLAN_DATE_DESIRED, BLOCK_EXAPLAN_DATE_FIXED]);
    $content .= '<h4>Sie planen: '.$moduleName.' | '.$modulepartName.'</h4>';
    if ($existingDates) {
        $usersMoodle = getPuser($userid)['moodleid'];
        $content .= '<div class="register-existing-dates">';
        $tooltips = '<div class="tooltip_templates">';
        $content .= '<table class="table table-sm table-borderless">';
        $reallyUsedDates = 0; // foreach can show not all dates, so calc them
        foreach ($existingDates as $dKey => $date) {
            // if the date is related to "moodle" - show it only this moodle is the same as users moodle
            if ($usersMoodle && $date['moodleid'] && $usersMoodle != $date['moodleid']) {
                continue;
            }
            $reallyUsedDates++;
            $content .= '<tr>';
            $content .= '<td>';
            if ($dKey === 0) {
                $content .= 'Termine zur Auswahl:';
            }
            $content .= '</td>';
            $trainer = getTableData('mdl_block_exaplanpusers', $date['trainerpuserid']);
            $tooltips .= '<span id="tooltipster_content'.$date['id'].'">';
            $tooltips .= ($date['starttime'] ? 'Uhrzeit: <strong>'.date('H:i', $date['starttime']).'</strong> '.date('d.m.Y', $date['date']).'<br>' : '')
                .($date['duration'] ? 'Dauer: '.$date['duration'].'<br>' : '')
                .($date['moodleid'] ? 'Ort: '.getMoodleDataByMoodleid($date['moodleid'], 'companyname').'<br>' : '')
                .getRegionTitle($date['region']).' - '.getIsOnlineTitle($date['isonline']).'<br>'
                .($date['location'] ? 'Location: '.$date['location'].'<br>' : '')
                .($trainer ? 'Skillswork-Trainer: '.@$trainer['firstname'].' '.@$trainer['lastname'].'<br>' : '');
            $tooltips .= '</span>';
            $content .= '<td align="right"><a href="#" class="btn btn-sm exaplan-existing-date tooltipster" data-tooltip-content="#tooltipster_content'.$date['id'].'">';
            $content .= date('d.m.Y', $date['date']);
            $content .= '</a></td>';
            $url = $CFG->wwwroot.'/blocks/exaplan/calendar.php?action=registerToDate&mpid='.$modulepartId.'&dateid='.$date['id'].'&userid='.$userid.'&pagehash='.block_exaplan_hash_current_userid($userid).'';
            $confirmMessage = 'Sie buchen sich gerade im Termin "'.getFixedDateTitle($date['id']).'" am "'.date('d.m.Y', $date['date']).'" in Region "'.getRegionTitle($date['region']).'" ein. Wollen Sie das wirklich?';
            $confirmMessage = htmlentities($confirmMessage);
            $content .= '<td align="left"><a href="'.$url.'" class="btn btn-sm exaplan-register-toDate" data-confirmMessage="'.$confirmMessage.'">Termin bestätigen</a></td>';

            $content .= '</tr>';
        }
        $content .= '</table>';
        $tooltips .= '</div>';
        if ($reallyUsedDates) {
            $content .= $tooltips;
            $content .= '<strong>ODER</strong><br>';
            $content .= 'Wunschtermin wählen:';
        }
        $content .= '</div>';
    }
    if ($modulePart['duration'] != 1) {
        $content .= '<div class="midday-type">
                    <label class="midday-type-radio">
                        <input type="radio" name="midday_type" value="' . BLOCK_EXAPLAN_MIDDATE_BEFORE . '"> '.getTimeslotName(BLOCK_EXAPLAN_MIDDATE_BEFORE).'
                    </label>
                    <label class="midday-type-radio">
                        <input type="radio" name="midday_type" value="' . BLOCK_EXAPLAN_MIDDATE_AFTER . '"> '.getTimeslotName(BLOCK_EXAPLAN_MIDDATE_AFTER).'
                    </label>
                    <label class="midday-type-radio">
                        <input type="radio" name="midday_type" value="' . BLOCK_EXAPLAN_MIDDATE_ALL . '" checked="checked"> '.getTimeslotName(BLOCK_EXAPLAN_MIDDATE_ALL).'
                    </label>  
                </div>';
    }
    $content .= '<p>Bitte markieren Sie im Kalender jeweils Ihren Wunschzeitraum</p>';
    $content .= '</div>';
    return $content;
}

/**
 * @param int $modulepartid
 * @param string $date
 * @param string $region
 * @param int $selectedDateId
 */
function printAdminModulepartView($modulepartid, $date = '', $region = '', $selectedDateId = 0) {
    $content = '';
    $content .= '<div class="adminModuleplanView">';
    $content .= '<table class="moduleplanView-header">';
    $titleSet = [];
    $moduleSetId = getTableData(BLOCK_EXAPLAN_DB_MODULEPARTS, $modulepartid, 'modulesetid');
    $titleSet[] = getTableData(BLOCK_EXAPLAN_DB_MODULESETS, $moduleSetId, 'title');
    $titleSet[] = getTableData(BLOCK_EXAPLAN_DB_MODULEPARTS, $modulepartid, 'title');
    $titleSet[] = getTableData(BLOCK_EXAPLAN_DB_MODULESETS, $moduleSetId, 'location');
    $titleSet[] = getRegionTitle($region);
    // header
    $content .= '<tr>';
    $content .= '<td width="25%" valign="top">'.implode('&nbsp;|&nbsp;', $titleSet).'</td>';
    // count of students
    $desiredDates = getDesiredDates(null, $modulepartid, null, null, $region);
    if (is_array($desiredDates) && count($desiredDates) > 0) {
        // get count of unique pUsers
        $desiredDatesUsers = count(array_unique(array_column($desiredDates, 'puserid')));
    } else {
        $desiredDatesUsers = 0;
    }
    $content .= '<td width="25%" valign="top">Gesamt Teilnehmer angefragt: '.$desiredDatesUsers.'</td>';
    $content .= '<td width="25%" valign="top"></td>'; // Rest:
    $content .= '</tr>';
    $content .= '</table>';
    // calendars
    $content .= block_exaplan_calendars_view(0, 3, false, $modulepartid, $region);
    // day ajax reloaded data (just HTML container)
    $content .= '<div id="modulepart-date-data">';
    if ($date) {
        $content .= modulepartAdminViewByDate($modulepartid, $date, $region, $selectedDateId);
    }
    $content .= '</div>';

    $content .= '</div>';

    return $content;

}

/**
 * @param int $modulepartId
 * @param string $date
 * @param string $defaultRegion
 * @param int $selectedDateId
 * @return string
 */
function modulepartAdminViewByDate_OLD($modulepartId, $date, $defaultRegion = '', $selectedDateId = 0) {
    global $CFG;
    $dashboardType = optional_param('dashboardType', '', PARAM_TEXT);
    $content = '';

    $actionUrl = $CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$modulepartId.'&date='.$date.($defaultRegion ? '&region='.$defaultRegion : '').($dashboardType ? '&dashboardType='.$dashboardType : '');
    $content .= '<form class="small" action="'.$actionUrl.'" method="post" autocomplete="off">';
    $content .= '<input type="hidden" name="action" value="saveFixedDates" />';
    $content .= '<table class="table table-sm exaplan-adminModulepartView" border="0"';
    // header
    $content .= '<thead class="thead-light">';
    $content .= '<tr>';
    $content .= '<th>'.german_dateformat($date).'</th>';
    $content .= '<th></th>';
    $content .= '<th>Organization</th>';
    $content .= '<th>weitere Termine</th>';
    $content .= '<th>VM</th>';
    $content .= '<th>NM</th>';
    $content .= '<th>TN gefehlt?</th>';
    $content .= '<th><!-- existing fixed dates --></th>';
    $content .= '<th class="mainForm"></th>';
    $content .= '</tr>';
    $content .= '</thead>';

    $content .= '<tbody>';

    $states = [BLOCK_EXAPLAN_DATE_DESIRED, BLOCK_EXAPLAN_DATE_FIXED, BLOCK_EXAPLAN_DATE_BLOCKED];

    $rowsCount = 0;
    $studentFilters = [];

    // if dateId selected - use its moodle ID as a filter for desired users
    if ($selectedDateId) {
        $selectedDateData = getTableData('mdl_block_exaplandates', $selectedDateId);
        if ($selectedDateData['moodleid']) {
            $studentFilters['moodleid'] = $selectedDateData['moodleid'];
        }
    }

    $mergedData = block_exaplan_get_admindata_for_modulepartid_and_date($modulepartId, $date, null, $defaultRegion, $states);

    $studentRowFilled = false;
    $formsShown = false;
    $shownStudents = []; // is possible to have the same user for 'blocked' and 'desired' list groups
    $defaultRowHeght = 20;

    $userMidDayTypeCheckboxTemplate = function($pUserid, $userTimeSlot, $timeslotColumn) {
        $checked = '';
        if ($userTimeSlot == $timeslotColumn || $userTimeSlot == BLOCK_EXAPLAN_MIDDATE_ALL) {
            $checked = ' checked = "checked" ';
        }
        $content = '<input type = "checkbox" disabled readonly onclick="return false;"
                                value = "1"      
                                id = "middayType'.$pUserid.'_'.$timeslotColumn.'"                               
                                name = "middayType'.$timeslotColumn.'['.$pUserid.']" 
                                '.$checked.'/>&nbsp;';
        return $content;
    };

    $getListGroupTitle = function($dateTypeCode) {
        switch ($dateTypeCode) {
            case 'desired':
                return 'Angefragte TN:';
                break;
            case 'fixed':
                return 'eingeschriebene TN:';
                break;
            case 'blocked':
                return 'Blocken TN:';
                break;
            case 'theSameModulePart':
                return 'Gleicher Kurs, andere Termine gewünscht';
                break;
        }
        return '';
    };

    if (count($mergedData) > 0) {
        $currentUsersGroup = '--GROUP--';

        foreach ($mergedData as $dKey => $dateData) {
            $rowContent = '';
            $studentShown = false;

            // needs to show or not
            $studentNeedsToBeShown = false;
            if ($dateData['pUserData']
                && !in_array($dateData['pUserData']['id'], $shownStudents) // not shown yet
                && (
                    // 1. no selected dateID AND this user is Desired
                    (!$selectedDateId && $dateData['dateType'] == BLOCK_EXAPLAN_DATE_DESIRED)
                    ||
                    // 2. selected dateId AND (the user is desired OR related to this fixed date (or blocked))
                    ($selectedDateId && ($dateData['dateType'] == BLOCK_EXAPLAN_DATE_DESIRED || ($selectedDateId == $dateData['id'] && in_array($dateData['dateType'], [BLOCK_EXAPLAN_DATE_FIXED, BLOCK_EXAPLAN_DATE_BLOCKED]))))
                )
            ) {
                $studentNeedsToBeShown = true;
            }
            // additional filters, regarding selected dateId
            if ($selectedDateId && $studentFilters) {
                $studentNeedsToBeShown = false;
                if ($dateData['pUserData'] && $dateData['pUserData']['moodleid'] == $studentFilters['moodleid']) {
                    $studentNeedsToBeShown = true;
                }
            }

            // show user only in these cases:
            if ($studentNeedsToBeShown) {
                $rowsCount++;
                $shownStudents[] = $dateData['pUserData']['id'];
                $studentRowFilled = true;
                $studentShown = true;
                $rowContent .= '<tr>';
                $rowContent .= '<td valign="top" height="' . $defaultRowHeght . '">';
                // fixed or desired
                $rowContent .= '<input type="checkbox" 
                                value="1"      
                                id = "fixedUser' . $dateData['pUserData']['id'] . '"                               
                                name = "fixedPuser[' . $dateData['pUserData']['id'] . ']" 
                                ' . (in_array($dateData['dateType'], [BLOCK_EXAPLAN_DATE_FIXED, BLOCK_EXAPLAN_DATE_BLOCKED]) ? 'checked = "checked"' : '') . '/>&nbsp;';
                $rowContent .= '<label for="fixedUser' . $dateData['pUserData']['id'] . '">' . @$dateData['pUserData']['firstname'] . ' ' . @$dateData['pUserData']['lastname'] . '</label>';
                $rowContent .= '</td>';
                // buttons
                $rowContent .= '<td valign="top">' ./*buttons*/
                    '</td>';
                // organization
                $companyName = getTableData('mdl_block_exaplanmoodles', $dateData['pUserData']['moodleid'], 'companyname');
                $rowContent .= '<td valign="top">' . $companyName . '</td>';
                // count of desired dates
                $desiredDates = getDesiredDates($dateData['pUserData']['id'], $modulepartId, null, null, $defaultRegion);
                if (count($desiredDates) > 0) {

                    $desiredDatesCount = count($desiredDates) . ' Termin' . (count($desiredDates) > 1 ? 'e' : '');
                } else {
                    $desiredDatesCount = '';
                }
                $rowContent .= '<td valign="top">' . $desiredDatesCount . '</td>';
                // midDay type checkboxes
                $rowContent .= '<td valign="top" class="timeslotCheck1">';
                $rowContent .= $userMidDayTypeCheckboxTemplate($dateData['pUserData']['id'], $dateData['timeslot'], BLOCK_EXAPLAN_MIDDATE_BEFORE);
                $rowContent .= '</td>';
                $rowContent .= '<td valign="top" class="timeslotCheck2">';
                $rowContent .= $userMidDayTypeCheckboxTemplate($dateData['pUserData']['id'], $dateData['timeslot'], BLOCK_EXAPLAN_MIDDATE_AFTER);
                $rowContent .= '</td>';
                // absent or not
                $absent = '';
                if ($relationData = isPuserIsFixedForDate($dateData['pUserData']['id'], $dateData['id'], true)) {
                    if ($relationData['absent']) {
                        $absent = ' checked = "checked" ';
                    }
                }
                $rowContent .= '<td style="text-align: center;  vertical-align: top;">
                        <input type="checkbox" 
                                value="1"                                     
                                name="absentPuser[' . $dateData['pUserData']['id'] . ']" 
                                ' . $absent . '/></td>';
            }
            if ($studentShown) {
                $rowContent .= '</tr>';
            }

            // add list's group title (for desired, fixed, blocked users)
            if ($studentShown && $dateData['dateType'] != $currentUsersGroup) { // show header of student's group
                $rowsCount++;
                $oldRowContent = $rowContent;

                $rowContent = '<tr><td colspan="7" height="'.$defaultRowHeght.'" class="listGroupTitle">'.$getListGroupTitle(getDateStateCodeByIndex($dateData['dateType'])).'</td>';
                // add form - group title is always first, so it is here
                if (!$formsShown) {
                    $formsShown = true;
                    $rowContent .= '<td rowspan="###FORM_ROWSPAN###" class="fixedDatesList" valign="top">';
                    $rowContent .= buttonsForExistingDates($modulepartId, $date, $selectedDateId);
                    $rowContent .= '</td>';
                    // meta-data form
                    $rowContent .= '<td rowspan="###FORM_ROWSPAN###" class="mainForm" valign="top">'.formAdminDateFixing($modulepartId, $date, null, $defaultRegion, $selectedDateId).'</td>';
                }
                $rowContent .= '</tr>';
                $rowContent .= $oldRowContent;
                $currentUsersGroup = $dateData['dateType'];
            }

            $content .= $rowContent;
        }
    }
    if (!$studentRowFilled || !$formsShown) {
        // show empty (no students) form to create an empty date
        $rowsCount++;
        $content .= '<tr>';
        $content .= '<td height="'.$defaultRowHeght.'"></td>';
        $content .= '<td></td>';
        $content .= '<td></td>';
        $content .= '<td class="timeslotCheck1"></td>';
        $content .= '<td class="timeslotCheck1"></td>';
        $content .= '<td></td>';
        $content .= '<td></td>';
        // existing fix dates list
        $content .= '<td rowspan="###FORM_ROWSPAN###" class="fixedDatesList" valign="top">';
        $content .= buttonsForExistingDates($modulepartId, $date, $selectedDateId);
        $content .= '</td>';
        // meta-data form
        $content .= '<td rowspan="###FORM_ROWSPAN###" class="mainForm" valign="top">'.formAdminDateFixing($modulepartId, $date, null, $defaultRegion, $selectedDateId).'</td>';
        $content .= '</tr>';
    }

    // list of DESIRED users in this modulepart, but not in this date
    $additionalDesiredDates = getDesiredDates(null, $modulepartId, null, null, $defaultRegion);
    // filter by already shown students
    $additionalDesiredDates = array_filter($additionalDesiredDates, function($s) use ($shownStudents) {if (!in_array($s['relatedUserId'], $shownStudents)) return true; return false;});
    if (count($additionalDesiredDates)) {
        $rowsCount++;
        $content .= '<tr><td colspan="7" height="'.$defaultRowHeght.'" class="listGroupTitle">'.$getListGroupTitle('theSameModulePart').'</td>';
        foreach ($additionalDesiredDates as $dateData) {
            $rowContent = '';
            $setRowHight = $defaultRowHeght;

            $pUserId = $dateData['relatedUserId'];
            $pUserData = getTableData('mdl_block_exaplanpusers', $pUserId);

            // needs to show or not
            $studentNeedsToBeShown = false;
            if ($pUserId
                && !in_array($pUserId, $shownStudents) // not shown yet
            ) {
                $studentNeedsToBeShown = true;
            }
            // additional filters, regarding selected dateId
            if ($selectedDateId && $studentFilters) {
                $studentNeedsToBeShown = false;
                if ($pUserData && $pUserData['moodleid'] == $studentFilters['moodleid']) {
                    $studentNeedsToBeShown = true;
                }
            }

            if ($studentNeedsToBeShown) {
                $rowsCount++;
                $shownStudents[] = $pUserId;
                $rowContent .= '<tr>';
                $rowContent .= '<td valign="top" height="' . $setRowHight . '">';
                // fixed or desired
                $rowContent .= '<input type="checkbox" 
                                value="1"      
                                id = "fixedUser' . $pUserId . '"                               
                                name = "fixedPuser[' . $pUserId . ']" 
                                ' . (in_array($dateData['dateType'], [BLOCK_EXAPLAN_DATE_FIXED, BLOCK_EXAPLAN_DATE_BLOCKED]) ? 'checked = "checked"' : '') . '/>&nbsp;';
                $rowContent .= '<label for="fixedUser' . $pUserId . '">' . @$pUserData['firstname'] . ' ' . @$pUserData['lastname'] . '</label>';
                $rowContent .= '</td>';
                // buttons
                $rowContent .= '<td valign="top">' ./*buttons*/
                    '</td>';
                // organization
                $companyName = getTableData('mdl_block_exaplanmoodles', $pUserData['moodleid'], 'companyname');
                $rowContent .= '<td valign="top">' . $companyName . '</td>';
                // count of desired dates
                $desiredDates = getDesiredDates($pUserId, $modulepartId, null, null, $defaultRegion);
                if (count($desiredDates) > 0) {
                    $desiredDatesCount = count($desiredDates) . ' Termin' . (count($desiredDates) > 1 ? 'e' : '');
                } else {
                    $desiredDatesCount = '';
                }
                $rowContent .= '<td valign="top">' . $desiredDatesCount . '</td>';
                // midDay type checkboxes
                $rowContent .= '<td valign="top" class="timeslotCheck1">';
                $rowContent .= $userMidDayTypeCheckboxTemplate($pUserId, $dateData['timeslot'], BLOCK_EXAPLAN_MIDDATE_BEFORE);
                $rowContent .= '</td>';
                $rowContent .= '<td valign="top" class="timeslotCheck2">';
                $rowContent .= $userMidDayTypeCheckboxTemplate($pUserId, $dateData['timeslot'], BLOCK_EXAPLAN_MIDDATE_AFTER);
                $rowContent .= '</td>';
                // absent (not always)
                $absent = '';
                $rowContent .= '<td style="text-align: center;  vertical-align: top;">
                        <input type="checkbox" 
                                value="1"                                     
                                name="absentPuser[' . $pUserId . ']" 
                                ' . $absent . '/></td>';
            }
            $rowContent .= '</tr>';

            $content .= $rowContent;
        }
    }

    // special empty row (to miss row height counting)
    $content .= '<tr><td colspan="7" class="emptyRow"></td></tr>';
    $rowsCount++;

    $content .= '</table>';
    $content .= '</form>';

    $content = str_replace('###FORM_ROWSPAN###', $rowsCount, $content);


    return $content;
}

function modulepartAdminViewByDate($modulepartId, $date, $defaultRegion = '', $selectedDateId = 0) {
    global $CFG;
    $dashboardType = optional_param('dashboardType', '', PARAM_TEXT);
    $content = '';
    $rowsCount = 0;
    $usersDataColumns = 7;

    $listGroupTitle = function($listId, $title) use ($usersDataColumns, $CFG) {
        $selectAllButton = '<img data-listId="'.$listId.'" class="selectAllicon" src="'.$CFG->wwwroot.'/blocks/exaplan/pix/selectAll.png" title="select all in this list" '.($listId == 'mainList' ? 'data-selected="1"' : '').'/>';
        return '<tr><td data-listId="'.$listId.'" colspan="'.$usersDataColumns.'" height="20" class="listGroupTitle">' . $selectAllButton . ' '.$title . '</td>';
    };

    $getListGroupTitle = function($dateType) {
        switch ($dateType) {
            case 'desired':
            case BLOCK_EXAPLAN_DATE_DESIRED:
                return 'Angefragte TN:';
                break;
            case 'fixed':
            case BLOCK_EXAPLAN_DATE_FIXED:
                return 'eingeschriebene TN:';
                break;
            case 'blocked':
            case BLOCK_EXAPLAN_DATE_BLOCKED:
                return 'Blocken TN:';
                break;
            case 'theSameModulePart':
                return 'Gleicher Kurs, andere Termine gewünscht';
                break;
        }
        return '';
    };

    $actionUrl = $CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$modulepartId.'&date='.$date.($defaultRegion ? '&region='.$defaultRegion : '').($dashboardType ? '&dashboardType='.$dashboardType : '');
    $content .= '<form class="small" action="'.$actionUrl.'" method="post" autocomplete="off">';
    $content .= '<input type="hidden" name="action" value="saveFixedDates" />';
    $content .= '<table class="table table-sm exaplan-adminModulepartView" border="0"';
    // header
    $content .= '<thead class="thead-light">';
    $content .= '<tr>';
    $content .= '<th class="studentNameColumn">'.german_dateformat($date).'</th>';
    $content .= '<th class="iconButtonsColumn"></th>';
    $content .= '<th class="organizationColumn">Organization</th>';
    $content .= '<th class="desiredDatesColumn">weitere Termine</th>';
    $content .= '<th class="timeslotCheck'.BLOCK_EXAPLAN_MIDDATE_BEFORE.'">VM</th>';
    $content .= '<th class="timeslotCheck'.BLOCK_EXAPLAN_MIDDATE_AFTER.'">NM</th>';
    $content .= '<th class="absentColumn">TN gefehlt?</th>';
    $content .= '<th class="fixedDatesList"><!-- existing fixed dates --></th>';
    $content .= '<th class="mainForm"></th>';
    $content .= '</tr>';
    $content .= '</thead>';

    $content .= '<tbody>';
    // first row with forms and buttons
    $rowsCount++;
    $content .= '<tr>';
    $content .= '<td colspan="'.$usersDataColumns.'" height="5" class="emptyRow"></td>';
    // existing fix dates list
    $content .= '<td rowspan="###FORM_ROWSPAN###" class="fixedDatesList" valign="top">';
    $content .= buttonsForExistingDates($modulepartId, $date, $selectedDateId);
    $content .= '</td>';
    // meta-data form
    $content .= '<td rowspan="###FORM_ROWSPAN###" class="mainForm" valign="top">'.formAdminDateFixing($modulepartId, $date, null, $defaultRegion, $selectedDateId).'</td>';
    $content .= '</tr>';

    $filterDesiredDates = function ($desiredDates, $shownStudents, $filters = null) {
        // 1. not shown yet
        $desiredDates = array_filter($desiredDates, function($d) use ($shownStudents) {if (!in_array($d['relatedUserId'], $shownStudents)) {return true;} return false;});
        // 2. by additional filters
        if ($filters && count($filters)) {
            $filters = array_filter($filters);
            foreach ($filters as $filterName => $filterVal) {
                switch ($filterName) {
                    case 'moodleid':
                        $desiredDates = array_filter($desiredDates, function($d) use ($filterVal) {if ($d['pUserMoodleId'] == $filterVal) {return true;} return false;});
                        break;
                }
            }
        }
        // filter the result for unique students
        // some sql-result can have multiple rows. We need to have single user
        $usedStudentInThisList = [];
        $desiredDates = array_filter($desiredDates, function($d) use (&$usedStudentInThisList) {if (!in_array($d['relatedUserId'], $usedStudentInThisList)) {$usedStudentInThisList[] = $d['relatedUserId']; return true;} return false;});
        return $desiredDates;
    };

    // lists of students
    $studentFilters = [];
    $shownStudents = [];
    // 1. students of selected dateId
    if ($selectedDateId) {
        $dateData = getTableData('mdl_block_exaplandates', $selectedDateId);
        $studentFilters['moodleid'] = $dateData['moodleid'];
        $students = getFixedPUsersForDate($selectedDateId);
        if ($students && count($students)) {
            $content .= $listGroupTitle('mainList', $getListGroupTitle($dateData['state']));
            $rowsCount++;
            foreach ($students as $pUserRelation) {
                $pUserId = $pUserRelation['puserid'];
                $pUserData = getTableData('mdl_block_exaplanpusers', $pUserId);
                $shownStudents[] = $pUserId;
                $content .= rowForStudentInFormAdminDateFixing($pUserData, $dateData, false, $modulepartId, $defaultRegion, 'mainList'); // always selected students
                $rowsCount++;
            }
        }
    }
    // 2. desired students for this date
    $desiredDates = getDesiredDates(null, $modulepartId, $date, null, $defaultRegion);
    $desiredDates = $filterDesiredDates($desiredDates, $shownStudents, $studentFilters);
    if ($desiredDates && count($desiredDates) > 0) {
        $content .= $listGroupTitle('desiredList', $getListGroupTitle('desired'));
        $rowsCount++;
        foreach ($desiredDates as $dateData) {
            $pUserId = $dateData['relatedUserId'];
            $shownStudents[] = $pUserId;
            $pUserData = getTableData('mdl_block_exaplanpusers', $pUserId);
            $content .= rowForStudentInFormAdminDateFixing($pUserData, $dateData, false, $modulepartId, $defaultRegion, 'desiredList');
            $rowsCount++;
        }
    }
    // 3. desired students for other dates, but for this modulepartId
    $desiredDates = getDesiredDates(null, $modulepartId, null, null, $defaultRegion);
    $desiredDates = $filterDesiredDates($desiredDates, $shownStudents, $studentFilters);
    if ($desiredDates && count($desiredDates) > 0) {
        $content .= $listGroupTitle('desiredOtherList', $getListGroupTitle('theSameModulePart'));
        $rowsCount++;
        foreach ($desiredDates as $dateData) {
            $pUserId = $dateData['relatedUserId'];
            $shownStudents[] = $pUserId;
            $pUserData = getTableData('mdl_block_exaplanpusers', $pUserId);
            $content .= rowForStudentInFormAdminDateFixing($pUserData, $dateData, false, $modulepartId, $defaultRegion, 'desiredOtherList');
            $rowsCount++;
        }
    }

    // bulk functions
    if ($selectedDateId ) {
        $content .= adminBulkFunctionsFormPart($usersDataColumns, $selectedDateId);
        $rowsCount++;
    }

    // special empty row (to miss row height counting. Last row will have flexible height)
    $content .= '<tr><td colspan="'.$usersDataColumns.'" class="emptyRow"></td></tr>';
    $rowsCount++;

    $content .= '</table>';
    $content .= '</form>';


    $content = str_replace('###FORM_ROWSPAN###', $rowsCount, $content);
    return $content;
}

function rowForStudentInFormAdminDateFixing($pUserData, $dateData, $pUserSelected, $modulepartId, $defaultRegion, $listId) {
    global $CFG;

    $pUserId = $pUserData['id'];
    $content = '';
    $defaultRowHeght = 20;

    $content .= '<tr data-listId="'.$listId.'" class="'.$listId.'">';
    $content .= '<td valign="top" height="' . $defaultRowHeght . '" class="studentNameColumn">';
    // fixed or desired
    $content .= '<label for="fixedUser' . $pUserId . '">';
    $content .= '<input type="checkbox" 
                                value="1"    
                                class="fixedPuserCheckbox"  
                                id = "fixedUser' . $pUserId . '"                               
                                name = "fixedPuser[' . $pUserId . ']" 
                                ' . ($pUserSelected ? 'checked = "checked"' : '') . '/>&nbsp;';
    $content .= $pUserData['firstname'].' '.$pUserData['lastname'] . '</label>';
//    $content .= '<label for="fixedUser' . $pUserId . '">' .$pUserData['firstname'].' '.$pUserData['lastname'] . '</label>';
    $content .= '</td>';
    // buttons
    $content .= '<td valign="top" class="iconButtonsColumn">' ./* icon buttons*/ '</td>';
    // organization
    $companyName = getTableData('mdl_block_exaplanmoodles', $pUserData['moodleid'], 'companyname');
    $content .= '<td valign="top" class="organizationColumn">' . $companyName . '</td>';
    // count of desired dates
    $desiredDates = getDesiredDates($pUserId, $modulepartId, null, null, $defaultRegion);
    if (count($desiredDates) > 0) {
        $datelist = getDesiredDatesDatelist($desiredDates);
        $dateListForJS = implode(',', array_map(function ($d) {return date('Y-m-d', $d["date"]);}, $desiredDates));
        $desiredDatesCount = '<a href="#" title="'.$datelist.'" class="exaplan-markCalendarDates" data-markDates="'.$dateListForJS.'">'.count($desiredDates) . '</a> Termin' . (count($desiredDates) > 1 ? 'e' : '');
    } else {
        $desiredDatesCount = '';
    }
    $content .= '<td valign="top" class="desiredDatesColumn">' . $desiredDatesCount . '</td>';
    // midDay type checkboxes
    $content .= '<td valign="top" class="timeslotCheck'.BLOCK_EXAPLAN_MIDDATE_BEFORE.'">';
    $content .= '<input type="checkbox" disabled readonly onclick="return false;"
                                value = "1"      
                                id = "middayType'.BLOCK_EXAPLAN_MIDDATE_BEFORE.'_'.$pUserId.'"                               
                                name = "middayType'.BLOCK_EXAPLAN_MIDDATE_BEFORE.'['.$pUserId.']" 
                                '.($dateData['timeslot'] == BLOCK_EXAPLAN_MIDDATE_BEFORE || $dateData['timeslot'] == BLOCK_EXAPLAN_MIDDATE_ALL ? ' checked="checked" ' : '').'/>&nbsp;';

    $content .= '</td>';
    $content .= '<td valign="top" class="timeslotCheck'.BLOCK_EXAPLAN_MIDDATE_AFTER.'">';
    $content .= '<input type="checkbox" disabled readonly onclick="return false;"
                                value = "1"      
                                id = "middayType'.BLOCK_EXAPLAN_MIDDATE_AFTER.'_'.$pUserId.'"                               
                                name = "middayType'.BLOCK_EXAPLAN_MIDDATE_AFTER.'['.$pUserId.']" 
                                '.($dateData['timeslot'] == BLOCK_EXAPLAN_MIDDATE_AFTER || $dateData['timeslot'] == BLOCK_EXAPLAN_MIDDATE_ALL ? ' checked="checked" ' : '').'/>&nbsp;';
    $content .= '</td>';
    // absent or not
    $absent = '';
    if ($relationData = isPuserIsFixedForDate($pUserId, $dateData['id'], true)) {
        if ($relationData['absent']) {
            $absent = '<img class="absentStudent" src="'.$CFG->wwwroot.'/blocks/exaplan/pix/absent.png" title="TN gefehlt" />';
//            $absent = ' checked = "checked" ';
        }
    }
    $content .= '<td style="text-align: center;  vertical-align: top;" class="absentColumn">';
    $content .= $absent;
    /*    $content .= '<input type="hidden" value="0" name="absentPuser[' . $pUserId . ']" />
                            <input type="checkbox"
                                    value="1"
                                    name="absentPuser[' . $pUserId . ']"
                                    ' . $absent . '/>'*/;
    $content .= '</td>';

    $content .= '</tr>';

    return $content;
}

/**
 * inputs for main data of 'mdl_block_exaplandates'.
 * Look also inputs in function modulepartAdminViewByDate()
 * @param int $modulepartId
 * @param string $date
 * @param int $timeslot
 * @param string $defaultRegion
 * @param int $selectedDateId
 * @return string
 */
function formAdminDateFixing($modulepartId, $date, $timeslot = null, $defaultRegion = '', $selectedDateId = 0) {
    global $CFG;
    $content = '';
    $dateTs = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0)->getTimestamp();
    $instanceKey = $modulepartId.'_'.$dateTs/*.'_'.$timeslot*/;

    $selectboxTemplate = function($name, $items, $preselected = null) use ($instanceKey) {
        $content = '<select id="'.$name.'_'.$instanceKey.'" class="form-control" name="'.$name.'">';
        foreach ($items as $option) {
            $content .= '<option value="'.$option['id'].'" '.($preselected == $option['id'] ? ' selected="selected" ' : '').'>'.$option['title'].'</option>';
        }
        $content .= '</select>';
        return $content;
    };

    if ($selectedDateId) {
//        $dateRec = getPrefferedDate($modulepartId, $dateTs, $timeslot); // get fisrt
        $dateRec = getTableData('mdl_block_exaplandates', $selectedDateId);
    } else {
        // empty form
        $dateRec = [];
    }

    if ($selectedDateId) {
        $content .= '<input type="hidden" value="'.$selectedDateId.'" name="dateId" />';
    }
    $content .= '<input type="hidden" value="'.$date.'" name="date" />';
    $content .= '<input type="hidden" value="'.$modulepartId.'" name="mpId" />';

    $content .= '<table class="table table-sm table-borderless" border="0">';

    // moodleid
    $content .= '<td colspan="6">';
    $content .= '<label for="moodleid_'.$instanceKey.'">DF Ort:</label>';
    $moodles = getMoodles();
    $options = array_map(function ($m) {
        return [
            'id' => $m['moodleid'],
            'title' => $m['companyname'],
        ];
    }, $moodles);
    $options = array_merge(['-1' => ['id' => 0, 'title' => 'Öffentlich']], $options);
    $content .= $selectboxTemplate('moodleid', $options, @$dateRec['moodleid']);
    $content .= '</td>';
    $content .= '</tr>';

    // region
    $content .= '<tr>';
    $content .= '<td colspan="3">';
    $content .= '<label for="dateRegion_'.$instanceKey.'">Region:</label>';
    $options = [
        ['id' => 'RegionOst', 'title' => getRegionTitle('RegionOst')],
        ['id' => 'RegionWest', 'title' => getRegionTitle('RegionWest')],
    ];
    $content .= $selectboxTemplate('dateRegion', $options, @$dateRec['region']);
    $content .= '</td>';
    // isonline
    $content .= '<td colspan="3">';
    $content .= '<label for="isonline_'.$instanceKey.'">DF Art:</label>';
    $options = [
        ['id' => '0', 'title' => getIsOnlineTitle(0)],
        ['id' => '1', 'title' => getIsOnlineTitle(1)],
    ];
    $content .= $selectboxTemplate('isonline', $options, @$dateRec['isonline']);
    $content .= '</td>';
    $content .= '</tr>';

    // trainer
    $content .= '<td colspan="6">';
    $content .= '<label for="trainer_'.$instanceKey.'">Trainer:</label>';
    $trainers = block_exaplan_get_users_from_cohort();
    $options = array_map(function ($t) {
        return [
            'id' => getPuser($t->id)['id'], // original ID (not pUser), because it is on MAIN moodle installation
            'title' => fullname($t),
        ];
    }, $trainers);
    $content .= $selectboxTemplate('trainer', $options, @$dateRec['trainerpuserid']);
    $content .= '</td>';
    $content .= '</tr>';

    // location
    $content .= '<tr>';
    $content .= '<td colspan="2">';
    $content .= '<label for="location_'.$instanceKey.'">Location:</label>';
    $content .= '<input type="text" name="location" value="'.@$dateRec['location'].'" class="form-control" id="location_'.$instanceKey.'" /></td>';
    $content .= '</td>';
    // time
    $timeString = (@$dateRec['starttime'] ? date('H:i', @$dateRec['starttime']) : '00:00');
    $content .= '<td colspan="2">';
    $content .= '<label for="time_'.$instanceKey.'">Uhrzeit:</label>';
    $content .= '<input type="text" name="time" value="'.$timeString.'" class="form-control" id="time_'.$instanceKey.'" >';
    $content .= '</td>';
    // duration
    $content .= '<td colspan="2">';
    $content .= '<label for="duration_'.$instanceKey.'">Dauer:</label>';
    $content .= '<input type="text" name="duration" value="'.@$dateRec['duration'].'" class="form-control" id="duration_'.$instanceKey.'" >';
    $content .= '</td>';
    $content .= '</tr>';

    // link to online room
    $content .= '<tr>';
    $content .= '<td colspan="6">';
    $content .= '<label for="onlineroom_'.$instanceKey.'">Raum (BBB oder Teams):</label>';
    $content .= '<input type="text" name="onlineroom" value="'.@$dateRec['onlineroom'].'" class="form-control" id="onlineroom_'.$instanceKey.'" /></td>';
    $content .= '</td>';

    // description
    $content .= '<tr>';
    $content .= '<td colspan="6">';
    $content .= '<textarea name="description" id="description_'.$instanceKey.'" class="form-control" placeholder="Notiz" >'.@$dateRec['comment'].'</textarea>';
    $content .= '</td>';
    $content .= '</tr>';

    // buttons
    $buttons = ['row1' => [], 'row2' => []];
    // first buttons row
    // possible variants of buttons:
    // 1. no any selected date: date_block, date_save
    if (empty($dateRec)) {
        $buttons['row1']['left'] = '<button name="date_block" class="btn btn-info btn-date-block" type="submit" value="date_block" >Termin blocken</button>';
        $buttons['row1']['right'] = '<button name="date_save" class="btn btn-success btn-date-save" type="submit" value="date_save" >Termin fixieren</button>';
    } else {
        // 2. selected date - by selected state
        // 2.a. blocked
        if (@$dateRec['state'] == BLOCK_EXAPLAN_DATE_BLOCKED) {
            $buttons['row1']['left'] = '<button name="date_save" class="btn btn-success btn-date-save" type="submit" value="date_save" >Termin fixieren</button>';
            $buttons['row1']['right'] = '<button name="date_block" class="btn btn-info btn-date-block" type="submit" value="date_block" >Änderung speichern</button>';
        }
        // 2.b. fixed
        if (@$dateRec['state'] == BLOCK_EXAPLAN_DATE_FIXED) {
            $buttons['row1']['left'] = '';
            $buttons['row1']['right'] = '<button name="date_save" class="btn btn-success btn-date-save" type="submit" value="date_save" >Änderung speichern</button>';
        }
        // 2.c canceled
        if (@$dateRec['state'] == BLOCK_EXAPLAN_DATE_CANCELED) {
            // empty row
        }

    }
    // second buttons row
    $buttons['row2']['left'] = ''; // always empty
    if ($selectedDateId) {
        $cancelButtonTitle = 'Termin absagen';
        $buttonDisabled = '';
        if (@$dateRec['state'] == BLOCK_EXAPLAN_DATE_BLOCKED) {
            $cancelButtonTitle = 'Blockierung aufheben';
        } elseif ($dateRec['state'] == BLOCK_EXAPLAN_DATE_CANCELED) {
            $cancelButtonTitle = 'Termin abgesagt';
            $buttonDisabled = ' disabled = "disabled" ';
        }
        $buttons['row2']['right'] = '<button name="date_cancel" class="btn btn-default btn-date-cancel" type="submit" value="date_cancel" '.$buttonDisabled.'>'.$cancelButtonTitle.'</button></td>';
    }

    // third buttons row
    $buttons['row3']['left'] = ''; // always empty
    if ($selectedDateId) {
        $returnButtonTitle = 'abbrechen';
        $buttons['row3']['right'] = '<button name="date_return" class="btn btn-default btn-date-return" type="button" value="date_return" data-toDate="'.$date.'">'.$returnButtonTitle.'</button></td>';
    }

    $getButtonRowContent = function($rowNumber) use ($buttons) {
        $rowContent = '';
        $rowIndex = 'row'.$rowNumber;
        if (array_key_exists($rowIndex, $buttons) && count($buttons[$rowIndex]) > 0) {
            $rowContent .= '<tr>';
            $rowContent .= '<td align="left" colspan="3">';
            $rowContent .= @$buttons[$rowIndex]['left'];
            $rowContent .= '</td>';
            $rowContent .= '<td align="right" colspan="3">';
            $rowContent .= @$buttons[$rowIndex]['right'];
            $rowContent .= '</td>';
            $rowContent .= '</tr>';
        }
        return $rowContent;
    };

    $content .= $getButtonRowContent(1);
    $content .= $getButtonRowContent(2);
    $content .= $getButtonRowContent(3);

    $content .= '</table>';

    return $content;
}

function adminBulkFunctionsFormPart($usersDataColumnsCount, $dateId) {
    $content = '';
    $content .= '<tr><td colspan="' . $usersDataColumnsCount . '" class="bulkFunctions">';
    $content .= '<table class="bulkFunctionsForm" border="0">';
    $content .= '<tr>';
    $content .= '<td align="right">mit ausgewähtlen TN:</td>';
    $content .= '<td>';
    $bulkFunctions = [
        '' => 'Aktion auswählen',
        'studentsAdd' => 'TN hinzufügen',
        'studentsRemove' => 'TN entfernen',
        'studentsAbsent' => 'TN gefehlt',
        'sendMessage' => 'Nachricht senden',
    ];
    $dateData = getTableData('mdl_block_exaplandates', $dateId);
    // hide some actions by date states
    switch ($dateData['state']) {
        case BLOCK_EXAPLAN_DATE_DESIRED:
            unset($bulkFunctions['studentsAdd']);
            unset($bulkFunctions['studentsRemove']);
            unset($bulkFunctions['studentsAbsent']);
            break;
        case BLOCK_EXAPLAN_DATE_FIXED:
            break;
        case BLOCK_EXAPLAN_DATE_BLOCKED:
            unset($bulkFunctions['studentsAbsent']);
            break;
        case BLOCK_EXAPLAN_DATE_CANCELED:
            unset($bulkFunctions['studentsAdd']);
            unset($bulkFunctions['studentsRemove']);
            unset($bulkFunctions['studentsAbsent']);
            break;
    }
    $content .= '<select id="bulk_function" class="form-control" name="bulk_function">';
    foreach ($bulkFunctions as $value => $title) {
        $content .= '<option value="'.$value.'">'.$title.'</option>';
    }
    $content .= '</select>';
    $content .= '</td>';
    $content .= '<td align="left" width="5%"><button name="bulk_go" class="btn btn-info btn-bulkaction-go" type="submit" value="bulk_go" >go!</button></td>';
    $content .= '</tr>';
    $content .= '<tr id="bulkMessage" style="display: none;">';
    $content .= '<td align="right">Nachricht:</td>';
    $content .= '<td colspan="2"><textarea name="bulk_message" id="bulk_message" class="form-control" placeholder="" ></textarea></td>';
    $content .= '</tr>';
    $content .= '</table>';
    return $content;
}

/**
 * @param int $modulepartId
 * @param string $date
 * @param int $selectedDateId
 * @return string
 */
function buttonsForExistingDates($modulepartId, $date, $selectedDateId) {
    $content = '';
    $when = 'future';
    if (block_exaplan_is_admin()) {
        $when = 'always';
    }
    $dates = getDatesForModulePart($modulepartId, $date, '', $when);
    foreach ($dates as $fDate) {
        $titleParts = [
            $date,
            getTableData('mdl_block_exaplanmoodles', $fDate['moodleid'], 'companyname'),
            getRegionTitle($fDate['region']),
            getIsOnlineTitle($fDate['isonline']),
        ];
        $titleParts = array_filter($titleParts);
        $title = implode(' - ', $titleParts);
        $url = new moodle_url('/blocks/exaplan/admin.php', array('mpid' => $modulepartId, 'date' => $date, 'region' => 'all', 'dateId' => $fDate['id']));
        $content .= '<span class="exaplan-date-button-item '.($selectedDateId == $fDate['id'] ? 'exaplan-existing-date-selected' : '').'">';
        $content .= '<a class="btn btn-'.getDateStateCodeByIndex($fDate['dateType']).' exaplan-existing-date " href="'.$url.'">'.$title.'</a>';
        $users = getFixedPUsersForDate($fDate['id']);
        $studentsCount = count($users);
        $content .= $studentsCount ? '<span class="countStudents">'.$studentsCount.'</span>' : '';
        $content .= '</span>';
    }
    return $content;
}

function studentEventDetailsView($userId, $modulepartId, $dateId) {
    global $OUTPUT;

    $content = '';

    $puserId = getPuser($userId)['id'];

    if (!isPuserIsFixedForDate($puserId, $dateId)) {
        return 'No data!';
    }

    $modulepartName = getTableData('mdl_block_exaplanmoduleparts', $modulepartId, 'title');
    $moduleId = getTableData('mdl_block_exaplanmoduleparts', $modulepartId, 'modulesetid');
    $moduleName = getTableData('mdl_block_exaplanmodulesets', $moduleId, 'title');
    $dateData = getTableData('mdl_block_exaplandates', $dateId);

    $absent = false;
    if ($relationData = isPuserIsFixedForDate($puserId, $dateData['id'], true)) {
        if ($relationData['absent']) {
            $absent = true;
        }
    }

    $content .= '<div class="exaplan-date-details-container">';
    $content .= '<table class="table table-sm table-borderless exaplan-date-details-table '.($absent ? ' absent ' : '').'">';

    // table header with main data
    $content .= '<tr>';
    $content .= '<th>Termindetails: '.$moduleName.' | '.$modulepartName.'</th>';
    $content .= '<th>'.date('d.m.Y', $dateData['date']).'</th>';
    $content .= '</tr>';

    $tableRow = function($label, $value) {
        $content = '<tr>';
        $content .= '<td class="dataLabel">'.$label.'</td>';
        $content .= '<td class="dataContent">'.$value.'</td>';
        $content .= '</tr>';
        return $content;
    };

    // moodleid info
    $moodleData = getMoodleDataByMoodleid($dateData['moodleid'],'','Öffentlich');
    $content .= $tableRow('Ort:', @$moodleData['companyname']);

    // region
    //$content .= $tableRow('Region:', getRegionTitle(@$dateData['region']));

    // is online
    $content .= $tableRow('Art:', getIsOnlineTitle($dateData['isonline']));

    // location
    $content .= $tableRow('Ort:', $dateData['location']);

    // time start
    $content .= $tableRow('Uhrzeit:', date('H:i', $dateData['starttime']));

    // duration
    $content .= $tableRow('Dauer:', $dateData['duration']);

    // trainer
    $trainer = getTableData('mdl_block_exaplanpusers', $dateData['trainerpuserid']);
    $content .= $tableRow('Trainer:', @$trainer['firstname'].' '.@$trainer['lastname']);

    // link to online room
    if ($dateData['isonline'] && $dateData['onlineroom']) {
        $link = $dateData['onlineroom'];
        $rowContent = checkOnlineRoomTypeByLink($link).'&nbsp;|&nbsp;<a href="'.$link.'" class="exaplan-onlineroom-link" target="_blank">Startseite&nbsp;'.$OUTPUT->pix_icon("e/insert_edit_video", checkOnlineRoomTypeByLink($link)).'</a>';
        $content .= $tableRow('Raum:', $rowContent);
    }

    // description
    if ($dateData['comment']) {
        $content .= '<tr>';
        $content .= '<td colspan="2"><strong>Notiz:</strong><br>'.$dateData['comment'].'</td>';
        $content .= '</tr>';
    }

    $content .= '</table>';

    // if the student is_absent - add marker
    if ($absent) {
        $content .= '<div class="block-exaplan-absent-marker">gefehlt</div>';
    }


    $content .= '</div>';

    return $content;
}

function printAdminDashboard($dashboardType = BLOCK_EXAPLAN_DASHBOARD_DEFAULT)
{
    global $CFG, $PAGE, $OUTPUT;
    $content = '';

    $modulesets = getAllModules();

    switch ($dashboardType) {
        case BLOCK_EXAPLAN_DASHBOARD_INPROCESS:
            $dashoboardTitle = 'Übersicht: zukünftige Termine';
            break;
        case BLOCK_EXAPLAN_DASHBOARD_PAST:
            $dashoboardTitle = 'Übersicht: zurückliegende Termine';
            break;
        case BLOCK_EXAPLAN_DASHBOARD_DEFAULT:
        default:
            $dashoboardTitle = 'Übersicht Anfragen';
            break;
    }


    $content .= '<div class="exaplan-result-item">';

    $content.= '<div class="UserBlock">';
    $content .= '<div class="BlockHeader">';

    $content .= '</div>';
    $content .= '<div class="BlockBody">';
    $content .= '<table class="mainTableAdmin" border="0">';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th colspan="3">
                    <div class="result-item-header">
                        <div class="result-item-header-cnt">                                                    
                            <h5 class="item-header-title">'.$dashoboardTitle.'</h5>   	                        	
                        </div>
                        <div class="dashboard-settings">
                            <a href="'.$CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid=1" role="button" >'.$OUTPUT->pix_icon("i/settings", "Einstellung").'</a>                    
                        </div>
                    </div>                    
                </th>';
    $content .= '</tr>';
    $content .= '</thead>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td valign="top">';

    $content .= '<table class="moduleListTable" border="0">';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th rowspan="2" valign="top">Module</th>';
    $content .= '<th rowspan="2" valign="top">Termine</th>';
    $content .= '<th colspan="1">';
    if ($dashboardType == BLOCK_EXAPLAN_DASHBOARD_DEFAULT) {
        $content .= 'Anzahl Teilnehmer angefragt:';
    }
    $content .= '</th>';
    $content .= '</tr>';
    /*$content .= '<tr>';
    $content .= '<th class="regionColumn">'.getRegionTitle('RegionOst').'</th>';
    $content .= '<th class="regionColumn">'.getRegionTitle('RegionWest').'</th>';
    $content .= '<th class="regionColumn">'.getRegionTitle('').'</th>';
    $content .= '</tr>';*/
    $content .= '</thead>';

    $regions = [/*'RegionOst', 'RegionWest', */'all'];

    $content .= '<tbody>';

    $buttonTemplate = function($modulepartid, $region, $title, $buttonClass, $date = '') use ($dashboardType) {
        global $CFG;
        return '<a href="'.$CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$modulepartid.'&region='.$region.($date ? '&date='.date('Y-m-d', $date) : '').($dashboardType ? '&dashboardType='.$dashboardType : '').'" 
                            role="button" 
                            data-modulepartId="'.$modulepartid.'"
                            class="btn btn-danger exaplan-admin-modulepart-button '.$buttonClass.'"
                        > '.$title.' </a>';
    };

    foreach ($modulesets as $moduleKey => $moduleset){
        if ($moduleset->parts && count($moduleset->parts) > 0) {
            $content .= '<tr>';
            $content .= '<td valign="top" rowspan="'.count($moduleset->parts).'" class="moduleset-title">';

            $editUrl = $CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid=1&targetTable=moduleparts&msid='.$moduleset->set['id'];
            $content .= html_writer::span(
                '<a href="'.$editUrl.'">'.$OUTPUT->pix_icon("i/edit", "Modulteile bearbeiten").'</a>',
                'edit-modulepart-button');
            $content .= html_writer::span($moduleset->set["title"], 'title');
            $content .= '</td>';
            foreach ($moduleset->parts as $partK => $part) {
                if ($partK != 0) {
                    $content .= '<tr>';
                }
                $content .= '<th>' . $part["title"] . '</th>';
                foreach ($regions as $region) {
                    $content .= '<td class="regionColumn">';
                    $buttonClass = '';
                    switch ($dashboardType) {
                        case BLOCK_EXAPLAN_DASHBOARD_INPROCESS:
                            // existing fixed / blocked dates (in the future)
                            $fixedDates = getFixedDatesAdvanced(null, $part['id'], null, null, true, $region, 'future');
                            //                        $fixedDates = getDatesForModulePart($part['id'], null, $region, 'future');
                            if (count($fixedDates) > 0) {
                                $shownDates = [];
                                foreach ($fixedDates as $fixedDate) {
                                    if (!in_array($fixedDate['id'], $shownDates)) { // getFixedDatesAdvanced returns multiple records for the same date by related users
                                        $buttonClass = ' exaplan-date-' . getDateStateCodeByIndex($fixedDate['dateType']) . ' ';
                                        $content .= $buttonTemplate($part['id'], $region, date('d.m.Y', $fixedDate['date']), $buttonClass, $fixedDate['date']) . '&nbsp;';
                                        $shownDates[] = $fixedDate['id'];
                                    }
                                }
                            }
                            break;
                        case BLOCK_EXAPLAN_DASHBOARD_PAST:
                            // fixed dates in past
                            //                        $fixedDates = getFixedDatesAdvanced(null, $part['id'], null, null, false, $region, 'past');
                            $fixedDates = getDatesForModulePart($part['id'], null, $region, 'past');
                            if (count($fixedDates) > 0) {
                                foreach ($fixedDates as $fixedDate) {
                                    $buttonClass = ' exaplan-date-' . getDateStateCodeByIndex($fixedDate['dateType']) . ' ';
                                    $content .= $buttonTemplate($part['id'], $region, date('d.m.Y', $fixedDate['date']), $buttonClass, $fixedDate['date']) . '&nbsp;';
                                }
                            }
                            break;
                        case BLOCK_EXAPLAN_DASHBOARD_DEFAULT:
                            // desired dates
                            $desiredDates = getDesiredDates(null, $part['id'], null, null, $region, 'future');

                            if (count($desiredDates) > 0) {
                                // get count of unique pUsers
                                $desiredDatesUsers = count(array_unique(array_column($desiredDates, 'puserid')));
                                if ($desiredDatesUsers == 1) {$title = $desiredDatesUsers . ' Anfrage<span class="hidechar">n</span> ';}
                                else {$title = $desiredDatesUsers . ' Anfragen';}
                                $buttonClass2 = $buttonClass.' exaplan-date-desired ';
                                $content .= $buttonTemplate($part['id'], $region, $title, $buttonClass2) . '&nbsp;';
                            }
                            // button to add new fixed date
                            $title = ' - - ';
                            $buttonClass .= ' exaplan-date-no-desired-admin ';
                            $content .= $buttonTemplate($part['id'], $region, $title, $buttonClass);
                            break;
                    }

                    $content .= '</td>';
                }
                if ($partK != 0 || count($moduleset->parts) == 1) {
                    $content .= '</tr>';
                }
            }
        } else {
            // no any part yet
            $content .= '<tr>';
            $content .= '<td valign="top" class="moduleset-title">';
            $content .= html_writer::span($moduleset->set["title"], 'title');
            // add module part button
            $editUrl = $CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid=1&targetTable=moduleparts&msid='.$moduleset->set['id'];
            $content .= html_writer::span(
                '<a href="'.$editUrl.'">'.$OUTPUT->pix_icon("i/addblock", "Modulteile hinzufügen").'</a>',
                'add-modulepart-button');
            $content .= '</td>';
            $content .= '<td>';
            $content .= '</td>';
            $content .= '<td colspan="20"></td>';
            $content .= '</tr>';
        }

    }

    $content .= '</tbody>';
    $content .= '</table>';

    $content .= '</td></tr>';

    $content .= '</tbody>';
    $content .= '</table>'; // .mainTable


    $content .= '</div>';

    $content .= '<div class="BlockFooter">';
    // buttons to dashboards
    switch ($dashboardType) {
        case BLOCK_EXAPLAN_DASHBOARD_INPROCESS:
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_DEFAULT.'" role="button" class="btn btn-info btn-to-dashboard btn-anfragen"> Übersicht Anfragen </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_INPROCESS.'" role="button" class="btn btn-info btn-to-dashboard2 btn-zukunft btnactive"> Übersicht: zukünftige Termine </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_PAST.'" role="button" class="btn btn-info btn-to-dashboard2 btn-past"> Übersicht: zurückliegende Termine </a>&nbsp;';
            break;
        case BLOCK_EXAPLAN_DASHBOARD_PAST:
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_DEFAULT.'" role="button" class="btn btn-info btn-to-dashboard btn-anfragen"> Übersicht Anfragen </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_INPROCESS.'" role="button" class="btn btn-info btn-to-dashboard2 btn-zukunft "> Übersicht: zukünftige Termine </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_PAST.'" role="button" class="btn btn-info btn-to-dashboard2 btn-past btnactive"> Übersicht: zurückliegende Termine </a>&nbsp;';
            break;
        case BLOCK_EXAPLAN_DASHBOARD_DEFAULT:
        default:
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_DEFAULT.'" role="button" class="btn btn-info btn-to-dashboard btnactive btn-anfragen"> Übersicht Anfragen </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_INPROCESS.'" role="button" class="btn btn-info btn-to-dashboard btn-zukunft "> Übersicht: zukünftige Termine </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType='.BLOCK_EXAPLAN_DASHBOARD_PAST.'" role="button" class="btn btn-info btn-to-dashboard2 btn-past "> Übersicht: zurückliegende Termine </a>&nbsp;';
            break;
    }
    $content .= '</div>';
    $content .= '<div><a href="'.$CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid=1" role="button" class="btn btn-info btn-to-dashboard3"> Moduleinträge bearbeiten </a>&nbsp;';
    $content .= '</div>';
    $content .= '</div><!-- / exaplan-result-item --->';
    return $content;
}

function printStudentExistingFixedDates($pUserId) {
    $content = '';
    $dates = getFixedDatesAdvanced($pUserId, null, null, null, false, '', 'future', [BLOCK_EXAPLAN_DATE_FIXED]);
    $content .= '<table class="exaplan-student-dates-preview">';
    foreach ($dates as $k => $dateData) {
        $content .= '<tr>';
        $content .= '<td>';
        $content .= '<strong>'.getFixedDateModuleTitles($dateData['id']).'</strong>';
        $content .= '</td>';
        $content .= '<td>';
        $content .= '<strong>'.date("d.m.Y", $dateData['date']).'</strong>';
        $content .= '</td>';
        $content .= '</tr>';
        $content .= '<tr>';
        $content .= '<td>';
        if ($dateData['isonline']) {
            $link = $dateData['onlineroom'];
            $content .= checkOnlineRoomTypeByLink($link).'&nbsp;|&nbsp;<a href="'.$link.'" class="exaplan-onlineroom-link" target="_blank">Startseite</a>';
        } else {
            $content .= 'Präsenztermin | '.$dateData['location'];
        }
        $content .= '</td>';
        $content .= '</tr>';
        $content .= '<tr><td colspan="2">&nbsp;</td></tr>';
    }
    $content .= '</table>';
    return $content;
}
