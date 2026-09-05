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
 * External API (AJAX) for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External functions for the kursfilter block.
 */
class block_kursfilter_external extends external_api {
    /** Absolutes serverseitiges Maximum fuer Suchergebnisse (F-02). */
    const MAX_RESULT_LIMIT = 200;

    /** Rate-Limit: maximale Anfragen pro Zeitfenster je Nutzer (F-01). */
    const RATE_LIMIT_REQUESTS = 30;

    /** Rate-Limit: Zeitfenster in Sekunden (F-01). */
    const RATE_LIMIT_WINDOW = 60;

    // Search_courses.

    /**
     * Parameter definition for search_courses.
     *
     * @return external_function_parameters
     */
    public static function search_courses_parameters(): external_function_parameters {
        return new external_function_parameters([
            'kursbereich'  => new external_value(PARAM_INT, 'Kategorie-ID (0 = alle)', VALUE_DEFAULT, 0),
            'schulform'    => new external_value(PARAM_TEXT, 'Schulform-Tag (Rohwert)', VALUE_DEFAULT, ''),
            'fach'         => new external_value(PARAM_TEXT, 'Fach-Tag (Rohwert)', VALUE_DEFAULT, ''),
            'niveaustufe'  => new external_value(PARAM_TEXT, 'Niveaustufe-Tag (Rohwert)', VALUE_DEFAULT, ''),
            'tag'          => new external_value(PARAM_TEXT, 'Freier Tag (Rohwert)', VALUE_DEFAULT, ''),
            'kursname'     => new external_value(PARAM_TEXT, 'Suchbegriff (Freitext)', VALUE_DEFAULT, ''),
            'contextid'    => new external_value(PARAM_INT, 'Aktueller Kontext', VALUE_DEFAULT, 1),
            'limit'        => new external_value(PARAM_INT, 'Max. Ergebnisse', VALUE_DEFAULT, 100),
        ]);
    }

    /**
     * Search courses by filter criteria.
     *
     * @param int    $kursbereich Category ID.
     * @param string $schulform   Schulform tag rawname.
     * @param string $fach        Fach tag rawname.
     * @param string $niveaustufe Niveaustufe tag rawname.
     * @param string $tag         Free tag rawname.
     * @param string $kursname    Free text search term.
     * @param int    $contextid   Current context ID.
     * @param int    $limit       Max results (server-capped).
     * @return array
     */
    public static function search_courses(
        int $kursbereich = 0,
        string $schulform = '',
        string $fach = '',
        string $niveaustufe = '',
        string $tag = '',
        string $kursname = '',
        int $contextid = 1,
        int $limit = 100
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::search_courses_parameters(), [
            'kursbereich'  => $kursbereich,
            'schulform'    => $schulform,
            'fach'         => $fach,
            'niveaustufe'  => $niveaustufe,
            'tag'          => $tag,
            'kursname'     => $kursname,
            'contextid'    => $contextid,
            'limit'        => $limit,
        ]);

        $context = context::instance_by_id($params['contextid']);
        self::validate_context($context);

        // F-01: Rate-Limiting.
        self::check_rate_limit((int)$USER->id);

        // F-02: Serverseitiges Limit erzwingen.
        $configlimit = (int)get_config('block_kursfilter', 'resultlimit');
        if ($configlimit < 1 || $configlimit > self::MAX_RESULT_LIMIT) {
            $configlimit = 100;
        }
        $effectivelimit = min((int)$params['limit'], $configlimit, self::MAX_RESULT_LIMIT);
        if ($effectivelimit < 1) {
            $effectivelimit = $configlimit;
        }

        // Basis-Bedingungen.
        $conditions = ['c.visible = 1', 'c.id != :siteid'];
        $args       = ['siteid' => SITEID];

        // Kursbereich (inkl. Unterkategorien).
        if (!empty($params['kursbereich'])) {
            $catids = self::get_category_ids_recursive((int)$params['kursbereich']);
            if ($catids) {
                [$catsql, $catargs] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');
                $conditions[] = "c.category $catsql";
                $args = array_merge($args, $catargs);
            }
        }

        // Freitextsuche: Beschreibung (primaer), Kursname, Kurzname.
        if (!empty($params['kursname'])) {
            $conditions[] = '(' .
                $DB->sql_like('c.summary', ':kn1', false) . ' OR ' .
                $DB->sql_like('c.fullname', ':kn2', false) . ' OR ' .
                $DB->sql_like('c.shortname', ':kn3', false) .
            ')';
            $term = '%' . $DB->sql_like_escape($params['kursname']) . '%';
            $args['kn1'] = $term;
            $args['kn2'] = $term;
            $args['kn3'] = $term;
        }

