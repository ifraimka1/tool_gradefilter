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
 * Plugin observers.
 *
 * @package     tool_gradefilter
 * @copyright   2025 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_gradefilter;

require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

/**
 * Event observer class define.
 */
class Observer
{
    /**
     * Launch tool_gradefilter_check_grade.
     * 
     * @param object $event event data
     * 
     * @return void
     */
    public static function tool_gradefilter_handle_user_graded(\core\event\user_graded $event)
    {
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) return;

        global $DB;

        $itemid = $event->other['itemid'];

        $sql = "
            SELECT
                itemname AS name,
                itemtype AS type
            FROM {grade_items}
            WHERE id = :itemid";
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);

        if ($item) {
            $itemtype = tool_gradefilter_get_item_type($item->name, $item->type);
            tool_gradefilter_check_grade($event->objectid, $itemtype);
        }
    }

    /**
     * Define correct gradepass.
     * 
     * @param \core\event\grade_item_created $event
     * 
     * @return void
     */
    public static function tool_gradefilter_handle_item_created(\core\event\grade_item_created $event)
    {
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) return;

        if ($event->crud != "c") return;

        global $DB;

        $itemid = $event->objectid;

        $sql = "
            SELECT
                gradepass AS pass,
                grademax,
                itemname AS name,
                itemtype AS type
            FROM {grade_items}
            WHERE id = :itemid";
        tool_gradefilter_sql_add_conditions($sql);
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);

        $itemtype = tool_gradefilter_get_item_type($item->name, $item->type);

        tool_gradefilter_check_grade_pass($itemid, $item->pass, $item->grademax, $itemtype);
    }

    /**
     * Checks gradepass and change if needed. In that case also checks grades of updated item.
     * 
     * @param \core\event\grade_item_updated $event
     * 
     * @return void
     */
    public static function tool_gradefilter_handle_item_updated(\core\event\grade_item_updated $event)
    {
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) return;

        global $DB;

        $itemid = $event->objectid;

        $sql = "
            SELECT
                gradepass AS pass,
                grademax,
                needsupdate,
                itemname AS name,
                itemtype AS type
            FROM {grade_items}
            WHERE id = :itemid";
        tool_gradefilter_sql_add_conditions($sql);
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);

        $itemtype = tool_gradefilter_get_item_type($item->name, $item->type);

        $ispasschanged = tool_gradefilter_check_grade_pass($itemid, $item->pass, $item->grademax, $itemtype, $item->needsupdate);

        if ($ispasschanged) {
            if ($itemtype === 1) {
                tool_gradefilter_check_bonus($event->courseid);
            }

            $sql = "SELECT g.id AS id
            FROM {grade_grades} g
            JOIN {grade_items} i ON i.id = g.itemid
            WHERE i.id = :itemid";
            tool_gradefilter_sql_add_conditions($sql);
            $grades = $DB->get_records_sql($sql, $params);

            foreach ($grades as $grade) {
                tool_gradefilter_check_grade($grade->id, $itemtype);
            }
        }
    }

    /**
     * Deletes all grades from "tool_gradefilter" table.
     * 
     * @param \core\event\grade_item_deleted $event
     * 
     * @return void
     */
    public static function tool_gradefilter_handle_item_deleted(\core\event\grade_item_deleted $event)
    {
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) return;

        global $DB;
        $DB->delete_records('tool_gradefilter', ['itemid' => $event->objectid]);
        tool_gradefilter_check_bonus($event->courseid);
    }

    public static function tool_gradefilter_handle_config_change(\core\event\config_log_created $event)
    {
        $plugin = $event->other['plugin'] ?? '';
        $paramname = $event->other['name'] ?? '';

        if ($plugin === 'tool_gradefilter' && $paramname === 'isenabled') {
            $newvalue = $event->other['value'];
            if ($newvalue) {
                // tool_gradefilter_enable_plugin();
            }
        }
    }
}
