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

$string['back_to_search']              = 'Back to search';
$string['backup_not_available']        = 'No backup available for this course yet. Please try again later.';
$string['btn_download_backup']         = 'Download course backup (.mbz)';
$string['btn_preview']                 = 'Preview';
$string['btn_reset']                   = 'Reset';
$string['course_not_found']            = 'This course was not found or is not publicly accessible.';
$string['error_ai_answer_invalid']     = 'The AI answer is not the expected JSON: {$a}';
$string['error_ai_request_failed']     = 'The AI request failed: {$a}';
$string['error_ai_tagging_failed']     = 'AI tagging failed for {$a->failed} of {$a->total} courses; see the task log for the causes.';
$string['error_ai_unavailable']        = 'AI tagging is enabled, but no AI provider offers the action "Generate text". Configure one under Site administration > General > AI.';
$string['error_backup_not_created']    = 'The backup of course {$a} produced no file.';
$string['error_backups_failed']        = '{$a->failed} of {$a->total} course backups failed; see the task log for the causes.';
$string['error_preview_needs_post']    = 'A preview can only be started with the preview button of the Course Filter.';
$string['error_rating_failed']         = 'The rating could not be saved.';
$string['error_search_failed']         = 'The search failed.';
$string['event_course_rated']          = 'Course rated';
$string['filter_all']                  = '– All –';
$string['hint_setfilter']              = 'Set a filter to search for courses.';
$string['kursfilter:addinstance']      = 'Add a Course Filter block';
$string['kursfilter:myaddinstance']    = 'Add a Course Filter block to My page';
$string['kursfilter:search']           = 'Search courses with the Course Filter';
$string['label_category']              = 'Course category';
$string['label_level']                 = 'Level';
$string['label_schooltype']            = 'School type';
$string['label_searchterm']            = 'Search term';
$string['label_subject']               = 'Subject';
$string['placeholder_searchterm']      = 'Search course name or description …';
$string['pluginname']                  = 'Course Filter';
$string['pool_account_description']    = 'Pool account of the Course Filter block for course previews.';
$string['pool_account_firstname']      = 'Pool account';
$string['pool_full']                   = 'All pool accounts are currently occupied. Please try again in a few minutes.';
$string['preview_title']               = 'View the course like a teacher without editing rights';
$string['privacy:metadata']            = 'The Course Filter stores ratings only with a random browser cookie that is not linked to any user account; it stores no personal data of users. For AI tagging it sends course content, no personal data, through Moodle\'s AI subsystem, which records these requests itself.';
$string['ratelimitexceeded']           = 'Too many requests. Please wait {$a->seconds} seconds.';
$string['rating_done']                 = 'Rated';
$string['rating_star']                 = 'Rate with {$a} of 5 stars';
$string['results_count']               = 'Courses found: {$a}';
$string['results_none']                = 'No courses found.';
$string['role_pool_description']       = 'Role of the pool accounts: like a non-editing teacher, but without access to participants, user identity and grades.';
$string['role_pool_name']              = 'Course Filter pool role';
$string['settings_ai_autoapply']       = 'Apply tags automatically';
$string['settings_ai_autoapply_help']  = 'If enabled, suitable values become course tags at once. If disabled, they are kept as tag suggestions for review.';
$string['settings_ai_batch_size']      = 'Courses per run';
$string['settings_ai_batch_size_help'] = 'Number of courses tagged per run of the scheduled task (default: 20).';
$string['settings_ai_desc']            = 'The scheduled task asks the AI provider configured in Moodle (action "Generate text") once per public course which filter values fit. Only course name, short name, category and the beginning of the course summary are sent. Only values from the filter value lists are accepted.';
$string['settings_ai_enabled']         = 'Enable AI tagging';
$string['settings_ai_enabled_help']    = 'Lets the scheduled task "AI tagging of courses" run; the task must also be enabled in the scheduled tasks.';
$string['settings_ai_heading']         = 'AI tagging';
$string['settings_backup_desc']        = 'The scheduled task creates one backup (.mbz) without user data per public course nightly at 02:00. Visitors can download it from the search results. Only the latest backup per course is kept.';
$string['settings_backup_heading']     = 'Course backups';
$string['settings_backup_userid']      = 'Backup user ID';
$string['settings_backup_userid_help'] = 'ID of the user the nightly course backups run as; the user needs permission to back up courses. Leave empty to use the first site administrator.';
$string['settings_filters_desc']       = 'The block shows these values as chips. A course matches a value if it carries a course tag with exactly this value; the case does not matter.';
$string['settings_filters_heading']    = 'Filter values';
$string['settings_levels']             = 'Levels';
$string['settings_levels_help']        = 'One level per line, e.g. Klasse 5-6.';
$string['settings_pool_desc']          = 'Visitors preview a public course through a pool account. Missing pool accounts are created by the scheduled task (daily at 03:00).';
$string['settings_pool_heading']       = 'Pool accounts';
$string['settings_poolsize']           = 'Number of pool accounts';
$string['settings_poolsize_help']      = 'Number of pool accounts, i.e. of simultaneous previews (default: 10, max. 50).';
$string['settings_resultlimit']        = 'Max. results';
$string['settings_resultlimit_help']   = 'Maximum number of courses per search (default: 100, max. 200).';
$string['settings_schooltypes']        = 'School types';
$string['settings_schooltypes_help']   = 'One school type per line, e.g. Gymnasium.';
$string['settings_subjects']           = 'Subjects';
$string['settings_subjects_help']      = 'One subject per line, e.g. Mathematik.';
$string['task_backup_courses']         = 'Create course backups for the Course Filter';
$string['task_setup_pool']             = 'Create Course Filter pool accounts and enrol them into public courses';
$string['task_tag_courses']            = 'AI tagging of courses for the Course Filter';
