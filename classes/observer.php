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
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        // Избегаем повторной проверки оценки.
        $source = $event->other['source'] ?? null;
        if ($source === get_string('pluginname', 'tool_gradefilter')) {
            return;
        }

        global $DB, $CFG;
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

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
    public static function tool_gradefilter_handle_cm_created(\core\event\course_module_created $event) {
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        if ($event->crud != "c") {
            return;
        }

        global $DB, $CFG;
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

        $sql = "SELECT
                    i.id,
                    i.gradepass AS pass,
                    i.grademax,
                    i.itemname AS name,
                    i.itemtype AS type,
                    i.courseid
                FROM {grade_items} i
                WHERE itemmodule = :modulename
                  AND iteminstance = :instance
                  AND courseid = :courseid";
        tool_gradefilter_sql_add_conditions($sql);
        $params = [
            'modulename' => $event->other['modulename'],
            'instance' => $event->other['instanceid'],
            'courseid' => $event->courseid,
        ];
        $item = $DB->get_record_sql($sql, $params);

        if (!$item) {
            return;
        }

        $itemtype = tool_gradefilter_get_item_type($item->name, $item->type);

        tool_gradefilter_check_grade_pass($item->id, $item->pass, $item->grademax, $itemtype);

        if ($itemtype === 0) {
            tool_gradefilter_init_item_grades($item->id);
            tool_gradefilter_check_bonus($item->courseid);
        }
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
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        if ($event->crud != "u") {
            return;
        }

        global $DB, $CFG;
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

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
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        if ($event->crud != "d") {
            return;
        }

        global $DB, $CFG;
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');
        $DB->delete_records('tool_gradefilter', ['itemid' => $event->objectid]);
        tool_gradefilter_check_bonus($event->courseid);
    }

    /**
     * @param \core\event\user_enrolment_created $event
     * @return void
     * @throws \dml_exception
     */
    public static function tool_gradefilter_handle_user_enrolled(\core\event\user_enrolment_created $event) {
        $ispluginenabled = get_config('tool_gradefilter', 'isenabled');
        if (!$ispluginenabled) {
            return;
        }

        global $DB, $CFG;
        require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $data = $event->get_data();
        $userid = $data['relateduserid'];
        $courseid = $data['courseid'];

        $isstudent = false;
        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $userroles = get_user_roles(\context_course::instance($courseid), $userid);
        foreach ($userroles as $role) {
            if ($role->id == $studentroleid) {
                $isstudent = true;
                break;
            }
        }

        if ($isstudent) {
            $gradeitems = \grade_item::fetch_all(['courseid' => $courseid]);
            foreach ($gradeitems as $item) {
                $itemtype = tool_gradefilter_get_item_type($item->itemname, $item->itemtype);
                if ($itemtype === 0) {
                    tool_gradefilter_generate_grade($item->id, $userid, $courseid);
                }
            }
        }
    }
}
