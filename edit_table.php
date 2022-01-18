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
$targetTable = optional_param('targetTable', "", PARAM_ALPHA);

// different titles/fields/etc... for different tables:
switch ($targetTable) {
    case 'moduleparts':
        $moduleSetId = required_param('msid', PARAM_INT);
        $moduleSetTitle = getTableData('block_exaplanmodulesets', $moduleSetId, 'title');
        $contentTitle = '<h3>Termine bearbeiten<br/><h4>für <strong>'.$moduleSetTitle.'</strong></h4></h3>';
        $reltable = "block_exaplanmoduleparts";
        $recordsCondition = ['modulesetid' => $moduleSetId];
        $cellTitles = ['Titel', 'Duration'];
        $inputs = [
            'title' => [],
//            'modulesetid' => ['type' => 'hidden'], // TODO: selectbox?
            'duration' => [],
        ];
        $addFieldValues['modulesetid'] = $moduleSetId;
        $addParamsToUrls = [
            'msid' => $moduleSetId
        ];
        break;
    case 'modulesets':
    default:
        $contentTitle = '<h3>Moduleinträge bearbeiten</h3>';
        $reltable = "block_exaplanmodulesets";
        $recordsCondition = [];
        $cellTitles = ['Titel', 'Beschreibung', 'Kurse ID', 'keine Wunschtermine', 'Ausbildnerkurs', '', '', 'Terminen'];
        $inputs = [
            'title' => [],
            'description' => ['type' => 'textarea'],
            'courseidnumber' => [ // TODO: selectbox?
                'cellAttributes' => ['width' => 70]],
            'nodesireddates' => [
                'type' => 'checkbox',
                'cellAttributes' => ['class' => 'to-center'] ],
            'isinstructor' => [
                'type' => 'checkbox',
                'cellAttributes' => ['class' => 'to-center'] ]
        ];
        $addFieldValues = [];
        $addParamsToUrls = [];
}

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

if ($isadmin) {
	if (isset($action)) {

	    $redirectUrl = $CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid='.$courseid;
	    if ($targetTable) {
            $addParamsToUrls['targetTable'] = $targetTable;
        }

        switch ($action) {
            case 'toDashboard':
                redirect($CFG->wwwroot.'/my/');
                die;
                break;
            case 'save':
                // save existing records
                if (isset($_POST['data'])) {
                    $data = $_POST['data'];
                    foreach ($data as $id => $recordData) {
                        if ($id > 0) {
                            $recordData = array_merge($addFieldValues, $recordData);
                            $set = [];
                            $values = [];
                            foreach ($recordData as $fieldName => $fieldValue) {
                                if (array_key_exists($fieldName, $inputs) || array_key_exists($fieldName, $addFieldValues)) {
                                    $fieldValue = trim($fieldValue);
                                    $set[] = ' '.$fieldName.' = ? ';
                                    $values[] = $fieldValue;
                                }
                            }
                            $values[] = $id; // for Where
                            if (count($set) > 0) {
                                $DB->execute('UPDATE {'.$reltable.'} SET '.implode(' , ', $set).' WHERE id = ? ', $values);
                            }
                        }
                    }
                }
                // add new record
                if (isset($_POST['datanew'])) {
                    $data = $_POST['datanew'];
                    foreach ($data as $id => $recordData) {
                        $values = [];
                        $recordData = array_merge($addFieldValues, $recordData);
                        foreach ($recordData as $fieldName => $fieldValue) {
                            if (array_key_exists($fieldName, $inputs) || array_key_exists($fieldName, $addFieldValues)) {
                                $fieldValue = trim($fieldValue);
                                $values[$fieldName] = $fieldValue;
                            }
                        }
                        if (count($values) > 0) {
                            $DB->insert_record($reltable, (object)$values);
                        }
                    }
                }
                $redirectUrl .= block_exaplan_add_params_to_url($addParamsToUrls);
                redirect($redirectUrl);
                die;
                break;
            case 'delete':
                $rectodelete = required_param('recid', PARAM_INT);
                $DB->delete_records($reltable, ['id' => $rectodelete]);
                $redirectUrl .= block_exaplan_add_params_to_url($addParamsToUrls);
                redirect($redirectUrl, 'Der Eintrag wurde gelöscht', null, 'info');
                die;
                break;
        }
    }
} else {
    throw new block_exaplan_permission_exception("User must be admin!");
}


