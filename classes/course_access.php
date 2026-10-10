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
 * Course access helper for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter;

/**
 * Provides safe access to public course records.
 *
 * Centralises the visibility and site-course checks so callers
 * do not have to repeat the guard logic.
 */
class course_access {
    /**
     * Returns the course record for a given ID if it is publicly visible.
     *
     * A course is considered public when:
     * - it exists in the database,
     * - it is visible (visible = 1),
     * - it is not the site course (id != SITEID).
     *
     * @param int $courseid The course ID to look up.
     * @return \stdClass|null The course record, or null if not publicly available.
     */
    public static function get_public_course(int $courseid): ?\stdClass {
        global $DB;
        // Exclude site course before DB query to avoid fetching it at all.
        if ($courseid === (int)SITEID || $courseid <= 0) {
            return null;
        }
        $course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], 'id, fullname, shortname', IGNORE_MISSING);
        if (!$course) {
            return null;
        }
        return $course;
    }
}
