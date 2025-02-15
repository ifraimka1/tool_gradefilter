<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade steps are defined here.
 *
 * @package     tool_gradefilter
 * @category    upgrade
 * @copyright   2025 Solomonov Ifraim <mr.ifraim@yandex.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute format_fqw upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tool_gradefilter_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025020400) {

        // Define table tool_gradefilter to be created.
        $table = new xmldb_table('tool_gradefilter');

        // Adding fields to table tool_gradefilter.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('gradeid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_gradefilter.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('gradeid', XMLDB_KEY_FOREIGN_UNIQUE, ['gradeid'], 'grade_grades', ['id']);

        // Conditionally launch create table for tool_gradefilter.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Gradefilter savepoint reached.
        upgrade_plugin_savepoint(true, 2025020400, 'tool', 'gradefilter');
    }

    if ($oldversion < 2025021200) {

        // Define table tool_gradefilter to be updated.
        $table = new xmldb_table('tool_gradefilter');

        // Conditionally launch add field itemid.
        $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, 548, 'gradeid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Adding keys to table tool_gradefilter.
        $key = new xmldb_key('itemid', XMLDB_KEY_FOREIGN, ['itemid'], 'grade_items', ['id']);
        $dbman->add_key($table, $key);

        // Gradefilter savepoint reached.
        upgrade_plugin_savepoint(true, 2025021200, 'tool', 'gradefilter');
    }

    return true;
}
