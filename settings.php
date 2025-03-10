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
 * Admin settings.
 *
 * @package     tool_gradefilter
 * @copyright   2025 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/choicegroup/lib.php');

if ($hassiteconfig) {
    $ADMIN->add('grades', new admin_category('tool_gradefilter_settings', new lang_string('pluginname', 'tool_gradefilter')));
    $settingspage = new admin_settingpage('mainsettings', new lang_string('settings_main', 'tool_gradefilter'));

    if ($ADMIN->fulltree) {
        $settingspage->add(new admin_setting_configcheckbox(
            'tool_gradefilter/isenabled',
            new lang_string('isenabled', 'tool_gradefilter'),
            new lang_string('isenabled_desc', 'tool_gradefilter'),
            1
        ));

        // Настройка ignoreoldgrades
        $settingspage->add(new admin_setting_configcheckbox(
            'tool_gradefilter/ignoreoldgrades',
            new lang_string('ignoreoldgrades', 'tool_gradefilter'),
            new lang_string('ignoreoldgrades_desc', 'tool_gradefilter'),
            0 // По умолчанию выключено
        ));

        // Настройка ignoredate (дата/время)
        $settingspage->add(new admin_setting_configtext(
            'tool_gradefilter/ignoredate',
            new lang_string('ignoredate', 'tool_gradefilter'),
            new lang_string('ignoredate_desc', 'tool_gradefilter'),
            0,
            PARAM_INT
        ));
    }

    $ADMIN->add('root', $settingspage);

    $PAGE->requires->js_call_amd('tool_gradefilter/settings', 'init');
}
