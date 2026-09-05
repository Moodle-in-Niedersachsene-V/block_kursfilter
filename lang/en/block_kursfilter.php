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
 * English language strings for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['back_to_search']                 = 'Back to search';
$string['backup_not_available']           = 'No backup available for this course yet. Please try again later.';
$string['block_kursfilter:addinstance']   = 'Add a Course Filter block';
$string['block_kursfilter:myaddinstance'] = 'Add a Course Filter block to My page';
$string['btn_export']                     = 'Download';
$string['btn_opencourse']                 = 'Open course';
$string['btn_reset']                      = 'Reset';
$string['course_not_found']               = 'This course was not found or is not publicly accessible.';
$string['filter_all']                     = '– All –';
$string['hint_setfilter']                 = 'Set a filter to search for courses.';
$string['label_fach']                     = 'Subject';
$string['label_kursbereich']              = 'Course area';
$string['label_kursname']                 = 'Search term';
$string['label_niveaustufe']              = 'Level';
$string['label_schulform']                = 'School type';
$string['placeholder_kursname']           = 'Search course name or description …';
$string['pluginname']                     = 'Course Filter';
$string['pool_full']                      = 'All preview accounts are currently in use. Please try again in a few minutes.';
$string['ratelimitexceeded']              = 'Too many requests. Please wait {$a->seconds} seconds.';
$string['rating_already_done']            = 'You have already rated this course.';
$string['rating_saved']                   = 'Rating saved.';
$string['settings_backup_adminid']        = 'User ID for course backups';
$string['settings_backup_adminid_help']   = 'User ID of the administrator used for nightly backups. Leave empty to use the first site admin.';
$string['settings_backup_desc']           = 'The scheduled task creates one .mbz backup per course nightly at 02:00. Guests can download these via the download button. Only one file per course is kept.';
$string['settings_backup_heading']        = 'Course backups (guest download)';
$string['settings_faecher']               = 'Subjects';
$string['settings_faecher_heading']       = 'Subjects';
$string['settings_faecher_help']          = 'One per line. Course must carry tag "fach:Mathematik" etc.';
$string['settings_niveaustufen']          = 'Levels';
$string['settings_niveaustufen_desc']     = 'One per line. Course must carry tag "niveaustufe:Klasse 10" etc.';
$string['settings_niveaustufen_heading']  = 'Levels';
$string['settings_niveaustufen_help']     = 'One per line. Freely extensible.';
$string['settings_pool_desc']             = 'The pool provides anonymous course visitor accounts. New users are created automatically on the next scheduled task run (daily at 03:00). Maximum 50 users.';
$string['settings_pool_heading']          = 'Guest pool users';
$string['settings_poolsize']              = 'Number of pool users';
$string['settings_poolsize_help']         = 'Number of guest pool accounts (default: 10, max. 50). Missing users are created automatically on the next cron run after saving.';
$string['settings_resultlimit']           = 'Max. results';
$string['settings_resultlimit_help']      = 'Maximum number of courses per search (default: 100, max. 200).';
$string['settings_schulformen']           = 'School types';
$string['settings_schulformen_desc']      = 'Values shown as chips. Courses must be tagged "schulform:Value".';
$string['settings_schulformen_heading']   = 'School types';
$string['settings_schulformen_help']      = 'One per line. Course must carry tag "schulform:Gymnasium" etc.';
$string['task_backup_courses']            = 'Generate course backups for Course Filter block';
$string['task_setup_pool']                = 'Set up course filter pool users and enrol into new courses';
