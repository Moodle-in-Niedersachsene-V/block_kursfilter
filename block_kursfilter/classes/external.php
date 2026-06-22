<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class block_kursfilter_external extends external_api {

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
        global $DB;

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
        // Tags werden im Format "schulform:Gymnasium", "fach:Mathematik" usw. erwartet.
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

        $records = $DB->get_records_sql($sql, $args, 0, $params['limit']);

        $courses = [];
        foreach ($records as $course) {
            // Kategorie-Name.
            $cat         = core_course_category::get($course->category, IGNORE_MISSING);
            $catname     = $cat ? $cat->get_nested_name(false) : '';

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
    // Hilfsmethoden
    // ---------------------------------------------------------------

    /**
     * Alle Unterkategorie-IDs rekursiv sammeln.
     */
    private static function get_category_ids_recursive(int $catid): array {
        $ids      = [$catid];
        $cat      = core_course_category::get($catid, IGNORE_MISSING);
        if ($cat) {
            foreach ($cat->get_children() as $child) {
                $ids = array_merge($ids, self::get_category_ids_recursive($child->id));
            }
        }
        return $ids;
    }
}
