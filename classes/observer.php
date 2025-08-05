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

/**
 * Event observer class define.
 */
class Observer {
    /**
     * Launch tool_gradefilter_check_grade.
     *
     * @param object $event event data
     *
     * @return void
     * @throws \dml_exception
     */
    public static function tool_gradefilter_handle_user_graded(\core\event\user_graded $event) {
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        global $DB;

        $itemid = $event->other['itemid'];

        $sql = "SELECT
                    itemname AS name,
                    itemtype AS type
                FROM {grade_items} i
                WHERE i.id = :itemid";
        tool_gradefilter_sql_add_conditions($sql);
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
     * @throws \dml_exception
     */
    public static function tool_gradefilter_handle_item_created(\core\event\grade_item_created $event) {
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        if ($event->crud != "c") {
            return;
        }

        global $DB;

        $itemid = $event->objectid;

        $sql = "SELECT
                    i.gradepass AS pass,
                    i.grademax,
                    i.itemname AS name,
                    i.itemtype AS type
                FROM {grade_items} i
                WHERE i.id = :itemid";
        tool_gradefilter_sql_add_conditions($sql);
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);

        if (!$item) {
            return;
        }

        $itemtype = tool_gradefilter_get_item_type($item->name, $item->type);

        tool_gradefilter_check_grade_pass($itemid, $item->pass, $item->grademax, $itemtype);
    }

    /**
     * Checks gradepass and change if needed. In that case also checks grades of updated item.
     *
     * @param \core\event\grade_item_updated $event
     *
     * @return void
     * @throws \dml_exception
     */
    public static function tool_gradefilter_handle_item_updated(\core\event\grade_item_updated $event) {
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        if ($event->crud != "u") {
            return;
        }

        global $DB;

        $itemid = $event->objectid;

        $sql = "SELECT
                    gradepass AS pass,
                    grademax,
                    needsupdate,
                    itemname AS name,
                    itemtype AS type
                FROM {grade_items} i
                WHERE i.id = :itemid";
        tool_gradefilter_sql_add_conditions($sql);
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);
        if (!$item) {
            return;
        }
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
     * @throws \dml_exception
     */
    public static function tool_gradefilter_handle_item_deleted(\core\event\grade_item_deleted $event) {
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');d
        if (!$ispluginenabled) {
            return;
        }

        if ($event->crud != "d") {
            return;
        }

        global $DB;
        $DB->delete_records('tool_gradefilter', ['itemid' => $event->objectid]);
        tool_gradefilter_check_bonus($event->courseid);
    }
}
