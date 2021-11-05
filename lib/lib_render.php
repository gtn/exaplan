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
function printUser($userid, $mode = 0, $modulepartid = 0, $withCalendar = false, $dateId = 0, $withDateDetails = false){
    global $CFG;

   
    if ($mode == 1) {
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
    $content .= '<th colspan="2"><div class="result-item-header">
<div class="result-item-header-cnt">
	
<div class="icon">
<img style="position:absolute;left:-20px;" src="'.$CFG->wwwroot.'/blocks/exaplan/pix/teilnehmer.svg" height="50" width="50">
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
            $content .= '<th>'.$part["title"].'</th>';
        }
        $content .= '</tr>';
        $content .= '</thead>';
        $content .= '<tbody>';
        foreach($moduleset->parts as $part) {
            $content .= '<td>';
            if ($mode == 1) {
                // for admins
                $desiredDates = getDesiredDates(null, $part['id']);
                $buttonClass = '';
                if (count($desiredDates) > 0) {
                    $title = count($desiredDates).' Anfragen';
                    $buttonClass .= ' exaplan-date-desired ';
                } else {
                    $title = ' - - ';
                    $buttonClass .= ' exaplan-date-no-desired ';
                }
                $content .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$part["id"].'" 
                                role="button" 
                                data-modulepartId="'.$part['id'].'"
                                class="btn btn-danger exaplan-admin-modulepart-button '.$buttonClass.'"
                            > '.$title.' </a>';
            } else {
                if (!$part['date'] || $part['date'][0]['state'] != BLOCK_EXAPLAN_DATE_CONFIRMED) {
                    // desired dates
                    $disabled = '';
                    if (getDesiredDates($pUser['id'], $part['id'])) {
                      $buttonTitle = 'Wunschtermin';
                      $buttonClass = ' exaplan-date-desired ';
                      $innerButtonClass = ' btn btn-desire ';
                      if ($modulepartid == $part["id"]) {
                        $buttonClass .= ' exaplan-date-current-modulepart ';
                    	}
                    } else {
                    	$buttonTitle = 'offen';
	                    $buttonClass = '';
	                    $innerButtonClass = ' btn btn-danger ';
	                    if ($modulepartid == $part["id"]) {
                            $buttonClass .= ' exaplan-date-current-modulepart ';
                    	}
                    }
                    $dateUrl = $CFG->wwwroot.'/blocks/exaplan/calendar.php?mpid='.$part["id"].'&userid='.$userid.'&pagehash='.block_exaplan_hash_current_userid($userid);
                    if ($moduleset->set["nodesireddates"]) {
                        // disable possibility to select desired dates (if the student already has or not)
                        $buttonTitle = ' - - ';
                        $buttonClass = ' exaplan-date-nodesireddates ';
                        $dateUrl = '#';
                        $disabled = ' disabled = "disabled" ';
                    }
                    
                    $content .= '<a href="'.$dateUrl.'" 
                                    role="button" 
                                    class="btn exaplan-selectable-modulepart '.$buttonClass.'"                                     
                                    data-modulepartId="'.$part['id'].'"
                                    '.($modulepartid == $part["id"] ? 'data-modulepartselected="1"' : '').'
                                > <button type="button" class="'.$innerButtonClass.'" '.$disabled.'>'.$buttonTitle.'</button> </a>';
                } else {
                    // fixed date exists
                    $buttonClass = '';
                    if ($modulepartid == $part["id"]) {
                        $buttonClass .= ' exaplan-date-current-modulepart ';
                    }
                    $content .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/dateDetails.php?mpid='.$part["id"].'&userid='.$userid.'&dateid='.$part['date'][0]['id'].'&pagehash='.block_exaplan_hash_current_userid($userid).'"
                                    class="btn exaplan-date-fixed exaplan-selectable-date '.$buttonClass.'" 
                                    data-dateId="'.$part['date'][0]['id'].'" 
                                    data-modulepartId="'.$part['id'].'">
                                 <button type="button" class=" btn btn-fix ">'.date('d.m.Y', $part['date'][0]['date']).
                                '</button></a>';
                }
            }

            $content .= '</td>';
        }
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</td>';
        $content .= '</tr>';
    }

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
    $existingDates = getFixedDatesAdvanced(null, $modulepartId, null, null, true, '', '', [BLOCK_EXAPLAN_DATE_PROPOSED, BLOCK_EXAPLAN_DATE_CONFIRMED]);
    $content .= '<h4>Sie planen: '.$moduleName.' | '.$modulepartName.'</h4>';
    if ($existingDates) {
        $content .= '<div class="register-existing-dates">';
        $tooltips = '<div class="tooltip_templates">';
        $content .= '<table class="table table-sm table-borderless">';
        foreach ($existingDates as $dKey => $date) {
            $content .= '<tr>';
            $content .= '<td>';
            if ($dKey === 0) {
                $content .= 'Termine zur Auswahl:';
            }
            $content .= '</td>';
            $trainer = getTableData('mdl_block_exaplanpusers', $date['trainerpuserid']);
            $tooltips .= '<span id="tooltipster_content'.$date['id'].'">';
            $tooltips .= ($date['starttime'] ? 'Uhrzeit: <strong>'.date('H:i', $date['starttime']).'</strong> '.date('d.m.Y', $date['date']).'<br>' : '')
                .($date['location'] ? 'Location: '.$date['location'].'<br>' : '')
                .($trainer ? 'Skillswork-Trainer: '.@$trainer['firstname'].' '.@$trainer['lastname'].'<br>' : '');
            $tooltips .= '</span>';
            $content .= '<td align="right"><a href="#" class="btn btn-sm exaplan-existing-date tooltipster" data-tooltip-content="#tooltipster_content'.$date['id'].'">'.date('d.m.Y', $date['date']).'</a></td>';
            $url = $CFG->wwwroot.'/blocks/exaplan/calendar.php?action=registerToDate&mpid='.$modulepartId.'&dateid='.$date['id'].'&userid='.$userid.'&pagehash='.block_exaplan_hash_current_userid($userid).'';
            $content .= '<td align="left"><a href="'.$url.'" class="btn btn-sm exaplan-register-toDate">Termin bestätigen</a></td>';

            $content .= '</tr>';
        }
        $content .= '</table>';
        $tooltips .= '</div>';
        $content .= $tooltips;
        $content .= '<strong>ODER</strong><br>';
        $content .= 'Wunschtermin wählen:';
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
 */
function printAdminModulepartView($modulepartid, $date = '', $region = '') {
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
    $content .= '<td width="25%" valign="top">Gesamt Teilnehmer angefragt: </td>';
    $content .= '<td width="25%" valign="top">Rest:</td>';
    $content .= '</tr>';
    $content .= '</table>';
    // calendars
    $content .= block_exaplan_calendars_view(0, 4, false, $modulepartid, $region);
    // day ajax reloaded data (just HTML container)
    $content .= '<div id="modulepart-date-data">';
    if ($date) {
        $content .= modulepartAdminViewByDate($modulepartid, $date, $region);
    }
    $content .= '</div>';

    $content .= '</div>';

    return $content;

}

/**
 * @param int $modulepartId
 * @param string $date
 * @param string $defaultRegion
 * @return string
 */
function modulepartAdminViewByDate($modulepartId, $date, $defaultRegion = '') {
    global $CFG;
    $dashboardType = optional_param('dashboardType', '', PARAM_TEXT);
    $content = '';

    $actionUrl = $CFG->wwwroot.'/blocks/exaplan/admin.php?mpid='.$modulepartId.'&date='.$date.($defaultRegion ? '&region='.$defaultRegion : '').($dashboardType ? '&dashboardType='.$dashboardType : '');
    $content .= '<form class="small" action="'.$actionUrl.'" method="post" autocomplete="off">';
    $content .= '<input type="hidden" name="action" value="saveFixedDates" />';
    $content .= '<table class="table table-sm exaplan-adminModulepartView" border="0">';
    // header
    $content .= '<thead class="thead-light">';
    $content .= '<tr>';
    $content .= '<th>Angefragte TN: '.german_dateformat($date).'</th>';
    $content .= '<th></th>';
    $content .= '<th>Organization</th>';
    $content .= '<th>VM</th>';
    $content .= '<th>NM</th>';
    $content .= '<th>TN gefehlt?</th>';
    $content .= '<th><!--bewertung, later --></th>';
    $content .= '<th class="mainForm"></th>';
    $content .= '</tr>';
    $content .= '</thead>';

    $content .= '<tbody>';

    $states = [BLOCK_EXAPLAN_DATE_PROPOSED, BLOCK_EXAPLAN_DATE_CONFIRMED, BLOCK_EXAPLAN_DATE_BLOCKED];

    $rowsCount = 0;
    $mergedData = block_exaplan_get_admindata_for_modulepartid_and_date($modulepartId, $date, null, $defaultRegion, $states);

    if (count($mergedData) > 0) {
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

        foreach ($mergedData as $dKey => $dateData) {
            $setRowHight = 20;
            end($mergedData);
            if ($dKey === key($mergedData)) {
                $setRowHight = '';
            }
            $rowsCount++;
            $content .= '<tr>';
            if ($dateData['pUserData']) {
                $content .= '<td valign="top" height="' . $setRowHight . '">';
                // fixed or desired
                $content .= '<input type="checkbox" 
                                value="1"      
                                id = "fixedUser' . $dateData['pUserData']['id'] . '"                               
                                name = "fixedPuser[' . $dateData['pUserData']['id'] . ']" 
                                ' . (in_array($dateData['dateType'], ['fixed', 'blocked']) ? 'checked = "checked"' : '') . '/>&nbsp;';
                $content .= '<label for="fixedUser' . $dateData['pUserData']['id'] . '">' . @$dateData['pUserData']['firstname'] . ' ' . @$dateData['pUserData']['lastname'] . '</label>';
                $content .= '</td>';
                // buttons
                $content .= '<td valign="top">' ./*buttons*/
                    '</td>';
                // organization
                $companyName = getTableData('mdl_block_exaplanmoodles', $dateData['pUserData']['moodleid'], 'companyname');
                $content .= '<td valign="top">' . $companyName . '</td>';
                // midDay type checkboxes
                $content .= '<td valign="top" class="timeslotCheck1">';
                $content .= $userMidDayTypeCheckboxTemplate($dateData['pUserData']['id'], $dateData['timeslot'], BLOCK_EXAPLAN_MIDDATE_BEFORE);
                $content .= '</td>';
                $content .= '<td valign="top" class="timeslotCheck2">';
                $content .= $userMidDayTypeCheckboxTemplate($dateData['pUserData']['id'], $dateData['timeslot'], BLOCK_EXAPLAN_MIDDATE_AFTER);
                $content .= '</td>';
                // absend or not
                $absend = '';
                if ($relationData = isPuserIsFixedForDate($dateData['pUserData']['id'], $dateData['id'], true)) {
                    if ($relationData['absend']) {
                        $absend = ' checked = "checked" ';
                    }
                }
                $content .= '<td style="text-align: center;  vertical-align: top;">
                        <input type="checkbox" 
                                value="1"                                     
                                name="absendPuser[' . $dateData['pUserData']['id'] . ']" 
                                ' . $absend . '/></td>';
                $content .= '<td valign="top"><!--Bewertung--></td>';
            } else {
                // no related students for existing fixed/blocked date
                $content .= '<td></td>';
                $content .= '<td></td>';
                $content .= '<td></td>';
                $content .= '<td></td>';
                $content .= '<td></td>';
                $content .= '<td></td>';
                $content .= '<td></td>';
            }
            if ($rowsCount == 1) {
                $content .= '<td rowspan="###FORM_ROWSPAN###" class="mainForm" valign="top">'.formAdminDateFixing($modulepartId, $date, null, $defaultRegion).'</td>';
            }
            $content .= '</tr>';
        }
    } else {
        // show empty (no students) form to create an empty date
        $rowsCount++;
        $content .= '<tr>';
        $content .= '<td></td>';
        $content .= '<td></td>';
        $content .= '<td></td>';
        $content .= '<td class="timeslotCheck1"></td>';
        $content .= '<td class="timeslotCheck1"></td>';
        $content .= '<td></td>';
        $content .= '<td></td>';
        $content .= '<td rowspan="###FORM_ROWSPAN###" class="mainForm" valign="top">'.formAdminDateFixing($modulepartId, $date, null, $defaultRegion).'</td>';
        $content .= '</tr>';
    }

    $content .= '</table>';
    $content .= '</form>';

    $content = str_replace('###FORM_ROWSPAN###', $rowsCount, $content);


    return $content;
}


/**
 * inputs for main data of 'mdl_block_exaplandates'.
 * Look also inputs in function modulepartAdminViewByDate()
 * @param int $modulepartId
 * @param string $date
 * @param int $timeslot
 * @param string $defaultRegion
 * @return string
 */
function formAdminDateFixing($modulepartId, $date, $timeslot = null, $defaultRegion = '') {
    global $CFG;
    $content = '';
    $dateTs = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0)->getTimestamp();
    $instanceKey = $modulepartId.'_'.$dateTs/*.'_'.$timeslot*/;

    $dateRec = getPrefferedDate($modulepartId, $dateTs, $timeslot);

//    $content .= '<input type="hidden" value="'.$timeslot.'" name="middayType" />';
    $content .= '<input type="hidden" value="'.$date.'" name="date" />';
    $content .= '<input type="hidden" value="'.$modulepartId.'" name="mpId" />';

    $content .= '<table class="table table-sm table-borderless" border="0">';

    $content .= '<tr>';
    $content .= '<td colspan="2"><label for="trainer_'.$instanceKey.'">Trainer:</label></td>';
//    $content .= '<td><label for="region_'.$instanceKey.'">Region:</label></td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td colspan="2">';
    $trainers = block_exaplan_get_users_from_cohort();
    $content .= '<select id="trainer_'.$instanceKey.'" class="form-control" name="trainer">';
    foreach ($trainers as $trainer) {
        $trainerPid = getPuser($trainer->id);
        $content .= '<option value="'.$trainer->id.'" '.(@$dateRec['trainerpuserid'] == $trainerPid ? ' selected="selected" ' : '').'>'.fullname($trainer).'</option>'; // original ID (not pUser), because it is on MAIN moodle
    }
    $content .= '</select>';
    $content .= '</td>';
   /* $content .= '<td>';
    $selectedRegion = $defaultRegion;
    if (@$dateRec['region']) {
        $selectedRegion = $dateRec['region'];
    }
    $content .= '<select id="region_'.$instanceKey.'" class="form-control" name="region">';
    $content .= '<option value="all">Alle Regionen</option>';
    $content .= '<option value="RegionOst" '.($selectedRegion == 'RegionOst' ? 'selected="selected"' : '').'>Ost</option>';
    $content .= '<option value="RegionWest" '.($selectedRegion == 'RegionWest' ? 'selected="selected"' : '').'>West</option>';
    $content .= '</select>';
    $content .= '</td>';
    $content .= '<td>';
    $content .= '</td>';*/
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td><label for="location_'.$instanceKey.'">Location:</label></td>';
    $content .= '<td><label for="time_'.$instanceKey.'">Uhrzeit:</label></td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td><input type="text" name="location" value="'.@$dateRec['location'].'" class="form-control" id="location_'.$instanceKey.'" /></td>';
    $timeString = (@$dateRec['starttime'] ? date('H:i', @$dateRec['starttime']) : '00:00');
    $content .= '<td><input type="text" name="time" value="'.$timeString.'" class="form-control" id="time_'.$instanceKey.'" >';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td colspan="2">';
    $content .= '<textarea name="description" id="description_'.$instanceKey.'" class="form-control" placeholder="Notiz" >'.@$dateRec['comment'].'</textarea>';
    $content .= '</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td align="left">';
    if (!@$dateRec['state'] || @$dateRec['state'] == BLOCK_EXAPLAN_DATE_BLOCKED) {
        $content .= '<button name="date_block" class="btn btn-info" type="submit" value="date_block" >Termin blocken</button>';
    }
    $content .= '</td>';
    $content .= '<td align="right"><button name="date_save" class="btn btn-success" type="submit" value="date_save" >Kurs fixieren</button></td>';
    $content .= '</tr>';

    $content .= '</table>';

    return $content;
}

