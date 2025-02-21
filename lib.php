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
 * Gradefilter functions.
 *
 * @package     tool_gradefilter
 * @copyright   2025 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns code of type of gradeitem:
 * 0 - regular item;
 * 1 - bonus/exam;
 * 2 - course/category.
 * 
 * @param string $itemname
 * @param string $itemtype
 * 
 * @return integer type of item.
 */
function tool_gradefilter_get_item_type($itemname, $itemtype)
{
    // TODO: сделать регулярку настраиваемой
    $pattern = '/добор|экзамен|бонус|bonus|additional|exam/iu';
    $result = 0; // Regular item.

    if ($itemtype === 'course' || $itemtype === 'category') {
        $result = 2; // Course/category.
    } else if (preg_match($pattern, $itemname)) {
        $result = 1; // Bonus/exam.
    }

    return $result;
}

/**
 * Checks if grade is higher than gradepass and overrides finalgrade.
 * 
 * @param integer $gradeid
 * @param integer $itemtype
 * 
 * @return void
 */
function tool_gradefilter_check_grade($gradeid, $itemtype)
{
    // TODO: добавить возможность игнора оценок/курсов.
    global $DB;

    $sql = "
            SELECT
                items.id AS id,
                items.gradepass AS pass,
                items.courseid AS courseid,
                grades.rawgrade AS rawgrade,
                grades.finalgrade AS finalgrade,
                grades.userid AS userid
            FROM {grade_grades} grades
            JOIN {grade_items} items ON grades.itemid = items.id
            WHERE grades.id = :gradeid";
    $params = ['gradeid' => $gradeid];
    $item = $DB->get_record_sql($sql, $params);

    $newgrade = new stdClass();
    $newgrade->id = $gradeid;

    if ($itemtype === 0) {
        $gradestatuschanged = false;

        if ($item->rawgrade < $item->pass || $item->rawgrade === null) {
            // Исключаем.
            if ($item->rawgrade !== null || $item->finalgrade !== 0) {
                $newgrade->finalgrade = 0;
                $DB->update_record('grade_grades', $newgrade);
            }
            if (!$DB->record_exists('tool_gradefilter', ['gradeid' => $gradeid])) {
                $DB->insert_record('tool_gradefilter', ['gradeid' => $gradeid, 'itemid' => $item->id, 'userid' => $item->userid]);
                $gradestatuschanged = true;
            }
        } else {
            // Включаем.
            if ($DB->get_record('tool_gradefilter', ['gradeid' => $gradeid])) {
                $DB->delete_records('tool_gradefilter', ['gradeid' => $gradeid]);
                $gradestatuschanged = true;
            }
        }

        if ($gradestatuschanged) {
            tool_gradefilter_check_bonus($item->courseid, $item->userid);
        }
    } else if ($itemtype === 1) {
        $exgrade = $DB->get_record('tool_gradefilter', ['userid' => $item->userid]);
        if ($exgrade && $item->finalgrade != 0) {
            $newgrade->finalgrade = 0;
            $DB->update_record('grade_grades', $newgrade);
        } else if (!$exgrade && $item->finalgrade != $item->rawgrade) {
            $newgrade->finalgrade = $item->rawgrade;
            $DB->update_record('grade_grades', $newgrade);
        }
    }
}

/**
 * Checks bonuses on course. If userid is defined, checks specific user.
 * Disables/enables bonuses based on the existence of excluded grades.
 * 
 * @param integer $courseid
 * @param integer $userid
 * 
 * @return void
 */
function tool_gradefilter_check_bonus($courseid, $userid = null)
{
    global $DB;

    $sql = "SELECT
                grades.id AS id,
                grades.rawgrade AS rawgrade,
                items.itemname AS name,
                items.itemtype AS type
            FROM {grade_grades} grades
            JOIN {grade_items} items ON items.id = grades.itemid
            WHERE items.courseid = :courseid";            
    $params = ['courseid' => $courseid];

    if ($userid) {
        $sql .= " AND grades.userid = :userid";
        $params['userid'] = $userid;
    }

    $grades = $DB->get_records_sql($sql, $params);

    $bonusgrades = [];

    foreach ($grades as $grade) {
        if (tool_gradefilter_get_item_type($grade->name, $grade->type) === 1) {
            array_push($bonusgrades, $grade);
        }
    }

    if (!$bonusgrades) return;

    $sql = "
        SELECT gf.id
        FROM {tool_gradefilter} gf
        JOIN {grade_items} items ON items.id = gf.itemid
        WHERE items.courseid = :courseid";

    if ($userid !== -1) {
        $sql .= " AND gf.userid = :userid";
    }

    $exgrades = $DB->record_exists_sql($sql, $params);

    $newgrade = new stdClass();
    $newgrade->finalgrade = 0;

    foreach ($bonusgrades as $grade) {
        if (!$exgrades) {
            $newgrade->finalgrade = $grade->rawgrade;
        }

        $newgrade->id = $grade->id;
        $DB->update_record('grade_grades', $newgrade);
    }
}
