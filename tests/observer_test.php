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
 * Unit tests for observer.
 *
 * @package     tool_gradefilter
 * @copyright   2025 Solomonov Ifraim <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/gradefilter/classes/observer.php');

/**
 * Unit tests for tool_gradefilter_observer
 */
class tool_gradefilter_observer_testcase extends advanced_testcase {

    /**
     * Тестирование tool_gradefilter_handle_user_graded
     */
    public function test_tool_gradefilter_handle_user_graded() {
        global $DB;

        // Подготовка данных
        set_config('isenabled', 1, 'tool_gradefilter');

        $DB->insert_record('grade_items', [
            'id' => 1,
            'itemname' => 'Regular Assignment',
            'itemtype' => 'mod',
        ]);

        $event = \core\event\user_graded::create([
            'objectid' => 1,
            'other' => ['itemid' => 1],
        ]);

        // Вызов метода
        Observer::tool_gradefilter_handle_user_graded($event);

        // Проверка вызова tool_gradefilter_check_grade
        // (можно добавить mock или проверить изменения в базе данных)
    }

    /**
     * Тестирование tool_gradefilter_handle_item_created
     */
    public function test_tool_gradefilter_handle_item_created() {
        global $DB;

        // Подготовка данных
        set_config('isenabled', 1, 'tool_gradefilter');

        $DB->insert_record('grade_items', [
            'id' => 1,
            'itemname' => 'Regular Assignment',
            'itemtype' => 'mod',
            'gradepass' => 50,
            'grademax' => 100,
        ]);

        $event = \core\event\grade_item_created::create([
            'objectid' => 1,
            'crud' => 'c',
        ]);

        // Вызов метода
        Observer::tool_gradefilter_handle_item_created($event);

        // Проверка обновления проходного балла
        $item = $DB->get_record('grade_items', ['id' => 1]);
        $this->assertEquals(60, $item->gradepass); // 60% от 100
    }

    /**
     * Тестирование tool_gradefilter_handle_item_deleted
     */
    public function test_tool_gradefilter_handle_item_deleted() {
        global $DB;

        // Подготовка данных
        set_config('isenabled', 1, 'tool_gradefilter');

        $DB->insert_record('tool_gradefilter', [
            'itemid' => 1,
        ]);

        $event = \core\event\grade_item_deleted::create([
            'objectid' => 1,
        ]);

        // Вызов метода
        Observer::tool_gradefilter_handle_item_deleted($event);

        // Проверка удаления записей
        $this->assertFalse($DB->record_exists('tool_gradefilter', ['itemid' => 1]));
    }
}
