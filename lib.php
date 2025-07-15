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
 * Возвращает код типа элемента оценки:
 * 0 - обычный (regular) элемент;
 * 1 - бонус/экзамен;
 * 2 - курс/категория.
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
    $result = 0;

    if ($itemtype === 'course' || $itemtype === 'category') {
        $result = 2;
    } else if (preg_match($pattern, $itemname)) {
        $result = 1;
    }

    return $result;
}

/**
 * Проверяет, набран ли проходной балл. Если нет - зануляет finalgrade.
 *
 * @param integer $gradeid
 * @param integer $itemtype
 *
 * @return void
 * @throws dml_exception
 */
function tool_gradefilter_check_grade($gradeid, $itemtype)
{
    global $DB;

    $sql = "SELECT
                i.id AS id,
                i.gradepass AS pass,
                i.courseid AS courseid,
                g.rawgrade AS rawgrade,
                g.finalgrade AS finalgrade,
                g.userid AS userid,
                g.overridden AS overridden
            FROM {grade_grades} g
            JOIN {grade_items} i ON g.itemid = i.id
            WHERE g.id = :gradeid
              AND g.locked = 0";
    tool_gradefilter_sql_add_conditions($sql);
    $params = ['gradeid' => $gradeid];
    $item = $DB->get_record_sql($sql, $params);

    if (!$item) return;

    $newgrade = new stdClass();
    $newgrade->id = $gradeid;

    if ($itemtype === 0) {
        $gradestatuschanged = false;

        if ($item->overridden != 0 && ($item->rawgrade < $item->pass || $item->rawgrade === null)
            || $item->overridden == 0 && ($item->finalgrade < $item->pass || $item->finalgrade === null)) {
            // Исключаем.
            if (($item->rawgrade !== null || $item->finalgrade !== 0) && $item->overridden = 0) {
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
        $exgrade = $DB->record_exists('tool_gradefilter', ['userid' => $item->userid]);
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
 * Проверяет бонусы в курсе. Если передан userid, проверит только его.
 * Зануляет/разнуляет бонусы, в зависимости от наличия зануленных оценок.
 *
 * @param integer $courseid
 * @param integer $userid
 *
 * @return void
 * @throws dml_exception
 */
function tool_gradefilter_check_bonus($courseid, $userid = null)
{
    global $DB;

    $sql = "SELECT
                g.id AS id,
                g.rawgrade AS rawgrade,
                g.userid AS userid,
                i.itemname AS name,
                i.itemtype AS type
            FROM {grade_grades} g
            JOIN {grade_items} i ON i.id = g.itemid
            WHERE i.courseid = :courseid
              AND i.itemtype NOT LIKE 'course'
              AND i.itemtype NOT LIKE 'category'";
    tool_gradefilter_sql_add_conditions($sql);
    $params = ['courseid' => $courseid];
    if ($userid) {
        $sql .= " AND g.userid = :userid";
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

    $sql = "SELECT gf.id
            FROM {tool_gradefilter} gf
            JOIN {grade_items} i ON i.id = gf.itemid
            WHERE i.courseid = :courseid
            AND gf.userid = :userid";
    tool_gradefilter_sql_add_conditions($sql);


    foreach ($bonusgrades as $grade) {
        $params['userid'] = $grade->userid;
        $exgrades = $DB->record_exists_sql($sql, $params);

        $newgrade = new stdClass();
        if ($exgrades) {
            $newgrade->finalgrade = 0;
        } else {
            $newgrade->finalgrade = $grade->rawgrade;
        }

        $newgrade->id = $grade->id;
        $DB->update_record('grade_grades', $newgrade);
    }
}

/**
 * Проверяет корректность проходного балла.
 * Вернет true, если порог изменился, иначе false.
 *
 * @param integer $itemid
 * @param integer $pass item.gradepass
 * @param integer $max item.grademax
 * @param integer $itemtype type from get_item_type
 * @param bool $needsupdate
 *
 * @return bool
 * @throws dml_exception
 */
function tool_gradefilter_check_grade_pass($itemid, $pass, $max, $itemtype, $needsupdate = false)
{
    global $DB;

    $ispasschanged = false;

    if ($itemtype === 0) {
        $correctpass = $max * 0.6;
        $pass = floatval($pass);
        if (abs($pass - $correctpass) >= 0.01 || $needsupdate) {
            $DB->set_field('grade_items', 'gradepass', $correctpass, ['id' => $itemid]);
            $ispasschanged = true;
        }
    } else if ($itemtype === 1 && $pass != 0) {
        $DB->set_field('grade_items', 'gradepass', 0, ['id' => $itemid]);
        $DB->delete_records('tool_gradefilter', ['itemid' => $itemid]);
        $ispasschanged = true;
    }

    return $ispasschanged;
}

/**
 * Устанавливает проходной балл, проверяет все оценки.
 *
 * @return void
 */
function tool_gradefilter_enable_plugin()
{
    global $DB;
    // 1. Получаем все элементы оценок, кроме курсов и категорий
    $sql = "SELECT
                i.id,
                i.itemname AS name,
                i.itemtype AS type,
                i.gradepass AS pass,
                i.grademax AS max
            FROM {grade_items} i
            WHERE i.itemtype NOT LIKE 'course'
              AND i.itemtype NOT LIKE 'category'";
    tool_gradefilter_sql_add_conditions($sql);
    $items = $DB->get_recordset_sql($sql);

    /**
     * 2. Находим обычные (regular) элементы,
     * записываем в отдельный массив,
     * проверяем их порог.
     */
    $regularitems = [];
    foreach ($items as $item) {
        $itemtype = tool_gradefilter_get_item_type($item->name, $item->type);
        if ($itemtype === 0) {
            tool_gradefilter_check_grade_pass($item->id, $item->pass, $item->max, $itemtype);
            array_push($regularitems, $item->id);
        }
    }
    $items->close();
    if (sizeof($regularitems) === 0) {return;}

    // 3. Получаем оценки по обычным заданиям.
    [$sqlin, $params] = $DB->get_in_or_equal($regularitems, SQL_PARAMS_QM);
    unset($regularitems);

    $sql = "SELECT
                g.id,
                g.rawgrade,
                g.finalgrade,
                g.itemid,
                g.userid,
                i.courseid
            FROM {grade_grades} g
            JOIN {grade_items} i ON i.id = g.itemid
            WHERE g.overridden = 0
              AND g.locked = 0
              AND i.id $sqlin";
    tool_gradefilter_sql_add_conditions($sql);
    $grades = $DB->get_recordset_sql($sql, $params);

    // 4. Проверяем эти оценки. Проверка бонусов включена в check_grade.
    // TODO: "отвязать" проверку бонусов от проверки оценок.
    foreach ($grades as $grade) {
        tool_gradefilter_check_grade($grade->id, 0);
        echo "Оценка с id = {$grade->id} обработана<br>";
        flush();
    }
    $grades->close();
}

/**
 * Откатывает все изменения оценок
 *
 * @return void
 * @throws dml_exception
 */
function tool_gradefilter_disable_plugin()
{
    global $DB;

    $sql = "SELECT g.id, g.rawgrade
            FROM {grade_grades} g
            WHERE g.rawgrade IS NOT NULL
              AND g.finalgrade IS NOT NULL
              AND ABS(g.rawgrade - g.finalgrade) > 0.00001
              AND g.overridden = 0
              AND g.locked = 0";
    $grades = $DB->get_recordset_sql($sql);
    tool_gradefilter_sql_add_conditions($sql);

    $newgrade = new stdClass();
    foreach ($grades as $grade) {
        $newgrade->id = $grade->id;
        $newgrade->finalgrade = $grade->rawgrade;
        $DB->update_record('grade_grades', $newgrade);
    }

    $DB->delete_records('tool_gradefilter');
}

/**
 * Добавляет в SQL все условия, зависящие от настроек плагина
 *
 * @param string $sql
 *
 * @return void
 */
function tool_gradefilter_sql_add_conditions(&$sql)
{
    tool_gradefilter_sql_add_ignoredate($sql);
    tool_gradefilter_sql_add_ignorecourse($sql);
}

/**
 * Добавляет в SQL условия игнора курсов по ключевым словам.
 *
 * @param string $sql - SQL-запрос
 *
 * @return void
 * @throws dml_exception
 */
function tool_gradefilter_sql_add_ignorecourse(&$sql)
{
    $keywords = get_config('tool_gradefilter', 'ignorecoursekeywords');

    if (!$keywords) return;

    $formattedkeywords = array_map(function ($keyword) {
        return "c.fullname NOT LIKE '%" . trim($keyword) . "%'";
    }, explode(';', $keywords));
    $condition = implode(' AND ', $formattedkeywords);

    $coursepos = strpos($sql, '{course}');

    if ($coursepos === false) {
        $wherepos = strpos($sql, 'WHERE');
        $join = "JOIN {course} c ON c.id = i.courseid ";
        $sql = substr_replace($sql, $join, $wherepos, 0);
    }

    $sql .= " AND " . $condition;
}

/**
 * Добавляет в SQL условия игнора старых/новых оценок.
 *
 * @param string $sql - SQL-запрос
 *
 * @return void
 * @throws dml_exception
 */
function tool_gradefilter_sql_add_ignoredate(&$sql)
{
    $conditions = [];
    $ignoreoldgrades = get_config('tool_gradefilter', 'ignoreoldgrades');
    $ignorenewgrades = get_config('tool_gradefilter', 'ignorenewgrades');

    if ($ignoreoldgrades) {
        array_push($conditions, ' > ' . get_config('tool_gradefilter', 'ignoreolddate'));
    }
    if ($ignorenewgrades) {
        array_push($conditions, ' < ' . get_config('tool_gradefilter', 'ignorenewdate'));
    }

    if ($conditions) {
        $tables = [
            [
                'pattern' => '{grade_grades}',
                'field' => 'timemodified',
                'alias' => 'g.'
            ],
            [
                'pattern' => '{grade_items}',
                'field' => 'timecreated',
                'alias' => 'i.'
            ]
        ];

        foreach ($tables as $table) {
            if (strpos($sql, $table['pattern']) !== false) {
                foreach ($conditions as $condition) {
                    $sql .= " AND " . $table['alias'] . $table['field'] . $condition;
                }
            }
        }
    }
}
