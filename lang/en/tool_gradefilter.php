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

$string['pluginname'] = 'Grade filter';

$string['settings_main'] = 'Gradefilter settings';
$string['isenabled'] = 'Enable plugin';
$string['isenabled_desc'] = 'Enable filtering of grades and grade_items. Disabling will not reset previous settings and grades.';
$string['ignoreoldgrades'] = 'Ignore old grades';
$string['ignoreoldgrades_desc'] = 'Enable this option to ignore grades older than a specific date.';
$string['ignoreolddate'] = 'Ignore date for old grades';
$string['ignoreolddate_desc'] = 'Specify the date in Unix format. Grades and items, created before will be ignored.';
$string['ignorenewgrades'] = 'Ignore new grades';
$string['ignorenewgrades_desc'] = 'Enable this option to ignore grades newer than a specific date.';
$string['ignorenewdate'] = 'Ignore date for new grades';
$string['ignorenewdate_desc'] = 'Specify the date in Unix format. Grades and items, created before will be ignored.';
$string['ignorecoursesheading'] = 'Ignoring Courses';
$string['ignorecoursesdesc'] = 'Settings for ignoring courses based on specific criteria.';
$string['ignorecoursekeywords'] = 'Keywords';
$string['ignorecoursekeywordsdesc'] = 'Enter keywords to ignore courses. Separate multiple keywords with semicolon.';