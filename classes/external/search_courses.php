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

namespace block_kursfilter\external;

use block_kursfilter\backup_helper;
use block_kursfilter\rating_helper;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Web service: search public courses by category, course tags and search term.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_courses extends external_api {
    /** Absolute server-side maximum for search results. */
    const MAX_RESULT_LIMIT = 200;

    /** Result limit when the setting is missing or out of range (documented in the setting). */
    const DEFAULT_RESULT_LIMIT = 100;

    /** Rate limit: maximum requests per time window and user. */
    const RATE_LIMIT_REQUESTS = 30;

    /** Rate limit: time window in seconds. */
    const RATE_LIMIT_WINDOW = 60;

    /** Maximum length of the course summary in a result. */
    const SUMMARY_LENGTH = 250;

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'category'   => new external_value(PARAM_INT, 'Category ID including subcategories (0 = all)', VALUE_DEFAULT, 0),
            'schooltype' => new external_value(PARAM_TEXT, 'School type course tag', VALUE_DEFAULT, ''),
            'subject'    => new external_value(PARAM_TEXT, 'Subject course tag', VALUE_DEFAULT, ''),
            'level'      => new external_value(PARAM_TEXT, 'Level course tag', VALUE_DEFAULT, ''),
            'searchterm' => new external_value(PARAM_TEXT, 'Search term for summary, full name and short name', VALUE_DEFAULT, ''),
            'limit'      => new external_value(PARAM_INT, 'Maximum number of results (capped by the server)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Search public courses.
     *
     * @param int    $category   Category ID (0 = all).
     * @param string $schooltype School type course tag.
     * @param string $subject    Subject course tag.
     * @param string $level      Level course tag.
     * @param string $searchterm Search term.
     * @param int    $limit      Requested maximum number of results.
     * @return array Matching courses and their count.
     */
    public static function execute(
        int $category = 0,
        string $schooltype = '',
        string $subject = '',
        string $level = '',
        string $searchterm = '',
        int $limit = 0
    ): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'category'   => $category,
            'schooltype' => $schooltype,
            'subject'    => $subject,
            'level'      => $level,
            'searchterm' => $searchterm,
            'limit'      => $limit,
        ]);

        // The search covers courses of the whole site.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('block/kursfilter:search', $context);
        self::check_rate_limit((int)$USER->id);

        $records = self::find_courses(
            $params['category'],
            array_filter([$params['schooltype'], $params['subject'], $params['level']], 'strlen'),
            $params['searchterm'],
            self::effective_limit($params['limit'])
        );

        $cookiehash = rating_helper::get_cookie_hash();
        $courses = [];
        foreach ($records as $course) {
            $courses[] = self::course_result($course, $cookiehash);
        }
        return ['courses' => $courses, 'total' => count($courses)];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'           => new external_value(PARAM_INT, 'Course ID'),
                    'fullname'     => new external_value(PARAM_TEXT, 'Course full name'),
                    'shortname'    => new external_value(PARAM_TEXT, 'Course short name'),
                    'summary'      => new external_value(PARAM_RAW, 'Course summary as plain text, shortened'),
                    'categoryname' => new external_value(PARAM_TEXT, 'Category path'),
                    'tags'         => new external_multiple_structure(new external_value(PARAM_TEXT, 'Course tag')),
                    'courseurl'    => new external_value(PARAM_URL, 'Course URL'),
                    'backupurl'    => new external_value(PARAM_URL, 'Backup download URL, empty without backup'),
                    'hasbackup'    => new external_value(PARAM_BOOL, 'Whether a backup can be downloaded'),
                    'ratingavg'    => new external_value(PARAM_FLOAT, 'Average rating'),
                    'ratingcount'  => new external_value(PARAM_INT, 'Number of ratings'),
                    'userrating'   => new external_value(PARAM_INT, 'Rating of this visitor (0 = none)'),
                    'alreadyrated' => new external_value(PARAM_BOOL, 'Whether this visitor rated the course'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Number of returned courses'),
        ]);
    }

    /**
     * Cap the requested limit by the setting and the hard maximum.
     *
     * @param int $requested Requested limit (< 1 = setting).
     * @return int Limit to apply.
     */
    private static function effective_limit(int $requested): int {
        $configured = (int)get_config('block_kursfilter', 'resultlimit');
        if ($configured < 1 || $configured > self::MAX_RESULT_LIMIT) {
            $configured = self::DEFAULT_RESULT_LIMIT;
        }
        return $requested < 1 ? $configured : min($requested, $configured);
    }

    /**
     * Query public courses matching all given filters.
     *
     * @param int      $categoryid Category ID including subcategories (0 = all).
     * @param string[] $tags       Course tags that must all be present.
     * @param string   $searchterm Search term (empty = none).
     * @param int      $limit      Maximum number of records.
     * @return \stdClass[] Course records.
     */
    private static function find_courses(int $categoryid, array $tags, string $searchterm, int $limit): array {
        global $DB;

        $conditions = ['c.visible = 1', 'c.id != :siteid'];
        $args = ['siteid' => SITEID];

        if ($categoryid) {
            $category = \core_course_category::get($categoryid, IGNORE_MISSING);
            $catids = $category ? array_merge([$categoryid], $category->get_all_children_ids()) : [$categoryid];
            [$catsql, $catargs] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');
            $conditions[] = "c.category $catsql";
            $args += $catargs;
        }

        if ($searchterm !== '') {
            $conditions[] = '(' .
                $DB->sql_like('c.summary', ':st1', false) . ' OR ' .
                $DB->sql_like('c.fullname', ':st2', false) . ' OR ' .
                $DB->sql_like('c.shortname', ':st3', false) .
            ')';
            $term = '%' . $DB->sql_like_escape($searchterm) . '%';
            $args += ['st1' => $term, 'st2' => $term, 'st3' => $term];
        }

        // Course tags hold the plain value (e.g. "Oberstufe"), matched case-insensitively.
        foreach (array_values($tags) as $idx => $tagname) {
            $conditions[] = "EXISTS (
                SELECT 1 FROM {tag_instance} ti{$idx}
                JOIN {tag} t{$idx} ON t{$idx}.id = ti{$idx}.tagid
                WHERE ti{$idx}.itemtype = 'course'
                  AND ti{$idx}.itemid = c.id
                  AND " . $DB->sql_like("t{$idx}.rawname", ":tag{$idx}", false) . "
            )";
            $args["tag{$idx}"] = $DB->sql_like_escape($tagname);
        }

        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.category
                  FROM {course} c
                 WHERE " . implode(' AND ', $conditions) . "
              ORDER BY c.fullname ASC";
        return $DB->get_records_sql($sql, $args, 0, $limit);
    }

    /**
     * Build the result entry of one course.
     *
     * @param \stdClass   $course     Course record.
     * @param string|null $cookiehash Rater cookie of this visitor, null if none.
     * @return array Result entry.
     */
    private static function course_result(\stdClass $course, ?string $cookiehash): array {
        $category = \core_course_category::get($course->category, IGNORE_MISSING);
        $summary = html_to_text(format_text($course->summary, FORMAT_HTML, ['filter' => false]), 0, false);
        if (\core_text::strlen($summary) > self::SUMMARY_LENGTH) {
            $summary = \core_text::substr($summary, 0, self::SUMMARY_LENGTH) . '…';
        }
        // The backup download is public for every public course (see backup.php).
        $backupurl = backup_helper::has_backup($course->id)
            ? (new \moodle_url('/blocks/kursfilter/backup.php', ['courseid' => $course->id]))->out(false)
            : '';
        $rating = rating_helper::get_course_rating($course->id);
        $userrating = $cookiehash === null ? null : rating_helper::get_existing_rating($course->id, $cookiehash);

        return [
            'id'           => (int)$course->id,
            'fullname'     => format_string($course->fullname),
            'shortname'    => format_string($course->shortname),
            'summary'      => $summary,
            'categoryname' => $category ? $category->get_nested_name(false) : '',
            'tags'         => array_values(\core_tag_tag::get_item_tags_array('core', 'course', $course->id)),
            'courseurl'    => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'backupurl'    => $backupurl,
            'hasbackup'    => $backupurl !== '',
            'ratingavg'    => $rating['avg'],
            'ratingcount'  => $rating['count'],
            'userrating'   => $userrating ?? 0,
            'alreadyrated' => $userrating !== null,
        ];
    }

    /**
     * Count the request of a user and refuse it above the rate limit.
     *
     * @param int $userid User ID.
     * @throws \moodle_exception If the user exceeded the rate limit.
     */
    private static function check_rate_limit(int $userid): void {
        $cache = \cache::make('block_kursfilter', 'ratelimit');
        $key = 'rl_' . $userid;
        $now = time();
        $data = $cache->get($key);

        if ($data === false || ($now - $data['window_start']) >= self::RATE_LIMIT_WINDOW) {
            $cache->set($key, ['count' => 1, 'window_start' => $now]);
            return;
        }

        $data['count']++;
        $cache->set($key, $data);
        if ($data['count'] > self::RATE_LIMIT_REQUESTS) {
            throw new \moodle_exception('ratelimitexceeded', 'block_kursfilter', '', (object)[
                'seconds' => self::RATE_LIMIT_WINDOW - ($now - $data['window_start']),
            ]);
        }
    }
}