function studentEventDetailsView($userId, $modulepartId, $dateId) {
    $content = '';

    $puserId = getPuser($userId)['id'];

    if (!isPuserIsFixedForDate($puserId, $dateId)) {
        return 'No data!';
    }

    $content .= '<table class="table table-sm table-borderless exaplan-date-details-table">';

    $modulepartName = getTableData('mdl_block_exaplanmoduleparts', $modulepartId, 'title');
    $moduleId = getTableData('mdl_block_exaplanmoduleparts', $modulepartId, 'modulesetid');
    $moduleName = getTableData('mdl_block_exaplanmodulesets', $moduleId, 'title');
    $dateData = getTableData('mdl_block_exaplandates', $dateId);


    $content .= '<tr>';
    $content .= '<th>Sie planen: '.$moduleName.' | '.$modulepartName.'</th>';
    $content .= '<th>'.date('d.m.Y', $dateData['date']).'</th>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td class="dataLabel">Location:</td>';
    $content .= '<td class="dataContent">'.$dateData['location'].'</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td class="dataLabel">Uhrzeit:</td>';
    $content .= '<td class="dataContent">'.date('H:i', $dateData['starttime']).'</td>';
    $content .= '</tr>';

    $content .= '<tr>';
    $content .= '<td class="dataLabel">Skillswork-Trainer:</td>';
    $trainer = getTableData('mdl_block_exaplanpusers', $dateData['trainerpuserid']);
    $content .= '<td class="dataContent">'.@$trainer['firstname'].' '.@$trainer['lastname'].'</td>';
    $content .= '</tr>';

    if ($dateData['comment']) {
        $content .= '<tr>';
        $content .= '<td colspan="2"><strong>Notiz:</strong><br>'.$dateData['comment'].'</td>';
        $content .= '</tr>';
    }

    $content .= '</table>';

    return $content;
}

