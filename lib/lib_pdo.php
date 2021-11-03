<?php
// This file is part of Exabis Planning Tool
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Planning Tool is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This script is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You can find the GNU General Public License at <http://www.gnu.org/licenses/>.
//
// This copyright notice MUST APPEAR in all copies of the script!

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../config.php';


//global $dbname, $dbusername, $dbpassword;

global $CFG;
// RW: it works like this, but why?
// SZ: in function getPdoConnect(): dbname/dbuser/dbpassword from config.php will be used. If they are not working - will be used global moodle $CFG values
// it is useful for development on the single Moodle installation.
// if you have correct Moodle installtions and exaplan/config.php - all must work ok
$CFG->centraldbname = $dbname;
$CFG->centraldbusername = $dbusername;
$CFG->centraldbpassword = $dbpassword;
$CFG->centralsaltuserstring = $saltuserstring;
// this is needed because otherwise in cron tasks $dbname etc does not exist


function getPdoConnect()
{
    global $CFG, $dbname, $dbusername, $dbpassword;
    require_once __DIR__ . '/../config.php';
    // only for SZ developer server:
    if (isset($CFG->uniqueMoodleIdentificator) && $CFG->uniqueMoodleIdentificator == 'dfldf8dfh784hj484489045b4590tuydldfg954u4lf') {
        $dbname = $CFG->dbname;
        $dbusername = $CFG->dbuser;
        $dbpassword = $CFG->dbpass;
    };
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=' . $dbname, $dbusername, $dbpassword); // TODO: constant, global?
    } catch (Exception $e) {
        // use global parameters
        $pdo = new PDO('mysql:host=localhost;dbname=' . $CFG->centraldbname, $CFG->centraldbusername, $CFG->centraldbpassword);
    }
    return $pdo;
}

function getTableData($tableName, $id, $field = null)
{
    global $CFG;
    // TODO: make static variable for performance?
    $pdo = getPdoConnect();

    $params = array(
        ':id' => $id,
    );

    if (strpos($tableName, 'mdl_') === false) {
        $tableName = $CFG->prefix . $tableName; // for using with constants or other cases
    }

    $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = :id ';
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $data = $statement->fetchAll();
    if ($data && count($data) > 0) {
        $data = $data[0];
        if ($field) {
            if (array_key_exists($field, $data)) {
                return $data[$field];
            }
            return null;
        }
        return $data;
    }
    return null;
}

function getPuser($userid = 0)
{
    static $getAttempts = null;
    if ($getAttempts === null) {
        $getAttempts = array();
    }
    if (!array_key_exists($userid, $getAttempts)) {
        $getAttempts[$userid] = 0;
    }

    $pdo = getPdoConnect();

    $params = array(
        ':userid' => $userid,
        ':moodleid' => get_config('exaplan', 'moodle_id'),
    );

    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanpusers WHERE userid = :userid AND moodleid = :moodleid ");
    $statement->execute($params);
    $user = $statement->fetchAll();
    if (!$user || !count($user)) {
        // create a new pUser
        if (getOrCreatePuser($userid)) {
            $getAttempts[$userid]++;
            if ($getAttempts[$userid] > 3) { // ONLY FOR development server - NO such user in the server
                return ['id' => 0, 'firstname' => 'TEST', 'lastname' => 'USER', 'email' => ''];
            }
            return getPuser($userid); // get again
        }
        echo 'Can not find a user! 1634892218074';
        exit;
    }
    return $user[0];
}


function getOrCreatePuser($userid = 0)
{
    global $USER, $DB;
    if ($userid == 0) {
        $userid = $USER->id;
        $firstname = $USER->firstname;
        $lastname = $USER->lastname;
        $email = $USER->email;
    } elseif ($userid > 0) {
        $user = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING);
        $firstname = $user->firstname;
        $lastname = $user->lastname;
        $email = $user->email;
    } else {
        return false;
    }

    $region = block_exaplan_get_user_regioncohort($userid);

    $pdo = getPdoConnect();

    $params = array(
        ':userid' => $userid,
        ':moodleid' => get_config('exaplan', 'moodle_id'),
    );


    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanpusers WHERE userid = :userid AND moodleid = :moodleid");
    $statement->execute($params);
    $user = $statement->fetchAll();
    if ($user == null) {
        $params = array(
            ':userid' => $userid,
            ':moodleid' => get_config('exaplan', 'moodle_id'),
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':email' => $email,
            ':region' => $region,
        );

        $statement = $pdo->prepare("INSERT INTO mdl_block_exaplanpusers (userid, moodleid, firstname, lastname, email, region) VALUES (:userid, :moodleid, :firstname,:lastname, :email, :region);");
        $statement->execute($params);
        return $pdo->lastInsertId();
    } else {
        return $user[0]['id'];
    }
}

