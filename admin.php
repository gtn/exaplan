<?php

require __DIR__.'/inc.php';

global $CFG, $PAGE, $OUTPUT, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title("Terminplaner");
$PAGE->set_heading("Terminplaner");
$PAGE->set_url($CFG->wwwroot.'/blocks/exaplan/admin.php');

block_exaplan_init_js_css();

require_login();

$action = optional_param("action", "", PARAM_TEXT);
$modulepartid = optional_param("mpid", "", PARAM_INT);
$region = optional_param("region", "", PARAM_TEXT);
$dashboardType = optional_param("dashboardType", '', PARAM_TEXT);
$dateId = optional_param("dateId", 0, PARAM_INT);

$isadmin = block_exaplan_is_admin();

$userid = $USER->id;

switch ($action) {
    case 'saveFixedDates':
        // create 'mdl_block_exaplandates' record and relate selected students to it
        $pUserId = getPuser($userid)['id'];
//        $middayType1 = optional_param_array("middayType".BLOCK_EXAPLAN_MIDDATE_BEFORE, [], PARAM_INT);
//        $middayType1 = array_keys($middayType1);
//        $middayType2 = optional_param_array("middayType".BLOCK_EXAPLAN_MIDDATE_AFTER, [], PARAM_INT);
//        $middayType2 = array_keys($middayType2);
        $date = required_param("date", PARAM_TEXT);
        $dateTS = DateTime::createFromFormat('Y-m-d', $date)->getTimestamp();
        $dateTSstart = strtotime("today", $dateTS); //same tstamp for whole day
        $students = optional_param_array('fixedPuser', [], PARAM_INT);
        $students = array_keys($students);
        $moodleid = optional_param('moodleid', '', PARAM_INT);
        $isonline = optional_param('isonline', '', PARAM_TEXT);
        $location = optional_param('location', '', PARAM_TEXT);
        $eventTime = optional_param('time', '', PARAM_TEXT);
        $duration = optional_param('duration', '', PARAM_TEXT);
        $description = optional_param('description', '', PARAM_TEXT);
//        $trainerId = optional_param('trainer', 0, PARAM_INT);
//        $pTrainer = getPuser($trainerId)['id'];
        $pTrainer = optional_param('trainer', 0, PARAM_INT);
        $dateRegion = optional_param('dateRegion', 'all', PARAM_TEXT);

        // fixed date or blocked date
        $state = BLOCK_EXAPLAN_DATE_DESIRED; // old. never accessible?
        $sendNotificationToStudent = true;
        if (optional_param('date_save', '', PARAM_TEXT)) {
            $state = BLOCK_EXAPLAN_DATE_FIXED;
        }
        if (optional_param('date_block', '', PARAM_TEXT)) {
            $state = BLOCK_EXAPLAN_DATE_BLOCKED;
            $sendNotificationToStudent = false;
        }
        if (optional_param('date_cancel', '', PARAM_TEXT)) {
            $state = BLOCK_EXAPLAN_DATE_CANCELED;
            $sendNotificationToStudent = false;
        }


        $modulepart = getModulepartByModulepartid($modulepartid);
        $moduleset = getModulesetByModulesetid($modulepart["modulesetid"]);
//        $absents = optional_param_array('absentPuser', [], PARAM_INT);
//        $absents = array_keys($absents);

        $isBulkAction = false;
        if ($bulkGo = optional_param('bulk_go', '', PARAM_TEXT)) {
            $isBulkAction = true;
        }

        if ($isBulkAction && $dateId) {
            $dateData = getTableData('mdl_block_exaplandates', $dateId);
            $bulkAction = required_param('bulk_function', PARAM_TEXT);
            switch ($bulkAction) {
                case 'studentsAdd':
                    if ($students && count($students)) {
                        foreach ($students as $student) {
                            $absent = 0; // add with absent = 0
                            addPUserToDate($dateId, $student, $absent, $pUserId, $date, $moduleset, $modulepart, true, $sendNotificationToStudent);
                            // delete (disable) ALL other desired dates (not for 'blocked' dates)
                            if ($dateData['state'] != BLOCK_EXAPLAN_DATE_BLOCKED) {
                                removeDesiredDate($modulepartid, $student);
                            }
                        }
                    }
                    break;
                case 'studentsRemove':
                    if ($students && count($students)) {
                        foreach ($students as $student) {
                            removePUserFromDate($dateId, $student, $modulepartid);
                            // send messages
                            $pUserData = getTableData('mdl_block_exaplanpusers', $student);
                            $text = 'Lieber '.$pUserData['firstname'].', du wurdest im Kurs '.getFixedDateTitle($dateId).' ausgetragen. Bitte mach jetzt folgendes:';
                            block_exaplan_create_plannotification($pUserId, $student, $text);
                        }
                    }
                    break;
                case 'studentsAbsent':
                    // if the user is not linked yet to this date - it will be linked now and setted up absent = 1
                    if ($students && count($students)) {
                        $sendNotificationToStudent = false; // TODO: message?
                        foreach ($students as $student) {
                            // set absent for the student
                            $absent = 1;
                            addPUserToDate($dateId, $student, $absent, $pUserId, $date, $moduleset, $modulepart, true, $sendNotificationToStudent);
                            // restore his desired dates
                            restoreDesiredDates($modulepartid, $student);
                        }
                    }
                    break;
                case 'sendMessage':
                    $bulkMessage = optional_param('bulk_message', '', PARAM_TEXT);
                    if ($bulkMessage && $students && count($students)) {
                        foreach ($students as $student) {
                            block_exaplan_create_plannotification($pUserId, $student, $bulkMessage);
                        }
                    }
                    break;
            }
        } else {
            // get timeslot for fixed date
            // get from selected (1, 2 or both)
            // selected types are from database. not from html form
            $useFormMiddateTypes = false;
            $middayTypes = [];
            foreach ($students as $student) {
                $desiredDate = getDesiredDates($student, $modulepartid, $dateTSstart);
                if ($middayTypes) { // if this user already has not desired dates for this modulepart - it is fix date editing.
                    $useFormMiddateTypes = true;
                    $middayTypes[] = $desiredDate[0]['timeslot'];
                }
            }
            if ($useFormMiddateTypes) {
                // found at least one timeslot
                $middayTypes = array_unique($middayTypes);
                if (!$middayTypes || count($middayTypes) > 1) {
                    $middayType = BLOCK_EXAPLAN_MIDDATE_ALL;
                } else {
                    $middayType = reset($middayTypes);
                }
            } else {
                // get from existing date
                $tempDate = getPrefferedDate($modulepartid, $dateTS);
                if ($tempDate) {
                    // use existing timeslot
                    $middayType = $tempDate['timeslot'];
                } else {
                    // new date. use default
                    $middayType = BLOCK_EXAPLAN_MIDDATE_ALL;
                }
            }

            if ($state == BLOCK_EXAPLAN_DATE_CANCELED) {
                // if we set 'canceled' state:
                // - set state for fixed date
                // - remove blocked date at all
                if ($dateId) {
                    $dateState = getFixedDateState($dateId);
                    if (in_array($dateState, [BLOCK_EXAPLAN_DATE_FIXED, BLOCK_EXAPLAN_DATE_BLOCKED])) {
                        // unlink students
                        $registeredUsers = getFixedPUsersForDate($dateId);
                        $registeredUsersIds = array_map(function ($u) {return $u['puserid']; }, $registeredUsers);
                        foreach ($registeredUsersIds as $studentId) {
                            removePUserFromDate($dateId, $studentId, $modulepartid);
                            if ($dateState == BLOCK_EXAPLAN_DATE_FIXED) {
                                // send messages
                                $pUserData = getTableData('mdl_block_exaplanpusers', $studentId);
                                $text = 'Lieber ' . $pUserData['firstname'] . ', leider wurde der Kurs ' . getFixedDateTitle($dateId) . ' abgesagt. Deine Planung ist noch da, bitte plane den Rest neu.';
                                block_exaplan_create_plannotification($pUserId, $studentId, $text);
                            }
                        }
                        if ($dateState == BLOCK_EXAPLAN_DATE_FIXED) {
                            // set 'canceled'
                            $dateId = setPrefferedDate(true, $dateId, $modulepartid, $pUserId, $dateTS, $middayType, $location, $pTrainer, $eventTime, $description, $dateRegion, $moodleid, $isonline, $duration, BLOCK_EXAPLAN_DATE_CANCELED);
                        } elseif ($dateState == BLOCK_EXAPLAN_DATE_BLOCKED) {
                            // remove date if not any user (must be no one)
                            removeDateIfNoUsers($dateId);
                        }
                    }
                }
            } else {
                // create/update date record
                $selectedDateId = $dateId;
                $dateId = setPrefferedDate(true, $dateId, $modulepartid, $pUserId, $dateTS, $middayType, $location, $pTrainer, $eventTime, $description, $dateRegion, $moodleid, $isonline, $duration, $state);
                // if it is a new date record - add selected students
                if (in_array($state, [BLOCK_EXAPLAN_DATE_FIXED, BLOCK_EXAPLAN_DATE_BLOCKED]) && $selectedDateId != $dateId) { // creating of new FIXED date
                    if ($students && count($students)) {
                        foreach ($students as $student) {
                            addPUserToDate($dateId, $student, 0, $pUserId, $date, $moduleset, $modulepartid, true, $sendNotificationToStudent);
                            removeDesiredDate($modulepartid, $student);
                        }
                    }
                }

            }

            // add selected users only during creating of NEW fixed date
            // for existing dates - this action moved to bulk actions
            if ($state == BLOCK_EXAPLAN_DATE_FIXED) {
                // change exists fixed date:
                $registeredUsers = getFixedPUsersForDate($dateId);
                /*$registeredUsersIds = array_map(function ($u) {
                    return $u['puserid'];
                }, $registeredUsers);
                foreach ($absents as $sId => $absentVal) {
                    // change absent only for already registered users
                    if (in_array($sId, $registeredUsersIds)) {
                        addPUserToDate($dateId, $sId, $absentVal, $pUserId, $date, $moduleset, $modulepart, true, false);
                    }
                }*/
                $dateData = getTableData('mdl_block_exaplandates', $dateId);
                // send messages to students
                foreach ($registeredUsers as $registeredStudent) {
                    $pUserData = getTableData('mdl_block_exaplanpusers', $registeredStudent['puserid']);
                    $trainerData = getTableData('mdl_block_exaplanpusers', $dateData['trainerpuserid']);
                    $text = 'Lieber ' . $pUserData['firstname'] . ', der Kurs ' . getFixedDateTitle($dateId) . ' hat sich geändert, hier sind die neuen Kursdaten: ' . "\r\n" .
                        'DF Ort: ' . getTableData('mdl_block_exaplanmoodles', $dateData['moodleid'], 'companyname') . "\r\n" .
                        'Region: ' . getRegionTitle($dateData['region']) . "\r\n" .
                        'DF Art: ' . getIsOnlineTitle($dateData['isonline']) . "\r\n" .
                        'Trainer: ' . $trainerData['firstname'] . ' ' . $trainerData['lastname'] . "\r\n" .
                        'Location: ' . $dateData['location'] . "\r\n" .
                        'Uhrzeit: ' . date('H:i', $dateData['starttime']) . "\r\n" .
                        'Dauer: ' . $dateData['duration'] . "\r\n" .
                        'Notiz: ' . $dateData['comment'] . "\r\n";
                    block_exaplan_create_plannotification($pUserId, $registeredStudent['puserid'], $text);
                }
            }
            /*if ($state == BLOCK_EXAPLAN_DATE_CONFIRMED) {
                // register / unregister students
                $registeredUsers = getFixedPUsersForDate($dateId);
                $registeredUsersIds = array_map(function ($u) {
                    return $u['puserid'];
                }, $registeredUsers);
                foreach ($students as $student) {
                    if (($key = array_search($student, $registeredUsersIds)) !== false) {
                        unset($registeredUsersIds[$key]);
                    }
                    $absent = 0;
                    if (in_array($student, $absents)) {
                        $absent = 1;
                    }
                    addPUserToDate($dateId, $student, $absent, $pUserId, $date, $moduleset, $modulepart, true, $sendNotificationToStudent);
                    // delete ALL other desired dates (not for 'blocked' dates)
                    removeDesiredDate($modulepartid, $student);
                }
                // unregister if it was unchecked
                if ($registeredUsersIds && count($registeredUsersIds) > 0) {
                    foreach ($registeredUsersIds as $puserid) {
                        removePUserFromDate($dateId, $puserid, $modulepartid);
                    }
                }
            }*/
            $dateId = 0; // unlink shown form from current dateId. So the admin will be able to create a new date instead of edit it
        }

        // redirect to admin view (without this we have wrong shown data)
        $params = array('mpid' => $modulepartid, 'date' => $date, 'region' => $region, 'dashboardType' => $dashboardType);
        if ($dateId) {
            $params['dateId'] = $dateId;
        }
        $url = new moodle_url('/blocks/exaplan/admin.php', $params);
        redirect($url);
        break;

}


echo $OUTPUT->header();

echo '<div id="exaplan">';

if ($isadmin) {
    $dateGP = optional_param('date', '', PARAM_TEXT);
    echo printAdminModulepartView($modulepartid, $dateGP, $region, $dateId);
}

echo '<a href="'.$CFG->wwwroot.'/my/'.($dashboardType ? '?dashboardType='.$dashboardType : '').'" role="button" class="btn btn-info btn-to-dashboard"> zurück zum Dashboard </a>';

echo '</div>';

echo $OUTPUT->footer();
