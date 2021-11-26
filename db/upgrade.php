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
    return $result;
}