function getAllModules()
{

    $modulesets = array();

    $modules = array();

    $pdo = getPdoConnect();
    $params = array();

    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmodulesets");
    $statement->execute($params);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $modules = $statement->fetchAll();

    foreach ($modules as $module) {
        $moduleset = new \stdClass;
        $moduleset->set = $module;
        $params = array(
            ':modulesetid' => $moduleset->set['id'],
        );
        $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmoduleparts WHERE modulesetid = :modulesetid");
        $statement->execute($params);
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $moduleset->parts = $statement->fetchAll();
        $modulesets[] = $moduleset;
    }

    return $modulesets;

}

function getModulepartByModulepartid($modulepartid)
{
    $pdo = getPdoConnect();
    $params = array(
        ':id' => $modulepartid,
    );

    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmoduleparts WHERE id = :id");
    $statement->execute($params);
    $modulepart = $statement->fetch();

    return $modulepart;
}

function getModulesetByModulesetid($modulesetid){
    $pdo = getPdoConnect();
    $params = array(
        ':id' => $modulesetid,
    );

    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmodulesets WHERE id = :id");
    $statement->execute($params);
    $moduleset = $statement->fetch();

    return $moduleset;
}

function getModulesOfUser($userid, $state = BLOCK_EXAPLAN_DATE_CONFIRMED)
{
    global $DB, $COURSE;


    $modulesets = array();
    $pdo = getPdoConnect();

    $courses = $DB->get_records('course');
    foreach ($courses as $course) {
        if ($course->idnumber > 0) {
            $context = context_course::instance($course->id);
            if (is_enrolled($context, $userid, '', true)) {
                $moduleset = new \stdClass;
                $params = array(
                    ':courseidnumber' => $course->idnumber,
                );
                $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmodulesets WHERE courseidnumber = :courseidnumber");
                $statement->execute($params);
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $modules = $statement->fetchAll();
                if (!(is_array($modules) && count($modules) > 0)) {
                    continue;
                }
                $moduleset->set = $modules[0];

                $params = array(
                    ':modulesetid' => $moduleset->set['id'],
                );
                $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmoduleparts WHERE modulesetid = :modulesetid");
                $statement->execute($params);
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                $moduleset->parts = $statement->fetchAll();

                $dates = array();
                foreach ($moduleset->parts as $key => $part) {
                    $params = array(
                        ':modulepartid' => $part['id'],
                        ':puserid' => getPuser($userid)['id'],
                        ':state' => $state,
                    );
                    $statement = $pdo->prepare("SELECT d.*
                                                  FROM mdl_block_exaplandates d
                                                    JOIN mdl_block_exaplanpuser_date_mm udmm ON d.id = udmm.dateid
                                                  WHERE d.modulepartid = :modulepartid
                                                        AND udmm.puserid = :puserid
                                                        AND d.state = :state");
                    $statement->execute($params);
                    $statement->setFetchMode(PDO::FETCH_ASSOC);
                    $date = $statement->fetchAll();
                    $moduleset->parts[$key]['date'] = $date;
                }
                $modulesets[] = $moduleset;

            }
        }
    }
    return $modulesets;
}

function getPrefferedDate($modulepartid, $date, $timeslot = null, $states = [])
{
    $pdo = getPdoConnect();

    $params = array(
        ':modulepartid' => $modulepartid,
        ':date' => $date,
    );
    if ($timeslot) {
        $params[':timeslot'] = $timeslot;
    }

    // get existing data for this modulepartid, date, timeslot
    $sql = "SELECT *
              FROM mdl_block_exaplandates
              WHERE modulepartid = :modulepartid
                ".($timeslot ? ' AND timeslot = :timeslot ' : '')."
                AND date = :date";
    if ($states) {
        $sql .= ' AND state IN ('.implode(',', $states).') ';
    }
    $statement = $pdo->prepare($sql);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $statement->execute($params);
    $dates = $statement->fetchAll();

    if ($dates && count($dates) > 0) {
        return $dates[0];
    }

    return null;
}

/**
 * @param bool $updateExisting Need to update?
 * @param int $modulepartid
 * @param int $puserid Puser id
 * @param int $date
 * @param int $timeslot
 * @param string $location
 * @param int $trainerId pUser!
 * @param string $starttime
 * @param string $comment
 * @param string $region
 * @return string
 */
function setPrefferedDate($updateExisting, $modulepartid, $puserid, $date, $timeslot, $location, $trainerId, $starttime, $comment, $region, $state = BLOCK_EXAPLAN_DATE_PROPOSED)
{
    $pdo = getPdoConnect();
    $timestamp = new DateTime();
    $timestamp = $timestamp->getTimestamp();
    $date = strtotime("today", $date); //same tstamp for whole day

    $params = [
        ':modulepartid' => $modulepartid,
        ':date' => $date,
        ':timeslot' => $timeslot,
        ':state' => $state,
        ':modifiedpuserid' => $puserid,
        ':modifiedtimestamp' => $timestamp,
        ':location' => $location,
        ':trainerpuserid' => $trainerId,
        ':starttime' => (strtotime(date('Y-m-d', $date) . ' ' . $starttime) ?: strtotime('today midnight')), // day's midninght if no time in the form!
        ':comment' => trim($comment),
        ':region' => trim($region),
    ];

    $dateRec = getPrefferedDate($modulepartid, $date, $timeslot, [$state]);

    if ($dateRec) {
        $dateId = $dateRec['id'];
        
        // return existing dateId. We do not need to create it
        if ($updateExisting) {
            unset($params[':modulepartid']);
            unset($params[':date']);
            unset($params[':state']);
            unset($params[':timeslot']); // for leave PHP warnings
            $sql = "UPDATE mdl_block_exaplandates
                        SET modifiedpuserid = :modifiedpuserid,
                             modifiedtimestamp = :modifiedtimestamp,
                             location = :location,
                             trainerpuserid = :trainerpuserid,
                             starttime = :starttime,
                             comment = :comment,
                             region = :region
                        WHERE id = " . $dateId . ";";
            $statement = $pdo->prepare($sql);
            $statement->execute($params);
        }
        return $dateId;
    }

    $params = array_merge($params, [
        ':creatorpuserid' => $puserid,
        ':creatortimestamp' => $timestamp,
    ]);

    $sql = "INSERT INTO mdl_block_exaplandates
                        (modulepartid, date, timeslot, state, creatorpuserid, creatortimestamp, modifiedpuserid, modifiedtimestamp, location, trainerpuserid, starttime, comment, region)
                  VALUES (:modulepartid, :date, :timeslot, :state, :creatorpuserid, :creatortimestamp, :modifiedpuserid, :modifiedtimestamp, :location, :trainerpuserid, :starttime, :comment, :region);";
//    echo "<pre>debug:<strong>lib_pdo.php:297</strong>\r\n"; print_r($params); echo '</pre>'; // !!!!!!!!!! delete it
//    echo $sql; exit;
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $dateid = $pdo->lastInsertId();

    return $dateid;

}

/**
 * set desired date. DELETE if it is existing
 * @param int $modulepartid
 * @param int $puserid
 * @param int $date
 * @param int $timeslot
 * @param int $creatorpuserid
 * @return int|string
 */
function setDesiredDate($modulepartid, $puserid, $date, $timeslot, $creatorpuserid = null)
{
    $pdo = getPdoConnect();
    $timestamp = new DateTime();
    $timestamp = $timestamp->getTimestamp();
    $date = strtotime("today", $date); //same tstamp for whole day
    $params = array(
        ':modulepartid' => $modulepartid,
        ':date' => $date,
//        ':timeslot' => $timeslot, // no timeslot checking. The pUser can have only single desired dat for any timeslot
        ':puserid' => $puserid
    );

    // get existing data for this modulepartid, date, timeslot
    //    deleted: AND timeslot = :timeslot
    $sql = "SELECT *
              FROM mdl_block_exaplandesired
              WHERE modulepartid = :modulepartid                
                AND date = :date
                AND puserid = :puserid";
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $dates = $statement->fetchAll();
    $dateid = 0;
    $addNew = false;
    if ($dates) {
        if ($dates[0]['timeslot'] != $timeslot) {
            // 1. delete old record with this timeslot
            // 2. add new record with the new timeslot
            $addNew = true;
        }
        $statement = $pdo->prepare('DELETE FROM mdl_block_exaplandesired WHERE id = :id');
        $statement->execute([':id' => $dates[0]['id']]);
    } else {
        $addNew = true;
    }
    if ($addNew) {
        $params = array_merge($params, [
            ':modulepartid' => $modulepartid,
            ':date' => $date,
            ':puserid' => $puserid,
            ':timeslot' => $timeslot,
            ':creatorpuserid' => $creatorpuserid,
            ':timestamp' => $timestamp,
        ]);
        $sql = "INSERT INTO mdl_block_exaplandesired (modulepartid, date, puserid, timeslot, creatorpuserid, timestamp) VALUES (:modulepartid, :date, :puserid, :timeslot, :creatorpuserid, :timestamp)";
        //  echo $sql; exit;
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $dateid = $pdo->lastInsertId();
    }
    return $dateid;
}

/**
 * remove all desired dates for the user and module part
 * @param int $modulepartid
 * @param int $puserid
 * @return bool
 */
function removeDesiredDate($modulepartid, $puserid)
{
    if (!$modulepartid || !$puserid) {
        return true;
    }
    $pdo = getPdoConnect();
    $params = [
        ':modulepartid' => $modulepartid,
        ':puserid' => $puserid,
    ];
    $statement = $pdo->prepare('DELETE FROM mdl_block_exaplandesired WHERE modulepartid = :modulepartid AND puserid = :puserid');
    $statement->execute($params);
    return true;
}

/**
 * @param $dateid
 * @param $puserid
 * @param int $absend
 * @param int $creatorpuserid
 * @param string $date
 * @param int $moduleset
 * @param int $modulepart
 * @param bool $withUpdating
 * @param bool $sendNotification
 * @return mixed|string
 */
function addPUserToDate($dateid, $puserid, $absend = 0, $creatorpuserid=null, $date=null, $moduleset=null, $modulepart=null, $withUpdating = false, $sendNotification = true)
{
    global $USER;

    $pdo = getPdoConnect();

    $params = array(
        ':dateid' => $dateid,
        ':puserid' => $puserid,
    );

    // get existing data for this modulepartid, date, timeslot
    $sql = "SELECT *
              FROM mdl_block_exaplanpuser_date_mm
              WHERE dateid = :dateid
                AND puserid = :puserid
                ";
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $existing = $statement->fetchAll();
    if ($existing) {
        // relation is already existing!
        if ($withUpdating) {
            $statement = $pdo->prepare("UPDATE mdl_block_exaplanpuser_date_mm SET absend = :absend;");
            $statement->execute([':absend' => $absend]);
        }
        return $existing[0]['id'];
    }

    $creatorpUserid = getPuser($USER->id)['id'];

    // create a new relation
    $params = array_merge($params, [
            ':creatorpuserid' => $creatorpUserid,
            ':absend' => $absend,
        ]
    );
    $statement = $pdo->prepare("INSERT INTO mdl_block_exaplanpuser_date_mm (dateid, puserid, creatorpuserid, absend) VALUES (:dateid, :puserid, :creatorpuserid, :absend);");
    $statement->execute($params);
    $id = $pdo->lastInsertId();

    // create notification for users
    if ($creatorpuserid && $date && $moduleset && $modulepart && $sendNotification) {
        block_exaplan_create_plannotification($creatorpuserid, $puserid, "Termin für Modulset ".$moduleset["title"].", Modulteil ".$modulepart["title"]." fixiert für ".$date.".");
    }


    return $id;
}

function removePUserFromDate($dateid, $puserid)
{
    $pdo = getPdoConnect();
    $params = [
        ':dateid' => $dateid,
        ':puserid' => $puserid,
//            ':creatorpuserid' => $puserid, // TODO: what if this relation was not self created?
    ];
    $statement = $pdo->prepare("DELETE FROM mdl_block_exaplanpuser_date_mm WHERE dateid = :dateid AND puserid = :puserid;");
    $statement->execute($params);
}

function removeDateIfNoUsers($dateid)
{
    $pdo = getPdoConnect();
    // get related to 'date' users
    $params = [':dateid' => $dateid];
    $sql = "SELECT * FROM mdl_block_exaplanpuser_date_mm WHERE dateid = :dateid";
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $existing = $statement->fetchAll();
    if (!$existing) {
        // no related users - delete this dateid
        $statement = $pdo->prepare("DELETE FROM mdl_block_exaplandates WHERE id = :dateid;");
        $statement->execute($params);
    }
}

function updateNotifications()
{
    $pdo = getPdoConnect();
    $params = array(
        ':moodleid' => get_config('exaplan', 'moodle_id')
    );

    // pu.id is the puserid used for the notification in the centralmoodle
    // pu.userid is the userid in the foreignmoodle
    $statement = $pdo->prepare("
        SELECT pu.userid as userto, n.notificationtext, n.id
        FROM mdl_block_exaplannotifications as n
        JOIN mdl_block_exaplanpusers as pu ON pu.id = n.puseridto
        WHERE n.moodlenotificationcreated = false
        AND pu.moodleid = :moodleid
     ");

    $statement->execute($params);

    $plannotifications = $statement->fetchAll();

    // iterate over all notifications that have not been used for creating moodle notifications and create them
    foreach ($plannotifications as $pn) {
        // userfrom = 2 because 2 is always admin
        block_exaplan_send_moodle_notification("datefixed", 2, $pn["userto"], "Termin fixiert", $pn["notificationtext"], "Termin");

        // set the moodlenotificationcreated to true
        $statement = $pdo->prepare("
            UPDATE mdl_block_exaplannotifications
            SET moodlenotificationcreated = 1
            WHERE id = :id;
        ");
        $statement->execute(array(':id' => $pn['id']));
    }
}



/**
 * @param int $puserid (null id needed data about whole modulepart)
 * @param int $modulepartid (null if needed data about all moduleparts)
 * @param string|int $date (null if for all dates)
 * @param int $timeslot midday type
 * @param int $region region: RegionOst, RegionWest, all
 */
function getDesiredDates($puserid = null, $modulepartid = null, $date = null, $timeslot = null, $region = null)
{
    $pdo = getPdoConnect();
    $leftJoin = '';
    $params = [];
    $whereArr = [' 1=1 '];
    if ($puserid) {
        $params[':puserid'] = $puserid;
        $whereArr[] = ' des.puserid = :puserid ';
    }
    if ($modulepartid) {
        $params[':modulepartid'] = $modulepartid;
        $whereArr[] = ' des.modulepartid = :modulepartid ';
    }
    if ($date) {
        if (!is_int($date)) {
            $date = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0)->getTimestamp();
        }
        $params[':date'] = $date;
        $whereArr[] = ' des.date = :date ';
    }
    if ($timeslot) {
        $params[':timeslot'] = $timeslot;
        $whereArr[] = ' des.timeslot = :timeslot ';
    }

    if ($region) {
        $leftJoin .= ' LEFT JOIN mdl_block_exaplanpusers u ON u.id = des.puserid ';
        switch ($region) {
            case 'RegionOst':
                $whereArr[] = ' u.region = \'RegionOst\' ';
                break;
            case 'RegionWest':
                $whereArr[] = ' u.region = \'RegionWest\' ';
                break;
            case 'all':
            case 'online':
                // all possible regions (or empty)
                $whereArr[] = ' u.region IN (\'RegionOst\', \'RegionWest\', \'all\', \'\') ';
                break;
        }
    }

    if (!count($params)) {
        return null;
    }

    $sql = "SELECT des.*, des.puserid as relatedUserId, 'desired' as dateType
              FROM mdl_block_exaplandesired des
              ".$leftJoin."
              WHERE " . implode(' AND ', $whereArr);
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $dates = $statement->fetchAll();

    return $dates;

}

/**
 * @param int $puserid (null if needed data about whole modulepart)
 * @param int $modulepartid (null if needed data about all moduleparts)
 * @param string|int $date (null if for all dates)
 * @param int $timeslot midday type
 * @param bool $withEmptyStudents true if you need also empty dates (without students)
 * @param string $timeRange range in the timeline: future | past
 * @param string $region
 * @param string $timeRange
 * @param array $states
 */
function getFixedDates($puserid = null, $modulepartid = null, $date = null, $timeslot = null, $withEmptyStudents = false, $region = '', $timeRange = '', $states = [])
{
    $pdo = getPdoConnect();
    $params = [];
    $whereArr = [' 1=1 '];
    if ($puserid) {
        $params[':puserid'] = $puserid;
        $whereArr[] = ' dumm.puserid = :puserid ';
    }
    if ($modulepartid) {
        $params[':modulepartid'] = $modulepartid;
        $whereArr[] = ' d.modulepartid = :modulepartid ';
    }
    if ($date) {
        if (!is_int($date)) {
            $date = DateTime::createFromFormat('Y-m-d', $date)->setTime(0, 0)->getTimestamp();
        }
        $date = strtotime("today", $date); //same tstamp for whole day
        $params[':date'] = $date;
        $whereArr[] = ' d.date = :date ';
    }
    if ($timeslot) {
        $params[':timeslot'] = $timeslot;
        $whereArr[] = ' d.timeslot = :timeslot ';
    }

    $leftJoin = '';
    if ($region) {
        $leftJoin .= ' LEFT JOIN mdl_block_exaplanpusers u ON u.id = dumm.puserid ';
        $tempWhere = '';
        switch ($region) {
            case 'RegionOst':
                $tempWhere .= ' u.region = \'RegionOst\' ';
                break;
            case 'RegionWest':
                $tempWhere .= ' u.region = \'RegionWest\' ';
                break;
            case 'all':
            case 'online':
                // all possible regions (or empty)
                $tempWhere .= ' u.region IN (\'RegionOst\', \'RegionWest\', \'all\', \'\') ';
                break;
        }
        if ($withEmptyStudents) {
            $tempWhere .= ' OR u.id IS NULL '; // if the date has not related students
            $tempWhere = ' ( '.$tempWhere.' ) ';
        }
        $whereArr[] = $tempWhere;
    }

    if ($timeRange) {
        switch ($timeRange) {
            case 'future':
                $whereArr[] = ' d.date >= '.strtotime("today", time()).' '; // today or in future
                break;
            case 'past':
                $whereArr[] = ' d.date < '.strtotime("today", time()).' '; // in past
                break;
        }
    }

    if ($states) {
        $whereArr[] = ' d.state IN ('.implode(',', $states).') ';
    }

    if (!count($params)) {
        return null;
    }

    if ($withEmptyStudents && !$puserid) {
        $sql = "SELECT DISTINCT d.*, dumm.puserid as relatedUserId, IF(d.state = ".BLOCK_EXAPLAN_DATE_BLOCKED.", 'blocked', 'fixed') as dateType
                                  FROM mdl_block_exaplandates d                                                                   
                                   LEFT JOIN mdl_block_exaplanpuser_date_mm dumm ON dumm.dateid = d.id
                                    ".$leftJoin."
                                  WHERE " . implode(' AND ', $whereArr) . "
                                  ORDER BY d.date
                                  ";
    } else {
        $sql = "SELECT DISTINCT d.*, dumm.puserid as relatedUserId, IF(d.state = ".BLOCK_EXAPLAN_DATE_BLOCKED.", 'blocked', 'fixed') as dateType
                                  FROM mdl_block_exaplanpuser_date_mm dumm
                                    JOIN mdl_block_exaplandates d ON d.id = dumm.dateid
                                    ".$leftJoin."
                                  WHERE " . implode(' AND ', $whereArr) . "
                                  ORDER BY d.date
                                  ";

    }
//    echo "<pre>debug:<strong>lib_pdo.php:779</strong>\r\n"; print_r($modulepartid); echo '</pre>'; // !!!!!!!!!! delete it
//    echo "<pre>debug:<strong>lib_pdo.php:771</strong>\r\n"; print_r($sql); echo '</pre>'; ; // !!!!!!!!!! delete it
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $dates = $statement->fetchAll();

    return $dates;

}

/**
 * @param int $puserid
 * @param int $dateid
 * @param bool $returnData
 * @return bool
 */
function isPuserIsFixedForDate($puserid, $dateid, $returnData = false)
{
    if (!$puserid || !$dateid) {
        return false;
    }
    $pdo = getPdoConnect();
    $params = [
        ':dateid' => $dateid,
        ':puserid' => $puserid,
    ];
    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanpuser_date_mm WHERE dateid = :dateid AND puserid = :puserid;");
    $statement->execute($params);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $dates = $statement->fetchAll();
    if ($dates) {
        if ($returnData) {
            return $dates[0];
        }
        return true;
    }
    return null;
}

/**
 * @param int $dateId
 */
function getFixedPUsersForDate($dateId = null)
{

    $pdo = getPdoConnect();

    $params = array(
        ':dateid' => $dateId,
    );

    // get existing data for this modulepartid, date, timeslot
    $sql = "SELECT *
              FROM mdl_block_exaplanpuser_date_mm
              WHERE dateid = :dateid
                ";
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $result = $statement->fetchAll();

    return $result;
}