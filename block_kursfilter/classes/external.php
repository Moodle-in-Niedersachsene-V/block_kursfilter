<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class block_kursfilter_external extends external_api {

    // ---------------------------------------------------------------
    // Konstanten
    // ---------------------------------------------------------------

    /** Absolutes serverseitiges Maximum für Suchergebnisse (F-02). */
    const MAX_RESULT_LIMIT = 200;

    /** Rate-Limit: maximale Anfragen pro Zeitfenster je Nutzer (F-01). */
    const RATE_LIMIT_REQUESTS = 30;

    /** Rate-Limit: Zeitfenster in Sekunden (F-01). */
    const RATE_LIMIT_WINDOW = 60;

    // ---------------------------------------------------------------
    // search_courses
    // ---------------------------------------------------------------
    public static function search_courses_parameters(): external_function_parameters {
        return new external_function_parameters([
            'kursbereich'  => new external_value(PARAM_INT,  'Kategorie-ID (0 = alle)', VALUE_DEFAULT, 0),
            'schulform'    => new external_value(PARAM_TEXT, 'Schulform-Tag',           VALUE_DEFAULT, ''),
            'fach'         => new external_value(PARAM_TEXT, 'Fach-Tag',                VALUE_DEFAULT, ''),
            'niveaustufe'  => new external_value(PARAM_TEXT, 'Niveaustufe-Tag',         VALUE_DEFAULT, ''),
            'tag'          => new external_value(PARAM_TEXT, 'Freier Tag',              VALUE_DEFAULT, ''),
            'kursname'     => new external_value(PARAM_TEXT, 'Kursname (Freitext)',      VALUE_DEFAULT, ''),
            'contextid'    => new external_value(PARAM_INT,  'Aktueller Kontext',       VALUE_DEFAULT, 1),
            'limit'        => new external_value(PARAM_INT,  'Max. Ergebnisse',         VALUE_DEFAULT, 100),
        ]);
    }

    public static function search_courses(
        int    $kursbereich = 0,
        string $schulform   = '',
        string $fach        = '',
        string $niveaustufe = '',
        string $tag         = '',
        string $kursname    = '',
        int    $contextid   = 1,
        int    $limit       = 100
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

        // ── F-01: Rate-Limiting ────────────────────────────────────
        self::check_rate_limit((int)$USER->id);

        // ── F-02: Serverseitiges Ergebnislimit erzwingen ──────────
        // Admin-Konfiguration hat Vorrang; clientseitiger Wert wird
        // nach unten auf das konfigurierte Maximum begrenzt.
        $configLimit = (int)get_config('block_kursfilter', 'resultlimit');
        if ($configLimit < 1 || $configLimit > self::MAX_RESULT_LIMIT) {
            $configLimit = 100; // Fallback auf sicheren Standardwert.
        }
        $effectiveLimit = min((int)$params['limit'], $configLimit, self::MAX_RESULT_LIMIT);
        if ($effectiveLimit < 1) {
            $effectiveLimit = $configLimit;
        }

        // Basis-Bedingungen.
        $conditions = ['c.visible = 1', 'c.id != :siteid'];
        $args       = ['siteid' => SITEID];

        // Kursbereich (inkl. Unterkategorien).
        if (!empty($params['kursbereich'])) {
            $catids = self::get_category_ids_recursive((int)$params['kursbereich']);
            if ($catids) {
                list($catsql, $catargs) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');
                $conditions[] = "c.category $catsql";
                $args = array_merge($args, $catargs);
            }
        }

        // Kursname-Freitext.
        if (!empty($params['kursname'])) {
            $conditions[] = '(' .
                $DB->sql_like('c.fullname',  ':kn1', false) . ' OR ' .
                $DB->sql_like('c.shortname', ':kn2', false) .
            ')';
            $term = '%' . $DB->sql_like_escape($params['kursname']) . '%';
            $args['kn1'] = $term;
            $args['kn2'] = $term;
        }

        // Tag-Filter: Schulform, Fach, Niveaustufe, freier Tag.
        $tagFilters = [];
        foreach (['schulform', 'fach', 'niveaustufe', 'tag'] as $key) {
            if (!empty($params[$key])) {
                $tagFilters[] = $params[$key];
            }
        }
        foreach ($tagFilters as $idx => $tagname) {
            $p = 'tag' . $idx;
            $conditions[] = "EXISTS (
                SELECT 1 FROM {tag_instance} ti{$idx}
                JOIN {tag} t{$idx} ON t{$idx}.id = ti{$idx}.tagid
                WHERE ti{$idx}.itemtype = 'course'
                  AND ti{$idx}.itemid = c.id
                  AND t{$idx}.rawname = :{$p}
            )";
            $args[$p] = $tagname;
        }

        $where = implode(' AND ', $conditions);
        $sql   = "SELECT c.id, c.fullname, c.shortname, c.summary, c.category
                    FROM {course} c
                   WHERE $where
                ORDER BY c.fullname ASC";

        // Effektives Limit aus F-02-Fix verwenden.
        $records = $DB->get_records_sql($sql, $args, 0, $effectiveLimit);

        $courses = [];
        foreach ($records as $course) {
            // Kategorie-Name.
            $cat     = core_course_category::get($course->category, IGNORE_MISSING);
            $catname = $cat ? $cat->get_nested_name(false) : '';

            // Zusammenfassung kürzen.
            $summary = html_to_text(format_text($course->summary, FORMAT_HTML, ['filter' => false]), 0, false);
            if (core_text::strlen($summary) > 250) {
                $summary = core_text::substr($summary, 0, 250) . '…';
            }

            // Tags.
            $tags = core_tag_tag::get_item_tags_array('core', 'course', $course->id);

            // Kurs-URL.
            $courseurl = (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);

            // Export-URL (nur wenn Capability vorhanden).
            $exporturl = null;
            $coursectx = context_course::instance($course->id);
            if (has_capability('moodle/backup:backupcourse', $coursectx)) {
                $exporturl = (new moodle_url('/backup/backup.php', ['id' => $course->id]))->out(false);
            }

            $courses[] = [
                'id'           => (int)$course->id,
                'fullname'     => format_string($course->fullname),
                'shortname'    => format_string($course->shortname),
                'summary'      => $summary,
                'categoryname' => $catname,
                'tags'         => array_values($tags),
                'courseurl'    => $courseurl,
                'exporturl'    => $exporturl ?? '',
                'hasexport'    => ($exporturl !== null),
            ];
        }

        return ['courses' => $courses, 'total' => count($courses)];
    }

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

    // ---------------------------------------------------------------
    // F-01: Rate-Limiting via Moodle MUC (Cache API)
    // ---------------------------------------------------------------

    /**
     * Prüft ob der Nutzer das Rate-Limit überschritten hat.
     * Wirft eine moodle_exception wenn das Limit erreicht ist.
     *
     * Verwendet Moodles MUC (session-Store), um Anfragen je Nutzer
     * innerhalb eines Zeitfensters zu zählen – ohne externe Abhängigkeiten.
     *
     * @param int $userid ID des aktuellen Nutzers.
     * @throws moodle_exception Bei Überschreitung des Rate-Limits.
     */
    private static function check_rate_limit(int $userid): void {
        $cache    = cache::make('block_kursfilter', 'ratelimit');
        $cachekey = 'rl_' . $userid;
        $now      = time();

        $data = $cache->get($cachekey);

        if ($data === false) {
            // Erster Aufruf in diesem Fenster.
            $cache->set($cachekey, ['count' => 1, 'window_start' => $now]);
            return;
        }

        // Neues Zeitfenster starten wenn das alte abgelaufen ist.
        if (($now - $data['window_start']) >= self::RATE_LIMIT_WINDOW) {
            $cache->set($cachekey, ['count' => 1, 'window_start' => $now]);
            return;
        }

        // Zähler erhöhen und prüfen.
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

    // ---------------------------------------------------------------
    // Hilfsmethoden
    // ---------------------------------------------------------------

    /**
     * Alle Unterkategorie-IDs rekursiv sammeln.
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
