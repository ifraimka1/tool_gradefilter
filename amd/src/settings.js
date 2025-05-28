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
 * Admin settings enhancements.
 *
 * @copyright   2025 Ifraim Solomonov <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


define(['jquery'], function($) {
    return {
        init: function() {
            const ignoreOldGradesCheckbox = $('input[name="s_tool_gradefilter_ignoreoldgrades"]');
            const ignoreOldDateField = $('#admin-ignoreolddate');

            const ignoreNewGradesCheckbox = $('input[name="s_tool_gradefilter_ignorenewgrades"]');
            const ignoreNewDateField = $('#admin-ignorenewdate');

            if (ignoreOldGradesCheckbox.length && ignoreOldDateField.length) {
                const updateVisibilityOld = () => {
                    if (ignoreOldGradesCheckbox.is(':checked')) {
                        ignoreOldDateField.show();
                    } else {
                        ignoreOldDateField.hide();
                    }
                };

                const updateVisibilityNew = () => {
                    if (ignoreNewGradesCheckbox.is(':checked')) {
                        ignoreNewDateField.show();
                    } else {
                        ignoreNewDateField.hide();
                    }
                };


                updateVisibilityOld();
                updateVisibilityNew();

                ignoreOldGradesCheckbox.on('change', updateVisibilityOld);
                ignoreNewGradesCheckbox.on('change', updateVisibilityNew);

                $('#runplugin').on('click', function(e) {
                    e.preventDefault();
                    const form = $('<form>', {
                        method: 'POST',
                        action: M.cfg.wwwroot + '/admin/tool/gradefilter/run.php'
                    });

                    $('<input>').attr({
                        type: 'hidden',
                        name: 'sesskey',
                        value: M.cfg.sesskey
                    }).appendTo(form);

                    $('<input>').attr({
                        type: 'hidden',
                        name: 'run',
                        value: true
                    }).appendTo(form);

                    form.appendTo('body').submit();
                });

                $('#resetplugin').on('click', function(e) {
                    e.preventDefault();

                    const form = $('<form>', {
                        method: 'POST',
                        action: M.cfg.wwwroot + '/admin/tool/gradefilter/run.php'
                    });

                    $('<input>').attr({
                        type: 'hidden',
                        name: 'sesskey',
                        value: M.cfg.sesskey
                    }).appendTo(form);

                    $('<input>').attr({
                        type: 'hidden',
                        name: 'run',
                        value: false
                    }).appendTo(form);

                    form.appendTo('body').submit();
                });
            }
        }
    };
});