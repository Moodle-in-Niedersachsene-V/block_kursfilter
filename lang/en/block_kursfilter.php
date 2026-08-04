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
 * Englische Sprachdatei des Plugins block_kursfilter.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @author     Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['block_kursfilter:addinstance'] = 'Add a Course Filter block';
$string['block_kursfilter:myaddinstance'] = 'Add a Course Filter block to My page';
$string['btn_export'] = 'Export';
$string['btn_opencourse'] = 'Open course';
$string['btn_reset'] = 'Reset';
$string['filter_all'] = '– All –';
$string['hint_setfilter'] = 'Set a filter to search for courses.';
$string['label_fach'] = 'Subject';
$string['label_kursbereich'] = 'Course area';
$string['label_kursname'] = 'Search term';
$string['label_niveaustufe'] = 'Level';
$string['label_schulform'] = 'School type';
$string['placeholder_kursname'] = 'Search course name or description …';
$string['pluginname'] = 'Course Filter';
$string['ratelimitexceeded'] = 'Too many requests. Please wait {$a->seconds} seconds.';
$string['settings_faecher'] = 'Subjects';
$string['settings_faecher_heading'] = 'Subjects';
$string['settings_faecher_help'] = 'One per line. Course must carry tag "fach:Mathematik" etc.';
$string['settings_niveaustufen'] = 'Levels';
$string['settings_niveaustufen_desc'] = 'One per line. Course must carry tag "niveaustufe:Klasse 10" etc.';
$string['settings_niveaustufen_heading'] = 'Levels';
$string['settings_niveaustufen_help'] = 'One per line. Freely extensible.';
$string['settings_resultlimit'] = 'Max. results';
$string['settings_resultlimit_help'] = 'Maximum number of courses per search (default: 100).';
$string['settings_schulformen'] = 'School types';
$string['settings_schulformen_desc'] = 'Values shown as chips. Courses must be tagged "schulform:Value".';
$string['settings_schulformen_heading'] = 'School types';
$string['settings_schulformen_help'] = 'One per line. Course must carry tag "schulform:Gymnasium" etc.';
