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

use stdClass;

require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

/**
 * Event observer class define.
 */
class Observer
{
    /**
     * Launch tool_gradefilter_check_grade.
     * @param object $event event data
     * @return void
     */
    public static function tool_gradefilter_handle_user_graded(\core\event\user_graded $event)
    {
        global $DB;

        $itemid = $event->objectid;

        $sql = "
            SELECT
                itemname AS name,
                itemtype AS type
            FROM {grade_items}
            WHERE id = :itemid";
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);

        if ($item) {
            $isregularitem = tool_gradefilter_is_regular_item($item->name, $item->type);
            tool_gradefilter_check_grade($event->objectid, $isregularitem);
        }
    }

    public static function tool_gradefilter_handle_item_created(\core\event\grade_item_created $event)
    {
        if ($event->crud != "c") return;

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
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);

        $isregularitem = tool_gradefilter_is_regular_item($item->name, $item->type);

        if ($isregularitem) {
            $correctpass = $item->grademax * 0.6;
            $item->pass = floatval($item->pass);
            if (abs($item->pass - $correctpass) >= 0.1) {
                $DB->set_field('grade_items', 'gradepass', $correctpass, ['id' => $itemid]);
            }
        } else if ($item->pass != 0) {
            $DB->set_field('grade_items', 'gradepass', 0, ['id' => $itemid]);
        }
    }

    public static function tool_gradefilter_handle_item_updated(\core\event\grade_item_updated $event)
    {
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
        $params = ['itemid' => $itemid];
        $item = $DB->get_record_sql($sql, $params);

        $isregularitem = tool_gradefilter_is_regular_item($item->name, $item->type);
        $ispasschanged = false;

        if ($isregularitem) {
            $correctpass = $item->grademax * 0.6;
            $item->pass = floatval($item->pass);
            if ($item->needsupdate || abs($item->pass - $correctpass) >= 0.1) {
                $DB->set_field('grade_items', 'gradepass', $correctpass, ['id' => $itemid]);
                $ispasschanged = true;
            }
        } else if ($item->pass != 0) {
            $DB->set_field('grade_items', 'gradepass', 0, ['id' => $itemid]);
            $ispasschanged = true;
        }

        if ($ispasschanged) {
            $sql = "SELECT g.id AS id
            FROM {grade_grades} g
            JOIN {grade_items} i ON i.id = g.itemid
            WHERE i.id = :itemid";
            $grades = $DB->get_records_sql($sql, $params);

            foreach ($grades as $grade) {
                tool_gradefilter_check_grade($grade->id, $isregularitem);
            }
        }
    }

    public static function tool_gradefilter_handle_item_deleted(\core\event\grade_item_deleted $event)
    {
        global $DB;
        $DB->delete_records('tool_gradefilter', ['itemid' => $event->objectid]);
    }
}
