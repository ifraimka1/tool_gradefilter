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
 * Event observer function definition and returns.
 *
 * @package     tool_gradefilter
 * @copyright   2024 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_gradefilter;

defined('MOODLE_INTERNAL') || die();

class observer {
    public static function grade_item_updated(\core\event\grade_item_updated $event) {
        global $DB;

        $gradeitem = $DB->get_record('grade_items', ['id' => $event->objectid], 'id, aggregationcoef');

        // Проверяем, является ли оценка бонусной 
        if ($gradeitem->aggregationcoef == 1) {
            $DB->insert_record('tool_gradefilter_bonuses', ['itemid' => $gradeitem->id]);
        } else {
            $DB->delete_records('tool_gradefilter_bonuses', ['itemid' => $gradeitem->id]);
        } 
    }
}