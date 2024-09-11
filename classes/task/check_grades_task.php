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
 * This task include/exclude grades, if they're higher/lower than 60%.
 *
 * @package     tool_gradefilter
 * @copyright   2024 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_gradefilter\task;

defined('MOODLE_INTERNAL') || die();

class check_grades_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('check_grades_task', 'tool_gradefilter');
    }

    public function execute() {
        global $DB;
        
        $lastcheck = $this->get_last_run_time(); // Последний запуск
        if ($lastcheck != 0) {
            $lastcheck -= MINSECS * 2;  // 2 буферные минуты
        }
        $sql = "SELECT g.id, g.userid, g.finalgrade, g.rawgrademax, g.excluded, g.timemodified,
                        gi.id AS itemid, gi.courseid, gi.gradepass
                FROM {grade_grades} g
                    JOIN {grade_items} gi ON gi.id = g.itemid
                WHERE gi.itemtype NOT LIKE 'course' AND gi.aggregationcoef != 1
                    AND (g.timemodified >= :tm OR g.excluded > 0 OR g.timecreated >= :tc)";   
        $params = ['tm' => $lastcheck, 'tc' => $lastcheck];        

        $grades = $DB->get_recordset_sql($sql, $params);

        foreach ($grades as $grade) {
            // Проверим, есть ли оценка
            if (is_null($grade->timemodified)) {
                // Разблокируем задание, оценки по которому нет
                if ($grade->excluded != 0) {
                    $DB->set_field('grade_grades', 'excluded', 0, ['id' => $grade->id]);
                }
            } else {
                $correctgradepass = $grade->rawgrademax * 0.6;
                if ($grade->gradepass != $correctgradepass) {
                    $grade->gradepass = $correctgradepass;
                    $DB->set_field('grade_items', 'gradepass', $correctgradepass, ['id' => $grade->itemid]);
                }
                
                $timenow = time();
                // Если оценка меньше 60%
                if ($grade->finalgrade < $grade->gradepass && $grade->excluded == 0) {
                    // Установим флаг "Не оценивается" (excluded=1)
                    $DB->set_field('grade_grades', 'excluded', $timenow, ['id' => $grade->id]);
                } else if ($grade->finalgrade >= $grade->gradepass && $grade->excluded > 0) {
                    // Иначе убираем флаг "Не оценивается" (excluded=0)
                    $DB->set_field('grade_grades', 'excluded', 0, ['id' => $grade->id]);
                }
            }
        }
    }
}

