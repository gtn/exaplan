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

/**
 * DATABSE TABLE NAMES
 */
const BLOCK_EXAPLAN_DB_MODULESETS = 'block_exaplanmodulesets';

/**
 *
 * @param courseid or context $context
 */
function block_exaplan_is_admin($context = null) {
    $context = block_exaplan_get_context_from_courseid($context);
    return has_capability('block/exaplan:admin', $context);
}

/**
 *
 * @param courseid or context $context
 * @param userid $userid
 */
function block_exaplan_is_teacher($context = null, $userid = null) {
    $context = block_exaplan_get_context_from_courseid($context);
    return has_capability('block/exaplan:teacher', $context, $userid);
}

function block_exaplan_get_context_from_courseid($courseid) {
    global $COURSE;

    if ($courseid instanceof context) {
        // already context
        return $courseid;
    } else if (is_numeric($courseid)) { // don't use is_int, because eg. moodle $COURSE->id is a string!
        return context_course::instance($courseid);
    } else if ($courseid === null) {
        return context_course::instance($COURSE->id);
    } else {
        throw new \moodle_exception('wrong courseid type '.gettype($courseid));
    }
}

class block_exaplan_permission_exception extends moodle_exception {
    function __construct($errorcode = 'Not allowed', $module = '', $link = '', $a = null, $debuginfo = null) {
        return parent::__construct($errorcode, $module, $link, $a, $debuginfo);
    }
}


/**
 * @param $title
 * @param $description
 * @param null $trainerpuserid
 * @param null $location
 * @param null $courseidnumber
 * @return bool|int
 * @throws dml_exception
 */
function block_exaplan_create_moduleset($title, $description, $trainerpuserid = null, $location = null, $courseidnumber = null)
{
    global $DB;

    // Only the admin can create modulesets
    if(!block_exaplan_is_admin()){
        throw new block_exaplan_permission_exception("User must be admin or teacher!");
    }

    $date = new stdClass();
    $date->title = $title;
    $date->description = $description;
    $date->trainerpuserid = $trainerpuserid;
    $date->location = $location;
    $date->courseidnumber = $courseidnumber;

    return $DB->insert_record(BLOCK_EXAPLAN_DB_MODULESETS, $date);
}

/**
 * @param $modulesetid
 * @param null $title
 * @param null $description
 * @param null $trainerpuserid
 * @param null $location
 * @param null $courseidnumber
 * @return bool
 * @throws dml_exception
 */
function block_exaplan_update_moduleset($modulesetid, $title = null, $description = null, $trainerpuserid = null, $location = null, $courseidnumber = null)
{
    global $DB;

    // Only the admin can create modulesets
    if(!block_exaplan_is_admin()){
        throw new block_exaplan_permission_exception("User must be admin or teacher!");
    }

    $moduleset = $DB->get_record(BLOCK_EXAPLAN_DB_MODULESETS, array('id' => $modulesetid));
    if ($title) $moduleset->title = $title;
    if ($description) $moduleset->description = $description;
    if ($trainerpuserid) $moduleset->trainerpuserid = $trainerpuserid;
    if ($location) $moduleset->location = $location;
    if ($courseidnumber) $moduleset->courseidnumber = $courseidnumber;

    return $DB->update_record(BLOCK_EXAPLAN_DB_MODULESETS, $moduleset);
}


/**
 * remove given crosssubject
 * @param unknown $crosssubjid
 */
function block_exaplan_delete_moduleset($modulesetid)
{
    global $DB;

    // Only the admin can create modulesets
    if(!block_exaplan_is_admin()){
        throw new block_exaplan_permission_exception("User must be admin or teacher!");
    }

    //delete moduleset
    $DB->delete_records(BLOCK_EXAPLAN_DB_MODULESETS, array('id' => $modulesetid));
}


function block_exaplan_get_modulesets_by_puserid($puserid)
{

}

function block_exaplan_create_date()
{

}

function block_exaplan_update_date($dateid)
{

}

function block_exaplan_delete_date()
{

}


//function block_exaplan_get_trainers... by company??(){
//
//}

function block_exaplan_get_()
{

}

function block_exaplan_()
{

}

//function block_exaplan_(){
//
//}
//
//function block_exaplan_(){
//
//}
//
//function block_exaplan_(){
//
//}
//
//function block_exaplan_(){
//
//}
//
//function block_exaplan_(){
//
//}
//
//function block_exaplan_(){
//
//}
//
//function block_exaplan_(){
//
//}
