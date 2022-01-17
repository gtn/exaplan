<?php
// This file is part of exaplan
//
// (c) 2022 GTN - Global Training Network GmbH <office@gtn-solutions.com>
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

require __DIR__.'/inc.php';
global $CFG, $PAGE, $OUTPUT, $USER;
block_exaplan_init_js_css();
$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', "", PARAM_ALPHA);
$reltable="block_exaplanmodulesets";
if (! $course = $DB->get_record ( 'course', array ('id' => $courseid) )) {
	print_error ( 'invalidcourse', 'block_simplehtml', $courseid );
}

require_login();

$context = context_system::instance();

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/exaplan/edit_table.php', array('courseid' => $courseid));
$PAGE->set_heading("Terminplaner Datenwartung");
$PAGE->set_title("Terminplaner Datenwartung");
$isadmin = block_exaplan_is_admin();
if ($isadmin){
	if (isset($action)) {
    switch ($action) {
        case 'save':
            // save existing records
            if (isset($_POST['data'])) {
                $data = $_POST['data'];
                foreach ($data as $id => $recordtitle) {
                    $newtitle = trim($recordtitle);
                    if ($id > 0 && $newtitle) {
                        $DB->execute('UPDATE {'.$reltable.'} SET title =? WHERE id = ?', array($newtitle, $id));
                    }
                }
            }
            // add new record
            if (isset($_POST['datanew'])) {
                $data = $_POST['datanew'];
                foreach ($data as $id => $recordtitle) {
                    $newtitle = trim($recordtitle);
                    if ($newtitle) {
                        /*$sqlmaxsorting = "SELECT MAX(sorting) as sorting FROM {".$reltable."} WHERE source = ?";
                        $max_sorting = $DB->get_record_sql($sqlmaxsorting, array(BLOCK_EXACOMP_DATA_SOURCE_CUSTOM));
                        $sorting = intval($max_sorting->sorting) + 1;*/
                        $DB->insert_record($reltable, (object)array(
                                'title' => $newtitle,
                                'description' => 0,
                                'courseidnumber' => $newcourseidnumber,
                                'nodesireddates' => 0,
                                'isinstructor ' => 0));
                    }
                }
            }
            redirect($CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid='.$courseid);
            die;
            break;
        case 'delete':
            $rectodelete = required_param('recid', PARAM_INT);
            $DB->delete_records($reltable, ['id' => $rectodelete]);
            redirect($CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid='.$courseid, 'Der Eintrag wurde gelöscht', null, 'info');
            die;
            break;
    }
}
}else{
//is no admin
}


// data save


// Build tab navigation & print header.

echo $OUTPUT->header();


// records from this Moodle
echo block_exaplan_edit_table($reltable,$courseid);



/* END CONTENT REGION */
echo $OUTPUT->footer();

function block_exaplan_edit_table($reltable,$courseid) {
	global $OUTPUT;
	    $content = '';

        $tablecontent = '';
        $table = new html_table();
        $table->id = 'exaplan-table-records';
        $table->attributes['class'] = ' ';
        $table->attributes['border'] = 0;
        $rows = array();
        $records = block_exaplan_get_records($reltable,$courseid);
        if ($records && count($records) > 0) {
            foreach ($records as $reckey => $record) {
                $row = new html_table_row();
                $cell = new html_table_cell();
                //$cell->attributes['width'] = '50%';
                $cell->text = html_writer::empty_tag('input',
                                array('type' => 'text',
                                        'name' => 'data['.$record->id.']',
                                        'value' => $record->title,
                                        'class' => 'form-control '));
                $row->cells[] = $cell;
                // up/down buttons
                end($records);
   
                reset($records);
         
                // Delimeter
                $row->cells[] = '&nbsp;';
                // Delete button
                $row->cells[] = '<a href="'.$_SERVER['REQUEST_URI'].'&action=delete&recid='.intval($record->id).'"
                                    onclick="return confirm(\''."Wollen sie den Eintrag wirklich löschen?".'\');"
                                    class="small">'
                                    .html_writer::span($OUTPUT->pix_icon("i/delete", "Löschen"))
                                    .'</a>';
                $rows[] = $row;
            }
        } else {
            $row = new html_table_row();
            $row->cells[] = "keine Einträge gefunden";
            $rows[] = $row;
        }
        $table->data = $rows;
        $tablecontent = html_writer::table($table);

        $buttons = html_writer::tag('button',
                                    "Neuen Eintrag anlegen",
                                    ['class' => 'btn btn-default',
                                    'id' => 'exaplan_add_record_button',
                                    'name' => 'add',
                                    'value' => 'add',
                                    'onclick' => 'return false;']);
        $buttons .= '&nbsp;'.html_writer::tag('button',
                                    "speichern",
                                    ['type' => 'submit',
                                    'class' => 'btn btn-default',
                                    'name' => 'action',
                                    'value' => 'save']);
        $buttons = html_writer::div($buttons);
        $form = html_writer::div(
                    html_writer::tag('form',
                            $tablecontent.$buttons,
                            array('action' => 'edit_table.php?courseid='.$courseid,
                                    'method' => 'post',
                                    'class' => 'form-vertical')));

        $content .= $form;
	    return $content;
}
    
function block_exaplan_get_records($reltable) {
	global $DB;
	return $DB->get_records_sql("
		SELECT tbl.*
		FROM {".$reltable."} tbl
		ORDER BY tbl.title
	");

}
