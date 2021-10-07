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
 * @param $title
 * @param $description
 * @param $trainerpuserid
 * @param $location
 * @param $courseidnumber
 * @return bool|int
 * @throws dml_exception
 */
function block_exaplan_create_moduleset($title, $description, $trainerpuserid = null, $location = null, $courseidnumber = null)
{
    global $DB;

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
 * @param $title
 * @param $description
 * @param $trainerpuserid
 * @param $location
 * @param $courseidnumber
 * @return bool
 * @throws dml_exception
 */
function block_exaplan_update_moduleset($modulesetid, $title = null, $description = null, $trainerpuserid = null, $location = null, $courseidnumber = null)
{
    global $DB;

    $moduleset = $DB->get_record(BLOCK_EXAPLAN_DB_MODULESETS, array('id' => $modulesetid));
    if ($title) $moduleset->title = $title;
    if ($description) $moduleset->description = $description;
    if ($trainerpuserid) $moduleset->trainerpuserid = $trainerpuserid;
    if ($location) $moduleset->location = $location;
    if ($courseidnumber) $moduleset->courseidnumber = $courseidnumber;

    return $DB->update_record(BLOCK_EXAPLAN_DB_MODULESETS, $moduleset);
}


/**
 * update title, description or subjectid of crosssubject
 * @param unknown $crosssubjid
 * @param unknown $title
 * @param unknown $description
 * @param unknown $subjectid
 */
function block_exacomp_edit_crosssub($crosssubjid, $title, $description, $subjectid, $groupcategory = "")
{
    global $DB;

    $crosssubj = $DB->get_record(BLOCK_EXACOMP_DB_CROSSSUBJECTS, array('id' => $crosssubjid));
    $crosssubj->title = $title;
    $crosssubj->description = $description;
    $crosssubj->subjectid = $subjectid;
    $crosssubj->groupcategory = $groupcategory;

    return $DB->update_record(BLOCK_EXACOMP_DB_CROSSSUBJECTS, $crosssubj);
}

/**
 * remove given crosssubject
 * @param unknown $crosssubjid
 */
function block_exacomp_delete_crosssub($crosssubjid)
{
    global $DB;
    //delete examples that were created specifically only for this cross_subject
    block_exacomp_delete_examples_for_crosssubject($crosssubjid);

    // TODO: pruefen ob mein crosssubj?

    //delete student-crosssubject association
    $DB->delete_records(BLOCK_EXACOMP_DB_CROSSSTUD, array('crosssubjid' => $crosssubjid));

    //delete descriptor-crosssubject association
    $DB->delete_records(BLOCK_EXACOMP_DB_DESCCROSS, array('crosssubjid' => $crosssubjid));

    //delete crosssubject overall evaluations
    $DB->delete_records(BLOCK_EXACOMP_DB_COMPETENCES, array('compid' => $crosssubjid, 'comptype' => BLOCK_EXACOMP_TYPE_CROSSSUB));

    //delete crosssubject
    $DB->delete_records(BLOCK_EXACOMP_DB_CROSSSUBJECTS, array('id' => $crosssubjid));
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
