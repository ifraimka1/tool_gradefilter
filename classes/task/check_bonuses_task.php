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
                    cgi.courseid,
                    cgi.id AS coursegradeid,
                    bgi.id AS bonusid,
                    gg.userid,
                    SUM(CASE WHEN gg.excluded > 0 THEN 1 ELSE 0 END) AS ex_count
                FROM {grade_items} cgi
                    JOIN {grade_items} bgi ON bgi.courseid = cgi.courseid
                    JOIN {grade_items} egi ON egi.courseid = egi.courseid
                    JOIN {grade_grades} gg ON gg.itemid = egi.id
                WHERE cgi.itemtype LIKE 'course'
                    AND bgi.aggregationcoef = 1
                    AND egi.itemtype NOT LIKE 'course' AND egi.aggregationcoef != 1
                    AND cgi.id != bgi.id
                    AND bgi.id != egi.id
                    AND cgi.timecreated >= :ctc AND bgi.timecreated >= :btc
                GROUP BY courseid, coursegradeid, bonusid, userid";
        $params = ['ctc' => $restriction, 'btc' => $restriction];
        $grades = $DB->get_recordset_sql($sql, $params);

        foreach ($grades as $grade) {
            $bonusparams = ['itemid' => $grade->bonusid, 'userid' => $grade->userid];
            $coursegradeparams = ['itemid' => $grade->coursegradeid, 'userid' => $grade->userid];

            // Получим текущие значениz в БД, чтобы не обновлять без необходимости
            $currentexcluded = $DB->get_field('grade_grades', 'excluded', $bonusparams);
            $currenthidden = $DB->get_field('grade_grades', 'hidden', $coursegradeparams);

            // Если у пользователя нет исключенных оценок, "включаем" бонусные баллы и открываем оценку курса. Иначе - исключаем и скрываем соответственно
            if ($grade->ex_count == 0) {
                if ($currentexcluded != 0) $DB->set_field('grade_grades', 'excluded', 0, $bonusparams);
                if ($currenthidden != 0) $DB->set_field('grade_grades', 'hidden', 0, $coursegradeparams);
            } else if ($grade->ex_count != 0 && $currentexcluded == 0) {
                if ($currentexcluded == 0) $DB->set_field('grade_grades', 'excluded', time(), $bonusparams);
                if ($currenthidden == 0) $DB->set_field('grade_grades', 'hidden', 1, $coursegradeparams);
            }
        }
    }
}
