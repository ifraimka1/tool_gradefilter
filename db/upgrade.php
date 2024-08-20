<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Plugin upgrade code
 *
 * @package    tool_gradefilter
 * @copyright  2024 Solomonov Ifraim <solomonov@sfedu.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to upgrade tool_gradefilter.
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_tool_gradefilter_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();

    if ($oldversion < 2024082102) {
        // Define table tool_gradefilter_bonuses to be created.
        $table = new xmldb_table('tool_gradefilter_bonuses');

        // Adding fields to table tool_gradefilter_bonuses.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_gradefilter_bonuses.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('itemid', XMLDB_KEY_FOREIGN_UNIQUE, ['itemid'], 'grade_items', ['id']);

        // Conditionally launch create table for tool_gradefilter_bonuses.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_gradefilter_bonuses_ex to be created.
        $table = new xmldb_table('tool_gradefilter_bonuses_ex');

        // Adding fields to table tool_gradefilter_bonuses_ex.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('bgid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reasongradeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_gradefilter_bonuses_ex.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('bgid', XMLDB_KEY_FOREIGN, ['bgid'], 'tool_gradefilter_bonuses', ['id']);
        $table->add_key('reasongradeid', XMLDB_KEY_FOREIGN_UNIQUE, ['reasongradeid'], 'grade_grades', ['id']);

        // Conditionally launch create table for tool_gradefilter_bonuses_ex.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Gradefilter savepoint reached.
        upgrade_plugin_savepoint(true, 2024082102, 'tool', 'gradefilter');
    }

    return true;
}
