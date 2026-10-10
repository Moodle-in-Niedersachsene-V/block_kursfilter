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
$string['btn_download_backup']            = 'Download course backup (.mbz)';
$string['btn_preview']                    = 'Preview';
$string['btn_reset']                      = 'Reset';
$string['course_not_found']               = 'This course was not found or is not publicly accessible.';
$string['error_backup_not_created']       = 'The backup of course {$a} produced no file.';
$string['error_backups_failed']           = '{$a->failed} of {$a->total} course backups failed; see the task log for the causes.';
$string['error_preview_needs_post']       = 'A preview can only be started with the preview button of the Course Filter.';
$string['error_rating_failed']            = 'The rating could not be saved.';
$string['error_search_failed']            = 'The search failed.';
$string['event_course_rated']             = 'Course rated';
$string['filter_all']                     = '– All –';
$string['hint_setfilter']                 = 'Set a filter to search for courses.';
$string['kursfilter:addinstance']         = 'Add a Course Filter block';
$string['kursfilter:myaddinstance']       = 'Add a Course Filter block to My page';
$string['kursfilter:search']              = 'Search courses with the Course Filter';
$string['label_category']                 = 'Course category';
$string['label_level']                    = 'Level';
$string['label_schooltype']               = 'School type';
$string['label_searchterm']               = 'Search term';
$string['label_subject']                  = 'Subject';
$string['placeholder_searchterm']         = 'Search course name or description …';
$string['pluginname']                     = 'Course Filter';
$string['pool_account_description']       = 'Pool account of the Course Filter block for course previews.';
$string['pool_account_firstname']         = 'Pool account';
$string['pool_full']                      = 'All pool accounts are currently occupied. Please try again in a few minutes.';
$string['preview_title']                  = 'View the course like a teacher without editing rights';
$string['privacy:metadata']               = 'The Course Filter stores ratings only with a random browser cookie that is not linked to any user account; it stores no personal data of users.';
$string['ratelimitexceeded']              = 'Too many requests. Please wait {$a->seconds} seconds.';
$string['rating_done']                    = 'Rated';
$string['rating_star']                    = 'Rate with {$a} of 5 stars';
$string['results_count']                  = 'Courses found: {$a}';
$string['results_none']                   = 'No courses found.';
$string['role_pool_description']          = 'Role of the pool accounts: like a non-editing teacher, but without access to participants, user identity and grades.';
$string['role_pool_name']                 = 'Course Filter pool role';
$string['settings_ai_autoapply']          = 'Apply tags automatically';
$string['settings_ai_autoapply_help']     = 'If enabled, AI suggestions are saved as tags directly. If disabled, suggestions are stored for manual review.';
$string['settings_ai_backend']            = 'AI backend';
$string['settings_ai_backend_claude']     = 'Anthropic Claude API';
$string['settings_ai_backend_help']       = 'Select the AI backend for automatic course tagging.';
$string['settings_ai_backend_ollama']     = 'Ollama (local)';
$string['settings_ai_batch_size']         = 'Batch size';
$string['settings_ai_batch_size_help']    = 'Number of courses processed per cron run (default: 20).';
$string['settings_ai_claude_apikey']      = 'Claude API key';
$string['settings_ai_claude_apikey_help'] = 'API key for the Anthropic Claude API. Stored securely in the database.';
$string['settings_ai_claude_desc']        = 'Settings for the Anthropic Claude API. No user data is transmitted – only course name, short name, category and a truncated description text.';
$string['settings_ai_claude_heading']     = 'Claude API';
$string['settings_ai_claude_model']       = 'Claude model';
$string['settings_ai_claude_model_help']  = 'Model identifier, e.g. claude-haiku-4-5-20251001.';
$string['settings_ai_desc']               = 'The plugin can tag courses automatically via AI. Only course name, short name, category and a truncated description are transmitted. No user data, no teacher names, no enrolment information.';
$string['settings_ai_enabled']            = 'Enable AI tagging';
$string['settings_ai_enabled_help']       = 'Activates the scheduled task for automatic AI-based course tagging.';
$string['settings_ai_heading']            = 'AI course tagging';
$string['settings_ai_ollama_heading']     = 'Ollama (local)';
$string['settings_ai_ollama_model']       = 'Ollama model';
$string['settings_ai_ollama_model_help']  = 'Model identifier, e.g. gemma3:4b.';
$string['settings_ai_ollama_url']         = 'Ollama URL';
$string['settings_ai_ollama_url_help']    = 'Base URL of the local Ollama server (default: http://localhost:11434).';
$string['settings_backup_desc']           = 'The scheduled task creates one backup (.mbz) without user data per public course nightly at 02:00. Visitors can download it from the search results. Only the latest backup per course is kept.';
$string['settings_backup_heading']        = 'Course backups';
$string['settings_backup_userid']         = 'Backup user ID';
$string['settings_backup_userid_help']    = 'ID of the user the nightly course backups run as; the user needs permission to back up courses. Leave empty to use the first site administrator.';
$string['settings_filters_desc']          = 'The block shows these values as chips. A course matches a value if it carries a course tag with exactly this value; the case does not matter.';
$string['settings_filters_heading']       = 'Filter values';
$string['settings_levels']                = 'Levels';
$string['settings_levels_help']           = 'One level per line, e.g. Klasse 5-6.';
$string['settings_pool_desc']             = 'Visitors preview a public course through a pool account. Missing pool accounts are created by the scheduled task (daily at 03:00).';
$string['settings_pool_heading']          = 'Pool accounts';
$string['settings_poolsize']              = 'Number of pool accounts';
$string['settings_poolsize_help']         = 'Number of pool accounts, i.e. of simultaneous previews (default: 10, max. 50).';
$string['settings_resultlimit']           = 'Max. results';
$string['settings_resultlimit_help']      = 'Maximum number of courses per search (default: 100, max. 200).';
$string['settings_schooltypes']           = 'School types';
$string['settings_schooltypes_help']      = 'One school type per line, e.g. Gymnasium.';
$string['settings_subjects']              = 'Subjects';
$string['settings_subjects_help']         = 'One subject per line, e.g. Mathematik.';
$string['task_backup_courses']            = 'Create course backups for the Course Filter';
$string['task_setup_pool']                = 'Create Course Filter pool accounts and enrol them into public courses';
$string['task_tag_courses']               = 'Automatically tag courses via AI';