// data save


// Build tab navigation & print header.

echo $OUTPUT->header();

echo '<div id="exaplan">';

echo $contentTitle;

// records from this Moodle
if ($isadmin) {
    $records = block_exaplan_get_records($reltable, $recordsCondition);
    echo block_exaplan_edit_table($records, $cellTitles, $inputs, $courseid);
} else {
    echo '<div class="alert alert-danger">Only for admins!</div>';
}

echo '</div>';

/* END CONTENT REGION */
echo $OUTPUT->footer();

function block_exaplan_edit_table($records, $cellTitles, $inputs, $courseid) {
	global $OUTPUT, $CFG, $targetTable, $addParamsToUrls;
	    $content = '';

        $table = new html_table();
        $table->id = 'exaplan-table-records';
        $table->attributes['class'] = ' ';
        $table->attributes['border'] = 0;
        $rows = array();

        // record titles
        $row = new html_table_row();
        foreach ($cellTitles as $title) {
            $cell = new html_table_cell();
            $cell->header = true;
            $cell->text = $title;
            $row->cells[] = $cell;
        }
        $rows[] = $row;

        if ($records && count($records) > 0) {

            // record inputs
            foreach ($records as $reckey => $record) {
                $row = new html_table_row();
                $row->attributes['class'] .= 'existingRecord';
                foreach ($inputs as $fieldName => $fieldAttrs) {
                    $row->cells[] = block_exaplan_get_cellcontent_editrecords($record->id, $fieldName, $record->{$fieldName}, @$fieldAttrs['type'], @$fieldAttrs['cellAttributes']);
                }
                // up/down buttons
//                end($records);
//                reset($records);
         
                // Delimeter
                $row->cells[] = '&nbsp;';
                // Delete button
                $cell = new html_table_cell();
                $cell->attributes['valign'] = 'top';
                $cell->attributes['class'] .= 'hideForNew';
                $deleteUrl = $_SERVER['REQUEST_URI'].'&action=delete&recid='.intval($record->id);
                if ($targetTable) {
                    $deleteUrl .= '&deleteUrl='.$deleteUrl;
                }
                $cell->text = '<a href="'.$deleteUrl.'"
                                    onclick="return confirm(\''."Wollen sie den Eintrag wirklich löschen?".'\');"
                                    class="small">'
                                    .html_writer::span($OUTPUT->pix_icon("i/delete", "Löschen"))
                                    .'</a>';
                $row->cells[] = $cell;

                // related records data
                switch ($targetTable) {
                    case 'modulesets':
                    case '': // like default
                        // count of module parts
                        $cell = new html_table_cell();
                        $cell->attributes['valign'] = 'top';
                        $cell->attributes['align'] = 'center';
                        $cell->attributes['class'] .= 'hideForNew';
                        $parts = getModulePartsForModuleSet($record->id);
                        $cell->text = (count($parts) > 0 ? count($parts) : '');
                        $row->cells[] = $cell;
                        // go to edit module parts
                        $cell = new html_table_cell();
                        $cell->attributes['valign'] = 'top';
                        $cell->attributes['class'] .= 'hideForNew';
                        $editUrl = $CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid=1&targetTable=moduleparts&msid='.$record->id;
                        $cell->text = '<a href="'.$editUrl.'" class="btn btn-info">Terminen bearbeiten</a>';
                        $row->cells[] = $cell;
                        break;
                }

                $rows[] = $row;
            }
        } else {
            $row = new html_table_row();
            $cell = new html_table_cell();
            $cell->colspan = 20;
            $cell->text = "keine Einträge gefunden";
            $row->cells[] = $cell;
            $rows[] = $row;
            // inputs for creating FIRST record
            $row = new html_table_row();
            $row->attributes['class'] .= 'onlyRecordTemplate';
            foreach ($inputs as $fieldName => $fieldAttrs) {
                $row->cells[] = block_exaplan_get_cellcontent_editrecords(0, $fieldName, '', @$fieldAttrs['type'], @$fieldAttrs['cellAttributes']);
            }
            $rows[] = $row;
        }
        $table->data = $rows;
        $tablecontent = html_writer::table($table);

        $buttons = '<p>';
        $buttons .= html_writer::tag('button',
                                    "Neuen Eintrag anlegen",
                                    ['class' => 'btn btn-info',
                                    'id' => 'exaplan_add_record_button',
                                    'name' => 'add',
                                    'value' => 'add',
                                    'onclick' => 'return false;']);
        $buttons .= '&nbsp;'.html_writer::tag('button',
                                    "speichern",
                                    ['type' => 'submit',
                                    'class' => 'btn btn-success',
                                    'name' => 'action',
                                    'value' => 'save']);
        $buttons .= '</p>';
        // buttons, regarding targetTable
        switch ($targetTable) {
            case 'moduleparts':
                $buttons .= '<p>';
                $buttons .= '<a href="'.$CFG->wwwroot.'/blocks/exaplan/edit_table.php?courseid=1" role="button" class="btn btn-secondary btn-to-dashboard">zurück zum Moduleinträge bearbeiten</a>';
                $buttons .= '</p>';
                break;
            default:
        }
        $buttons .= '<p>'.html_writer::tag('button',
                                    "zurück zum Dashboard",
                                    ['type' => 'submit',
                                    'class' => 'btn btn-secondary btn-to-dashboard',
                                    'name' => 'action',
                                    'value' => 'toDashboard']);
        $buttons .= '</p>';
        $buttons = html_writer::div($buttons);
        $formActionUrl = 'edit_table.php?courseid='.$courseid;
        $formActionUrl .= block_exaplan_add_params_to_url($addParamsToUrls);
        $form = html_writer::div(
                    html_writer::tag('form',
                            $tablecontent.$buttons,
                            array(  'action' => $formActionUrl,
                                    'method' => 'post',
                                    'class' => 'form-vertical')));

        $content .= $form;
	    return $content;
}
    
