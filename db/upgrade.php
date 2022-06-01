<?php
// This file is part of Exabis Planning Tool (extension for Moodle)
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// (c) 2016 GTN - Global Training Network GmbH <office@gtn-solutions.com>.

defined('MOODLE_INTERNAL') || die();

function xmldb_block_exaplan_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    $result = true;
    
		if ($oldversion < 2021112600) {
        $table = new xmldb_table('block_exaplandesired');
        $field = new xmldb_field('disabled', XMLDB_TYPE_INTEGER, 1, null, null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2021112600, 'exaplan');
    }
    if ($oldversion < 2021113000) {
        $table = new xmldb_table('block_exaplandates');
        $field = new xmldb_field('moodleid', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL, null, 0);
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('isonline', XMLDB_TYPE_INTEGER, 1, null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('duration', XMLDB_TYPE_CHAR, '100', null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('block_exaplanmodulesets');
        $field = new xmldb_field('isinstructor', XMLDB_TYPE_INTEGER, 1, null, null, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_block_savepoint(true, 2021113000, 'exaplan');
    }

    if ($oldversion < 2022012700) {
        $table = new xmldb_table('block_exaplandates');
        $field = new xmldb_field('onlineroom', XMLDB_TYPE_TEXT);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2022012700, 'exaplan');
    }

    if ($oldversion < 2022012704) {
        $table = new xmldb_table('block_exaplanpusers');
        $fields = block_exaplan_get_list_of_profile_fields(true);
        foreach ($fields as $fieldName) {
            $field = new xmldb_field($fieldName, XMLDB_TYPE_CHAR, '250', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_block_savepoint(true, 2022012704, 'exaplan');
    }

    if ($oldversion < 2022012706) {

        block_exaplan_update_profile_fields();

        upgrade_block_savepoint(true, 2022012706, 'exaplan');
    }

    if ($oldversion < 2022041801) {
        $table = new xmldb_table('block_exaplanpusers');
        $fields = ['phone1', 'phone2'];
        foreach ($fields as $fieldName) {
            $field = new xmldb_field($fieldName, XMLDB_TYPE_CHAR, '20', null, null, null);
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_block_savepoint(true, 2022041801, 'exaplan');
    }

    if ($oldversion < 2022042100) {
        $table = new xmldb_table('block_exaplannotifications');
        $field = new xmldb_field('smstext', XMLDB_TYPE_CHAR, '250', null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('smssent', XMLDB_TYPE_INTEGER, '11', null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_block_savepoint(true, 2022042100, 'exaplan');
    }

    if ($oldversion < 2022050100) {
        $table = new xmldb_table('block_exaplannotifications');
        $field = new xmldb_field('smstext', XMLDB_TYPE_TEXT, null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        upgrade_block_savepoint(true, 2022050100, 'exaplan');
    }

    return $result;
}