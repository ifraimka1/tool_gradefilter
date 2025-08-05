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
 * Unit tests for adding sql conditions functions.
 *
 * @package     tool_gradefilter
 * @copyright   2025 Solomonov Ifraim <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

/**
 * Unit tests for tool_gradefilter_lib
 * @group tool_gradefilter
 * @runInSeparateProcess
 */
class tool_gradefilter_lib_sql_testcase extends advanced_testcase {
    /**
     * Тестирование tool_gradefilter_get_item_type
     */
    public function test_tool_gradefilter_get_item_type() {
        // Тест 1: Обычный элемент
        $this->assertEquals(0, tool_gradefilter_get_item_type("Assignment", "mod"));

        // Тест 2: Бонус/экзамен
        $this->assertEquals(1, tool_gradefilter_get_item_type("Bonus Assignment", "mod"));

        // Тест 3: Курс или категория
        $this->assertEquals(2, tool_gradefilter_get_item_type("", "course"));
        $this->assertEquals(2, tool_gradefilter_get_item_type("", "category"));
    }

    /**
     * Тестирование tool_gradefilter_check_grade_pass
     * @runInSeparateProcess
     */
    public function test_tool_gradefilter_check_grade_pass() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $gradeitem = $this->getDataGenerator()->create_grade_item([
            'courseid' => $course->id,
            'gradepass' => 50,
            'grademax' => 100,
            'itemtype' => 'mod',
        ]);

        // Тест 1: Корректный проходной балл
        $this->assertFalse(tool_gradefilter_check_grade_pass($gradeitem->id, 60, 100, 0));

        // Тест 2: Некорректный проходной балл
        $this->assertTrue(tool_gradefilter_check_grade_pass($gradeitem->id, 40, 100, 0));