        // Tag-Filter: Rohwerte direkt suchen – kein Prefix-Format.
        // Moodle speichert Tags als Rohwert (z. B. "Oberstufe")..
        $tagfilters = [];
        foreach (['schulform', 'fach', 'niveaustufe', 'tag'] as $key) {
            if (!empty($params[$key])) {
                $tagfilters[] = $params[$key];
            }
        }
        foreach ($tagfilters as $idx => $tagname) {
            $p = 'tag' . $idx;
            $conditions[] = "EXISTS (
                SELECT 1 FROM {tag_instance} ti{$idx}
                JOIN {tag} t{$idx} ON t{$idx}.id = ti{$idx}.tagid
                WHERE ti{$idx}.itemtype = 'course'
                  AND ti{$idx}.itemid = c.id
                  AND " . $DB->sql_like("t{$idx}.rawname", ":{$p}", false) . "
            )";
            $args[$p] = $DB->sql_like_escape($tagname);
        }

        $where = implode(' AND ', $conditions);
        $sql   = "SELECT c.id, c.fullname, c.shortname, c.summary, c.category
                    FROM {course} c
                   WHERE $where
                ORDER BY c.fullname ASC";

        $records = $DB->get_records_sql($sql, $args, 0, $effectivelimit);

        $courses = [];
        foreach ($records as $course) {
            $cat     = core_course_category::get($course->category, IGNORE_MISSING);
            $catname = $cat ? $cat->get_nested_name(false) : '';

            $summary = html_to_text(
                format_text($course->summary, FORMAT_HTML, ['filter' => false]),
                0,
                false
            );
            if (core_text::strlen($summary) > 250) {
                $summary = core_text::substr($summary, 0, 250) . '…';
            }

            $tags      = core_tag_tag::get_item_tags_array('core', 'course', $course->id);
            $courseurl = (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);

            // Export-URL: oeffentlicher Download fuer alle Nutzer inkl. Gaeste.
            // Die mbz-Datei wird nachts durch den Scheduled Task erzeugt.
            // Kein Capability-Check – Datei ist fuer alle sichtbaren Kurse verfuegbar..
            $exporturl = '';
            if (\block_kursfilter\backup_helper::has_backup($course->id)) {
                $exporturl = (new moodle_url(
                    '/blocks/kursfilter/backup.php',
                    ['courseid' => $course->id]
                ))->out(false);
            }

            $courses[] = [
                'id'           => (int)$course->id,
                'fullname'     => format_string($course->fullname),
                'shortname'    => format_string($course->shortname),
                'summary'      => $summary,
                'categoryname' => $catname,
                'tags'         => array_values($tags),
                'courseurl'    => $courseurl,
                'exporturl'    => $exporturl,
                'hasexport'    => ($exporturl !== ''),
            ];
        }

        return ['courses' => $courses, 'total' => count($courses)];
    }

    /**
     * Return definition for search_courses.
     *
     * @return external_single_structure
     */
    public static function search_courses_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id'           => new external_value(PARAM_INT),
                    'fullname'     => new external_value(PARAM_TEXT),
                    'shortname'    => new external_value(PARAM_TEXT),
                    'summary'      => new external_value(PARAM_RAW),
                    'categoryname' => new external_value(PARAM_TEXT),
                    'tags'         => new external_multiple_structure(
                        new external_value(PARAM_TEXT)
                    ),
                    'courseurl'    => new external_value(PARAM_URL),
                    'exporturl'    => new external_value(PARAM_URL),
                    'hasexport'    => new external_value(PARAM_BOOL),
                ])
            ),
            'total' => new external_value(PARAM_INT),
        ]);
    }

    // F-01: Rate-limiting via Moodle MUC.

    /**
     * Check rate limit for the given user.
     *
     * @param int $userid
     * @throws moodle_exception
     */
    private static function check_rate_limit(int $userid): void {
        $cache    = cache::make('block_kursfilter', 'ratelimit');
        $cachekey = 'rl_' . $userid;
        $now      = time();
        $data     = $cache->get($cachekey);

        if ($data === false) {
            $cache->set($cachekey, ['count' => 1, 'window_start' => $now]);
            return;
        }

        if (($now - $data['window_start']) >= self::RATE_LIMIT_WINDOW) {
            $cache->set($cachekey, ['count' => 1, 'window_start' => $now]);
            return;
        }

        $data['count']++;
        $cache->set($cachekey, $data);

        if ($data['count'] > self::RATE_LIMIT_REQUESTS) {
            $remaining = self::RATE_LIMIT_WINDOW - ($now - $data['window_start']);
            throw new moodle_exception(
                'ratelimitexceeded',
                'block_kursfilter',
                '',
                (object)['seconds' => $remaining]
            );
        }
    }

    // Hilfsmethoden.

    /**
     * Recursively collect all child category IDs.
     *
     * @param int $catid Root category ID.
     * @return int[]
     */
    private static function get_category_ids_recursive(int $catid): array {
        $ids = [$catid];
        $cat = core_course_category::get($catid, IGNORE_MISSING);
        if ($cat) {
            foreach ($cat->get_children() as $child) {
                $ids = array_merge($ids, self::get_category_ids_recursive($child->id));
            }
        }
        return $ids;
    }
}
