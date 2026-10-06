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
 * Visibility rule for the public endpoints (rating, backup download, guest login).
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

        $course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], '*', IGNORE_MISSING);
        if (!$course || $course->id === SITEID) {
            return null;
        }
        return $course;
    }
}
