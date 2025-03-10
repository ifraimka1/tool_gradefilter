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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/gradefilter/lib.php');

/**
 * Unit tests for tool_gradefilter_lib
 */
class tool_gradefilter_lib_testcase extends advanced_testcase {

    /**
     * table        - items
     * WHERE        - none
     * JOIN         - none
     * conditions   - none
     */
    public function test_simple() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 0, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items}";
        $expected = "SELECT * FROM {grade_items}"; // Никаких изменений
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - grades
     * WHERE        - none
     * JOIN         - none
     * conditions   - old
     */
    public function test_grades_cold() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_grades}";
        $expected = "SELECT * FROM {grade_grades} WHERE timemodified > 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * WHERE        - none
     * JOIN         - none
     * conditions   - old
     */
    public function test_items_cold() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 0, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items}";
        $expected = "SELECT * FROM {grade_items} WHERE timecreated > 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * WHERE        - none
     * JOIN         - none
     * conditions   - new
     */
    public function test_cnew() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 0, 'tool_gradefilter');
        set_config('ignorenewgrades', 1, 'tool_gradefilter');
        set_config('ignorenewdate', 1672531200, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items}";
        $expected = "SELECT * FROM {grade_items} WHERE timecreated < 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items
     * WHERE        - none
     * JOIN         - none
     * conditions   - oldnew
     */
    public function test_coldnew() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 1, 'tool_gradefilter');
        set_config('ignorenewdate', 1672531200, 'tool_gradefilter');

        $sql = "SELECT * FROM {grade_items}";
        $expected = "SELECT * FROM {grade_items} WHERE timecreated > 1672531200 AND timecreated < 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }

    /**
     * table        - items + grades
     * WHERE        - none
     * JOIN         - yes
     * conditions   - oldnew
     */
    public function test_join_coldnew() {
        $this->resetAfterTest();
        set_config('ignoreoldgrades', 1, 'tool_gradefilter');
        set_config('ignoreolddate', 1672531200, 'tool_gradefilter');
        set_config('ignorenewgrades', 1, 'tool_gradefilter');
        set_config('ignorenewdate', 1672531200, 'tool_gradefilter');

        $sql =
            "SELECT *
            FROM {grade_items} i
            JOIN {grade_grades} g ON g.itemid = i.id";
        $expected =
            "SELECT *
            FROM {grade_items} i
            JOIN {grade_grades} g ON g.itemid = i.id WHERE g.timemodified > 1672531200 AND g.timemodified < 1672531200 AND i.timecreated > 1672531200 AND i.timecreated < 1672531200";
        tool_gradefilter_sql_add_conditions($sql);
        $this->assertEquals($expected, $sql);
    }    
}