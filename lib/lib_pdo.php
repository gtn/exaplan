<?php
// This file is part of Exabis Planning Tool
//
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>
//
// Exabis Competence Grid is free software: you can redistribute it and/or modify
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



function getOrCreatePuser(){
    global $USER;


    $pdo = new PDO('mysql:host=localhost;dbname=moodle2', 'root', ''); // TODO: constant, global?
    $params = array(
        ':userid' => $USER->id,
    );


    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanpusers WHERE userid = :userid");
    $statement->execute($params);
    $user = $statement->fetchAll();
    if($user == null){
        $params = array(
            ':userid' => $USER->id,
            ':moodleid' => get_config('exaplan', 'moodle_id'),
            ':firstname' => $USER->firstname,
            ':lastname' => $USER->lastname,
            ':email' => $USER->email,
        );

        $statement = $pdo->prepare("INSERT INTO mdl_block_exaplanpusers (userid, moodleid, firstname, lastname, email) VALUES (:userid, :moodleid, :firstname,:lastname, :email);");
        $statement->execute($params);
        return true;
    }else {
        return $user[0]['id'];
    }
}

function getModulesOfUser($userid){
    global $DB, $COURSE;

    $context = context_course::instance($COURSE->id);
    $modulesets = array();
    $pdo = new PDO('mysql:host=localhost;dbname=moodle2', 'root', ''); // TODO: constant, global?

    $courses = $DB->get_records('course');
    foreach($courses as $course){
        if($course->idnumber > 0){
            if(is_enrolled($context, $userid, '', true)){
                $moduleset = new \stdClass;
                $params = array(
                    ':courseidnumber' => $course->idnumber,
                );
                $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmodulesets WHERE courseidnumber = :courseidnumber");
                $statement->execute($params);
                $moduleset->set = $statement->fetchAll()[0];

                $params = array(
                    ':modulesetid' => $moduleset->set['id'],
                );
                $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplanmoduleparts WHERE modulesetid = :modulesetid");
                $statement->execute($params);
                $moduleset->parts = $statement->fetchAll();

                $dates = array();
                foreach($moduleset->parts as $key=>$part){
                    $params = array(
                        ':modulepartid' => $part['id'],
                    );
                    $statement = $pdo->prepare("SELECT * FROM mdl_block_exaplandates WHERE modulepartid = :modulepartid");
                    $statement->execute($params);
                    $dates = $statement->fetchAll();
                    $moduleset->parts[$key]['dates'] = $dates;
                }
                $modulesets[] = $moduleset;

            }
        }
    }
        return $modulesets;
}

function getDatesOfUser($userid){

}