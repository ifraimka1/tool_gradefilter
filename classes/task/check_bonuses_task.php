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
 * This task checks bonus grades and exclude/include them.
 *
 * @package     tool_gradefilter
 * @copyright   2024 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_gradefilter\task;

use DateTime;

defined('MOODLE_INTERNAL') || die();

class check_bonuses_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('check_bonuses_task', 'tool_gradefilter');
    }

    public function execute() {
        global $DB;

        // Узнаем текущую дату и время
        $currentDateTime = new DateTime();

        // Определяем номер текущего месяца
        $currentMonth = $currentDateTime->format('n'); // 'n' возвращает номер месяца без ведущего нуля

        // Проверяем номер месяца и определяем нужную Unix-метку
        if ($currentMonth < 8) {
            // Если номер меньше 8, берем первый день первого месяца текущего года
            $startDateTime = new DateTime($currentDateTime->format('Y') . '-01-01 00:00:00');
        } else {
            // Если номер равен или больше 8, берем первый день августа текущего года
            $startDateTime = new DateTime($currentDateTime->format('Y') . '-08-01 00:00:00');
        }

        // Получаем Unix-метку
        $restriction = $startDateTime->getTimestamp();

        /**
         * Запрос на получение бонусных оценок
         * Возвращает количество "исключенных" оценок по пользователям
         */ 
        $sql = "SELECT 
                    gi.id,
                    gg.userid,
                    SUM(CASE WHEN gg.excluded > 0 THEN 1 ELSE 0 END) AS ex_count
                FROM {grade_items} gi
                    JOIN {grade_items} gi2 ON gi2.courseid = gi.courseid
                    JOIN {grade_grades} gg ON gg.itemid = gi2.id
                WHERE gi2.itemtype NOT LIKE 'course'
                    AND gi.aggregationcoef = 1
                    AND gi.id != gi2.id
                    AND gi.timecreated >= :restriction
                GROUP BY gi.id, gg.userid";
        $params = ['restriction' => $restriction];
        $bonuses = $DB->get_recordset_sql($sql, $params);

        foreach ($bonuses as $bonus) {
            $updateparams = ['itemid' => $bonus->id, 'userid' => $bonus->userid];

            // Получим текущее значение excluded, чтобы не обновлять без необходимости
            $current_excluded = $DB->get_field('grade_grades', 'excluded', $updateparams);

            // Если у пользователя нет исключенных оценок, "включаем" бонусные баллы. Иначе - исключаем
            if ($bonus->ex_count == 0 && $current_excluded != 0) {
                $DB->set_field('grade_grades', 'excluded', 0, $updateparams);
            } else if ($bonus->ex_count != 0 && $current_excluded == 0) {
                $DB->set_field('grade_grades', 'excluded', $currentDateTime->getTimestamp(), $updateparams);
            }
        }
    }
}
