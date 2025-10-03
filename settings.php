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

        // Настройка ignoreoldgrades.
        $settingspage->add(new admin_setting_configcheckbox(
            'tool_gradefilter/ignoreoldgrades',
            new lang_string('ignoreoldgrades', 'tool_gradefilter'),
            new lang_string('ignoreoldgrades_desc', 'tool_gradefilter'),
            0 // По умолчанию выключено.
        ));

        // Настройка ignoreolddate (unix).
        $settingspage->add(new admin_setting_configtext(
            'tool_gradefilter/ignoreolddate',
            new lang_string('ignoreolddate', 'tool_gradefilter'),
            new lang_string('ignoreolddate_desc', 'tool_gradefilter'),
            0,
            PARAM_INT
        ));

        // Настройка ignorenewgrades.
        $settingspage->add(new admin_setting_configcheckbox(
            'tool_gradefilter/ignorenewgrades',
            new lang_string('ignorenewgrades', 'tool_gradefilter'),
            new lang_string('ignorenewgrades_desc', 'tool_gradefilter'),
            0 // По умолчанию выключено.
        ));

        // Настройка ignorenewdate (unix).
        $settingspage->add(new admin_setting_configtext(
            'tool_gradefilter/ignorenewdate',
            new lang_string('ignorenewdate', 'tool_gradefilter'),
            new lang_string('ignorenewdate_desc', 'tool_gradefilter'),
            0,
            PARAM_INT
        ));

        // Раздел "Игнорирование курсов".
        $settingspage->add(new admin_setting_heading(
            'tool_gradefilter_ignore_courses_heading',
            new lang_string('ignorecoursesheading', 'tool_gradefilter'),
            new lang_string('ignorecoursesdesc', 'tool_gradefilter')
        ));

        // Текстовое поле для ключевых слов.
        $settingspage->add(new admin_setting_configtextarea(
            'tool_gradefilter/ignorecoursekeywords',
            new lang_string('ignorecoursekeywords', 'tool_gradefilter'),
            new lang_string('ignorecoursekeywordsdesc', 'tool_gradefilter'),
            'добор',
            PARAM_TEXT
        ));

        $runbutton = html_writer::tag(
            'button',
            get_string('runplugin', 'tool_gradefilter'),
            ['id' => 'runplugin', 'class' => 'btn btn-primary']
        );
        $settingspage->add(new admin_setting_description('tool_gradefilter_btn_run', '', $runbutton));

        $resetbutton = html_writer::tag(
            'button',
            get_string('resetplugin', 'tool_gradefilter'),
            ['id' => 'resetplugin', 'class' => 'btn btn-secondary']
        );
        $settingspage->add(new admin_setting_description('tool_gradefilter_btn_reset', '', $resetbutton));
    }

    $ADMIN->add('root', $settingspage);

    $PAGE->requires->js_call_amd('tool_gradefilter/settings', 'init');
}