        // Проверка обновления в базе данных
        $updateditem = $DB->get_record('grade_items', ['id' => $gradeitem->id]);
        $this->assertEquals(60, $updateditem->gradepass); // Проверяем, что gradepass обновлен
    }

    /**
     * Тестирование tool_gradefilter_enable_plugin
     */
    public function test_tool_gradefilter_enable_plugin() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $gradeitem = $this->getDataGenerator()->create_grade_item([
            'courseid' => $course->id,
            'itemname' => 'Regular Assignment',
            'itemtype' => 'mod',
            'gradepass' => 50,
            'grademax' => 100,
        ]);

        tool_gradefilter_enable_plugin();

        $updateditem = $DB->get_record('grade_items', ['id' => $gradeitem->id]);
        $this->assertEquals(60, $updateditem->gradepass);
    }

    /**
     * Тестирование tool_gradefilter_disable_plugin
     */
    public function test_tool_gradefilter_disable_plugin() {
        global $DB;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $gradeitem = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id]);
        $grade = $this->getDataGenerator()->create_grade_grade([
            'itemid' => $gradeitem->id,
            'rawgrade' => 80,
            'finalgrade' => 0,
            'overridden' => 0,
        ]);

        tool_gradefilter_disable_plugin();

        $updatedgrade = $DB->get_record('grade_grades', ['id' => $grade->id]);
        $this->assertEquals(80, $updatedgrade->finalgrade);
    }

    /**
     * table        - items
     * conditions   - none
     * @group tool_gradefilter_ignore_time
     */
    public function test_simple() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 0, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items} i";
        $expected = "SELECT * FROM {grade_items} i"; // Никаких изменений
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - grades
     * conditions   - old
     * @group tool_gradefilter_ignore_time
     */
    public function test_grades_old() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_grades} g
                WHERE g.rawgrade = 5";
        $expected =
            "SELECT * FROM {grade_grades} g
                WHERE g.rawgrade = 5 AND g.timemodified > 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * conditions   - old
     * @group tool_gradefilter_ignore_time
     */
    public function test_items_old() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items} i WHERE i.id = 1";
        $expected = "SELECT * FROM {grade_items} i WHERE i.id = 1 AND i.timecreated > 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * conditions   - new
     * @group tool_gradefilter_ignore_time
     */
    public function test_new() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 0, 'tool_gradefilter');
        set_config('ignorenewgrades', 1, 'tool_gradefilter');
        set_config('ignorenewdate', 1672531200, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items} i WHERE i.id = 1";
        $expected = "SELECT * FROM {grade_items} i WHERE i.id = 1 AND i.timecreated < 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * conditions   - old, new
     * @group tool_gradefilter_ignore_time
     */
    public function test_oldnew() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 1, 'tool_gradefilter');
        set_config('ignorenewdate', 1672531200, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items} i WHERE i.id = 1";
        $expected = "SELECT * FROM {grade_items} i WHERE i.id = 1 AND i.timecreated > 1672531200 AND i.timecreated < 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items + grades
     * conditions   - oldnew
     * @group tool_gradefilter_ignore_time
     */
    public function test_join_oldnew() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 1, 'tool_gradefilter');
        set_config('ignorenewdate', 1672531200, 'tool_gradefilter');

        $sql =
            "SELECT *
            FROM {grade_items} i
            JOIN {grade_grades} g ON g.itemid = i.id
            WHERE i.id = 1";
        $expected =
            "SELECT *
            FROM {grade_items} i
            JOIN {grade_grades} g ON g.itemid = i.id
            WHERE i.id = 1 AND g.timemodified > 1672531200 AND g.timemodified < 1672531200 AND i.timecreated > 1672531200 AND i.timecreated < 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * conditions   - keyword
     * @group tool_gradefilter_ignore_course
     */
    public function test_courseignore_notable() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 0, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');
        set_config('ignorecoursekeywords', 'добор', 'tool_gradefilter');

        $sql =
            "SELECT *
            FROM {grade_items} i
            WHERE i.id = 1";
        $expected =
            "SELECT *
            FROM {grade_items} i
            JOIN {course} c ON c.id = i.courseid WHERE i.id = 1 AND c.fullname NOT LIKE '%добор%'";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * conditions   - keywords
     * @group tool_gradefilter_ignore_course
     */
    public function test_courseignores_notable() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 0, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');
        set_config('ignorecoursekeywords', 'добор;ввид', 'tool_gradefilter');

        $sql =
            "SELECT *
            FROM {grade_items} i
            WHERE i.id = 1";
        $expected =
            "SELECT *
            FROM {grade_items} i
            JOIN {course} c ON c.id = i.courseid WHERE i.id = 1 AND c.fullname NOT LIKE '%добор%' AND c.fullname NOT LIKE '%ввид%'";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items, course
     * conditions   - keywords
     * @group tool_gradefilter_ignore_course
     */
    public function test_courseignores_table() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 0, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');
        set_config('ignorecoursekeywords', 'добор;ввид', 'tool_gradefilter');

        $sql =
            "SELECT *
            FROM {grade_items} i
            JOIN {course} c ON c.id = i.courseid
            WHERE i.id = 1";
        $expected =
            "SELECT *
            FROM {grade_items} i
            JOIN {course} c ON c.id = i.courseid
            WHERE i.id = 1 AND c.fullname NOT LIKE '%добор%' AND c.fullname NOT LIKE '%ввид%'";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items, grades, course
     * conditions   - old, new, keywords
     * @group tool_gradefilter_ignore_course
     * @group tool_gradefilter_ignore_time
     */
    public function test_oldnew_courseignores() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 1, 'tool_gradefilter');
        set_config('ignorenewdate', 1672531200, 'tool_gradefilter');
        set_config('ignorecoursekeywords', 'добор;ввид', 'tool_gradefilter');

        $sql =
            "SELECT *
            FROM {grade_grades} g
            JOIN {grade_items} i ON i.id = g.itemid
            JOIN {course} c ON c.id = i.courseid
            WHERE i.id = 1";
        $expected =
            "SELECT *
            FROM {grade_grades} g
            JOIN {grade_items} i ON i.id = g.itemid
            JOIN {course} c ON c.id = i.courseid
            WHERE i.id = 1 AND g.timemodified > 1672531200 AND g.timemodified < 1672531200"
            ." AND i.timecreated > 1672531200 AND i.timecreated < 1672531200"
            ." AND c.fullname NOT LIKE '%добор%' AND c.fullname NOT LIKE '%ввид%'";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }
}