function printAdminDashboard($dashboardType = 'default')
{
    global $CFG, $PAGE;
    $content = '';

    $modulesets = getAllModules();

    switch ($dashboardType) {
        case 'inProcess':
            $dashoboardTitle = 'Übersicht: in Bearbeitung';
            break;
        case 'past':
            $dashoboardTitle = 'Übersicht: zurückliegende Termine';
            break;
        case 'default':
        default:
            $dashoboardTitle = 'Übersicht Anfragen';
            break;
    }


    $content .= '<div class="exaplan-result-item">';

    $content.= '<div class="UserBlock">';
    $content .= '<div class="BlockHeader">';

    $content .= '</div>';
    $content .= '<div class="BlockBody">';
    $content .= '<table class="mainTable" border="0">';
    $content .= '<thead>';
    $content .= '<tr>';
    $content .= '<th colspan="3">
                    <div class="result-item-header">
                        <div class="result-item-header-cnt">                                                    
                            <h5 class="item-header-title">'.$dashoboardTitle.'</h5>   	                        	
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
    $content .= '<th rowspan="2" valign="top">Meine Module</th>';
    $content .= '<th rowspan="2" valign="top">Termine</th>';
    $content .= '<th colspan="3">Anzahl Teilnehmer angefragt:</th>';
    $content .= '</tr>';
    $content .= '<tr>';
    $content .= '<th class="regionColumn">'.getRegionTitle('RegionOst').'</th>';
    $content .= '<th class="regionColumn">'.getRegionTitle('RegionWest').'</th>';
    $content .= '<th class="regionColumn">'.getRegionTitle('').'</th>';
    $content .= '</tr>';
    $content .= '</thead>';

    $regions = ['RegionOst', 'RegionWest', 'all'];

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
        $content .= '<tr> <td valign="top" rowspan="'.count($moduleset->parts).'">'.$moduleset->set["title"].'</td>';
        foreach($moduleset->parts as $partK => $part) {
            if ($partK != 0) {
                $content .= '<tr>';
            }
            $content .= '<th>'.$part["title"].'</th>';
            foreach ($regions as $region) {
                $content .= '<td class="regionColumn">';
                $buttonClass = '';
                switch ($dashboardType) {
                    case 'inProcess':
                        // existing fixed / blocked dates (in the future)
//                        $fixedDates = getFixedDatesAdvanced(null, $part['id'], null, null, true, $region, 'future');
                        $fixedDates = getDatesForModulePart($part['id'], null, $region, 'future');
                        if (count($fixedDates) > 0) {
                            $buttonClass .= ' exaplan-date-fixed ';
                            foreach ($fixedDates as $fixedDate) {
                                if ($fixedDate['dateType'] == 'blocked') {
                                    $buttonClass = ' exaplan-date-blocked ';
                                }
                                $content .= $buttonTemplate($part['id'], $region, date('d.m.Y', $fixedDate['date']), $buttonClass, $fixedDate['date']).'&nbsp;';
                            }
                        }
                        break;
                    case 'past':
                        // fixed dates in past
//                        $fixedDates = getFixedDatesAdvanced(null, $part['id'], null, null, false, $region, 'past');
                        $fixedDates = getDatesForModulePart($part['id'], null, $region, 'past');
                        if (count($fixedDates) > 0) {
                            $buttonClass .= ' exaplan-date-fixed ';
                            foreach ($fixedDates as $fixedDate) {
                                $content .= $buttonTemplate($part['id'], $region, date('d.m.Y', $fixedDate['date']), $buttonClass, $fixedDate['date']).'&nbsp;';
                            }
                        }
                        break;
                    case 'default':
                        // desired dates
                        $desiredDates = getDesiredDates(null, $part['id'], null, null, $region);
                        if (count($desiredDates) > 0) {
                            // get count of unique pUsers
                            $desiredDatesUsers = count(array_unique(array_column($desiredDates, 'puserid')));
                            $title = $desiredDatesUsers . ' Anfragen';
                            $buttonClass .= ' exaplan-date-desired ';
                            $content .= $buttonTemplate($part['id'], $region, $title, $buttonClass) . '&nbsp;';
                        }
                        // button to add new fixed date
                        $title = ' - - ';
                        $buttonClass .= ' exaplan-date-no-desired ';
                        $content .= $buttonTemplate($part['id'], $region, $title, $buttonClass);
                        break;
                }

                $content .= '</td>';
            }
            if ($partK != 0 || count($moduleset->parts) == 1) {
                $content .= '</tr>';
            }
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
        case 'inProcess':
            $content .= '<a href="'.$PAGE->url.'?dashboardType=default" role="button" class="btn btn-info"> Übersicht Anfragen </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType=past" role="button" class="btn btn-info"> Übersicht: zurückliegende Termine </a>&nbsp;';
            break;
        case 'past':
            $content .= '<a href="'.$PAGE->url.'?dashboardType=default" role="button" class="btn btn-info"> Übersicht Anfragen </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType=inProcess" role="button" class="btn btn-info"> Übersicht: in Bearbeitung </a>&nbsp;';
            break;
        case 'default':
        default:
            $content .= '<a href="'.$PAGE->url.'?dashboardType=inProcess" role="button" class="btn btn-info"> Übersicht: in Bearbeitung </a>&nbsp;';
            $content .= '<a href="'.$PAGE->url.'?dashboardType=past" role="button" class="btn btn-info"> Übersicht: zurückliegende Termine </a>&nbsp;';
            break;
    }
    $content .= '</div>';

    $content .= '</div>';
    $content .= '</div><!-- / exaplan-result-item --->';
    return $content;
}
