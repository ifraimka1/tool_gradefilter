<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     tool_gradefilter
 * @category    string
 * @copyright   2024 Solomonov Ifraim <solomonov@sfedu.ru>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Фильтр оценок';

$string['settings_main'] = 'Настройки БРС';
$string['isenabled'] = 'Включить плагин';
$string['isenabled_desc'] = 'Включает обработку оценок и заданий. Отключение не откатит оценки и настройки к прежним значениям.';
$string['ignoreoldgrades'] = 'Игнорировать старые оценки.';
$string['ignoreoldgrades_desc'] = 'Активируйте эту опцию, чтобы игнорировать оценки/задания старее заданной даты.';
$string['ignoreolddate'] = 'Дата игнорирования старых оценок';
$string['ignoreolddate_desc'] = 'Задайте дату в формате Unix, и оценки/задания старше этой даты не будут обрабатываться плагином.';
$string['ignorenewgrades'] = 'Игнорировать новые оценки';
$string['ignorenewgrades_desc'] = 'Активируйте эту опцию, чтобы игнорировать оценки/задания новее заданной даты.';
$string['ignorenewdate'] = 'Дата игнорирования новых оценок';
$string['ignorenewdate_desc'] = 'Задайте дату в формате Unix, и оценки/задания новее этой даты не будут обрабатываться плагином.';
$string['ignorecoursesheading'] = 'Исключения для курсов';
$string['ignorecoursesdesc'] = 'Настройки для исключения курсов из обработки.';
$string['ignorecoursekeywords'] = 'Ключевые слова';
$string['ignorecoursekeywordsdesc'] = 'Курсы, в названии которых есть эти ключевые слова, будут исключены из обработки. Используйте точку с запятой ";" для разделения ключевых слов.';