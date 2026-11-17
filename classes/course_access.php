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

namespace block_kursfilter;

/**
 * Visibility rule for the public endpoints (rating, backup download, preview).
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_access {
    /**
     * Look up a course that public visitors may use: it exists, is visible and is not the site course.
     *
     * @param int $courseid Course ID.
     * @return \stdClass|null The course record, or null if the course is not publicly usable.
     */
    public static function get_public_course(int $courseid): ?\stdClass {
        global $DB;

        [$rule, $params] = self::public_course_sql();
        $course = $DB->get_record_select('course', "id = :courseid AND $rule", ['courseid' => $courseid] + $params);
        return $course ?: null;
    }

    /**
     * The rule "public course" (visible, not the site course, within the configured root category) as SQL fragment.
     *
     * This is the single source of truth for all queries that need to restrict to public courses.
     * When the admin setting block_kursfilter/rootcategory is set, only courses within that
     * category tree (the category itself and all its subcategories) are included.
     *
     * @param string $alias Table alias of the course table, empty if none.
     * @return array [SQL condition, named parameters].
     */
    public static function public_course_sql(string $alias = ''): array {
        $prefix = $alias === '' ? '' : $alias . '.';
        $sql = "{$prefix}visible = 1 AND {$prefix}id <> :kfsiteid";
        $params = ['kfsiteid' => SITEID];

        $catids = self::root_category_ids();
        if ($catids !== null) {
            if (empty($catids)) {
                // Root category configured but not found: return no courses.
                $sql .= ' AND 1 = 0';
            } else {
                global $DB;
                [$insql, $inparams] = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'kfcat');
                $sql .= " AND {$prefix}category $insql";
                $params = array_merge($params, $inparams);
            }
        }

        return [$sql, $params];
    }

    /**
     * Return all category IDs that belong to the configured root category subtree, or null if unrestricted.
     *
     * Returns null  → no restriction configured (all categories allowed).
     * Returns []    → root category is configured but could not be found (no courses allowed).
     * Returns [ids] → the root category ID and all its descendant IDs.
     *
     * @return int[]|null
     */
    public static function root_category_ids(): ?array {
        $setting = trim((string)get_config('block_kursfilter', 'rootcategory'));
        if ($setting === '') {
            return null;
        }

        global $DB;

        // Accept either a numeric ID or a category name.
        if (ctype_digit($setting)) {
            $rootcat = $DB->get_record('course_categories', ['id' => (int)$setting], 'id, path');
        } else {
            $rootcat = $DB->get_record('course_categories', ['name' => $setting], 'id, path');
        }

        if (!$rootcat) {
            return [];
        }

        // Find the root category and all its descendants using the Moodle path column.
        // The path looks like /1/42/7 — every category whose path starts with this prefix is a descendant.
        $rootid = (int)$rootcat->id;
        $pathprefix = $DB->sql_like_escape($rootcat->path . '/');

        $descendants = $DB->get_fieldset_select(
            'course_categories',
            'id',
            $DB->sql_like('path', ':pathprefix'),
            ['pathprefix' => $pathprefix . '%']
        );

        return array_merge([$rootid], array_map('intval', $descendants));
    }
}
