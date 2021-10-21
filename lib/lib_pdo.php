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
$CFG->dbname = $dbname; // RW: it works like this, but why?
$CFG->dbusername = $dbusername;
$CFG->dbpassword = $dbpassword;


function getPdoConnect()
{
    global $CFG, $dbname, $dbusername, $dbpassword;
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=' . $dbname, $dbusername, $dbpassword); // TODO: constant, global?
    } catch (Exception $e) {
        // use global parameters
        $pdo = new PDO('mysql:host=localhost;dbname=' . $CFG->dbname, $CFG->dbuser, $CFG->dbpass);
    }
    return $pdo;
}

function getPuser($userid)
{

    $pdo = getPdoConnect();

    $params = array(
        ':userid' => $userid,
    );

    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanpusers WHERE userid = :userid");
    $statement->execute($params);
    $user = $statement->fetchAll();
    return $user[0];
}

function getOrCreatePuser()
{
    global $USER;

    $pdo = getPdoConnect();

    $params = array(
        ':userid' => $USER->id,
        ':moodleid' =>  get_config('exaplan', 'moodle_id'),
    );


    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanpusers WHERE userid = :userid AND moodleid=:moodleid");
    $statement->execute($params);
    $user = $statement->fetchAll();
    if ($user == null) {
        $params = array(
            ':userid' => $USER->id,
            ':moodleid' => get_config('exaplan', 'moodle_id'),
            ':firstname' => $USER->firstname,
            ':lastname' => $USER->lastname,
            ':email' => $USER->email,
        );

        $statement = $pdo->prepare("INSERT INTO mdl_block_exaplanpusers (userid, moodleid, firstname, lastname, email) VALUES (:userid, :moodleid, :firstname,:lastname, :email);");
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
    $modules = $statement->fetchAll();

    foreach ($modules as $module) {
        $moduleset = new \stdClass;
        $moduleset->set = $module;
        $params = array(
            ':modulesetid' => $moduleset->set['id'],
        );
        $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmoduleparts WHERE modulesetid = :modulesetid");
        $statement->execute($params);
        $moduleset->parts = $statement->fetchAll();
        $modulesets[] = $moduleset;
    }

    return $modulesets;

}

function getModulesOfUser($userid, $state = 2)
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
                $moduleset->parts = $statement->fetchAll();

                $dates = array();
                foreach ($moduleset->parts as $key => $part) {
                    $params = array(
                        ':modulepartid' => $part['id'],
                        ':puserid' => getPuser($userid)['id'],
                        ':state' => $state,
                    );
                    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplandates JOIN mdl_block_exaplanpuser_date_mm ON mdl_block_exaplandates.id=mdl_block_exaplanpuser_date_mm.dateid WHERE modulepartid = :modulepartid AND puserid = :puserid AND state = :state");
                    $statement->execute($params);
                    $date = $statement->fetchAll();
                    $moduleset->parts[$key]['date'] = $date;
                }
                $modulesets[] = $moduleset;

            }
        }
    }
    return $modulesets;
}

function setPrefferedDate($modulepartid, $puserid, $date, $timeslot)
{


    $pdo = getPdoConnect();
    $timestamp = new DateTime();
    $timestamp = $timestamp->getTimestamp();

    $params = array(
        ':modulepartid' => $modulepartid,
        ':date' => $date,
        ':timeslot' => $timeslot,
        ':state' => 1,
        ':creatorpuserid' => $puserid,
        ':creatortimestamp' => $timestamp,
        ':modifiedpuserid' => $puserid,
        ':modifiedtimestamp' => $timestamp,

    );


    $statement = $pdo->prepare("INSERT INTO mdl_block_exaplandates (modulepartid, date, timeslot, state, creatorpuserid, creatortimestamp, modifiedpuserid, modifiedtimestamp) VALUES (:modulepartid, :date, :timeslot, :state, :creatorpuserid, :creatortimestamp, :modifiedpuserid, :modifiedtimestamp);");
    $statement->execute($params);
    $dateid = $pdo->lastInsertId();

    $params = array(
        ':dateid' => $dateid,
        ':puserid' => $puserid,
        ':creatorpuserid' => $puserid,

    );

    $statement = $pdo->prepare("INSERT INTO mdl_block_exaplanpuser_date_mm (dateid, puserid, creatorpuserid) VALUES (:dateid, :puserid, :creatorpuserid);");
    $statement->execute($params);


}


function updateNotifications()
{
    $pdo = getPdoConnect();
    $params = array(
        ':moodleid' => get_config('exaplan', 'moodle_id')
    );

    $statement = $pdo->prepare("
        SELECT pu.userid as userto, n.notificationtext
        FROM mdl_block_exaplannotifications as n
        JOIN mdl_block_exaplanpusers as pu ON pu.userid = n.puseridto
        WHERE n.moodlenotificationcreated = false
        AND pu.moodleid = :moodleid
     ");
    $statement->execute($params);

    $plannotifications = $statement->fetchAll();

    // iterate over all notifications that have not been used for creating moodle notifications and create them
    foreach ($plannotifications as $pn) {
        // userfrom = 2 because 2 is always admin
        block_exaplan_send_notification("date_fixed", 2, $pn["userto"], "Termin fixiert", $pn["notificationtext"], "Termin");
    }
}