function block_exaplan_get_records($reltable, $additionalCondition = []) {
	global $DB;
	$where = '';
	$whereAnd = [];
	$params = [];
	if ($additionalCondition && count($additionalCondition) > 0) {
	    foreach ($additionalCondition as $field => $value) {
	        $whereAnd[] = ' '.$field.' = ? ';
	        $params[] = $value;
        }
    }
    if (count($whereAnd) > 0) {
        $where = ' WHERE '.implode(' AND ', $whereAnd).' ';
    }
	$sql = "SELECT tbl.*
		FROM {".$reltable."} tbl
		".$where."
		ORDER BY tbl.title";
	return $DB->get_records_sql($sql, $params);

}

function block_exaplan_get_cellcontent_editrecords($recId, $fieldName, $value, $type = 'text', $attributes = array()) {
    $cell = new html_table_cell();
    $cell->attributes['valign'] = 'top';
    if ($attributes && count($attributes) > 0) {
        foreach ($attributes as $attrName => $attrVal) {
            $cell->attributes[$attrName] = $attrVal;
        }
    }
    $inputContent = '';
    $inputName = 'data['.$recId.']['.$fieldName.']';
    switch ($type) {
        case 'textarea':
            $inputContent .= html_writer::tag('textarea',
                $value,
                array(
                    'name' => $inputName,
                    'class' => ' form-control '
                ));
            break;
        case 'checkbox':
            $inputContent .= html_writer::empty_tag('input',
                array(
                    'type' => 'hidden',
                    'name' => $inputName,
                    'value' => '0',
                )
            );
            $checked = [];
            if ($value) {
                $checked['checked'] = 'checked';
            }
            $inputContent .= html_writer::empty_tag('input',
                array_merge(
                    array(
                        'type' => 'checkbox',
                        'name' => $inputName,
                        'value' => 1,
                    ),
                    $checked
                )
            );
            break;
        case 'text':
        case 'hidden':
        default:
            $inputContent .= html_writer::empty_tag('input',
                array(
                    'type' => $type,
                    'name' => $inputName,
                    'value' => $value,
                    'class' => ' form-control '));
            break;
    }
    $cell->text = $inputContent;
    return $cell;
}

function block_exaplan_add_params_to_url($params = []) {
    $toReturn = '';
    foreach ($params as $name => $val) {
        $toReturn .= '&'.$name.'='.$val;
    }
    return $toReturn;
}