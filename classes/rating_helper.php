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
 * Course ratings by anonymous visitors (one per visitor ID and course).
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rating_helper {
    /** Table name for ratings. */
    const TABLE = 'block_kursfilter_ratings';

    /**
     * Rate a public course. Checks the stars and the course first, so rejected requests set no cookie.
     *
     * @param int $courseid Course ID.
     * @param int $stars    Rating 1–5.
     * @return array JSON-ready result: success plus saved/already_rated and the stored stars,
     *               or success false plus error.
     */
    public static function rate(int $courseid, int $stars): array {
        if ($stars < 1 || $stars > 5) {
            return ['success' => false, 'error' => 'Invalid rating'];
        }
        if (!course_access::get_public_course($courseid)) {
            return ['success' => false, 'error' => 'Course not found'];
        }

        $visitorid = visitor_id::get_or_create();
        $saved = self::save_rating($courseid, $visitorid, $stars);
        return [
            'success' => true,
            'saved' => $saved,
            'already_rated' => !$saved,
            'stars' => $saved ? $stars : (self::get_existing_rating($courseid, $visitorid) ?? $stars),
        ];
    }

    /**
     * Average rating, count and the visitor's own rating for many courses, in a fixed number of queries.
     *
     * @param int[]       $courseids Course IDs.
     * @param string|null $visitorid Visitor ID, null if the visitor has none.
     * @return array<int, array{avg: float, count: int, own: ?int}> Keyed by course ID.
     */
    public static function get_course_ratings(array $courseids, ?string $visitorid): array {
        global $DB;

        $courseids = array_values(array_unique(array_map('intval', $courseids)));
        if (!$courseids) {
            return [];
        }
        $result = [];
        foreach ($courseids as $courseid) {
            $result[$courseid] = ['avg' => 0.0, 'count' => 0, 'own' => null];
        }

        [$insql, $params] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'kcid');
        $aggregates = $DB->get_records_sql(
            "SELECT courseid, AVG(stars) AS avgstars, COUNT(*) AS cnt
               FROM {" . self::TABLE . "}
              WHERE courseid $insql
           GROUP BY courseid",
            $params
        );
        foreach ($aggregates as $row) {
            $result[(int)$row->courseid]['avg'] = round((float)$row->avgstars, 1);
            $result[(int)$row->courseid]['count'] = (int)$row->cnt;
        }

        if ($visitorid !== null) {
            $own = $DB->get_records_sql(
                "SELECT courseid, stars
                   FROM {" . self::TABLE . "}
                  WHERE cookiehash = :visitorid AND courseid $insql",
                ['visitorid' => $visitorid] + $params
            );
            foreach ($own as $row) {
                $result[(int)$row->courseid]['own'] = (int)$row->stars;
            }
        }
        return $result;
    }

    /**
     * Save a rating for a course.
     * If the visitor already rated this course, the rating is not changed
     * (one rating per visitor ID and course; the unique index decides, so double clicks are safe).
     *
     * @param int    $courseid  Course ID.
     * @param string $visitorid Visitor ID.
     * @param int    $stars     Rating 1–5, checked by the caller.
     * @return bool True if saved, false if already rated.
     */
    private static function save_rating(int $courseid, string $visitorid, int $stars): bool {
        global $DB;

        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->cookiehash = $visitorid;
        $record->stars = $stars;
        $record->timecreated = time();

        try {
            $record->id = $DB->insert_record(self::TABLE, $record);
        } catch (\dml_write_exception $e) {
            if (self::get_existing_rating($courseid, $visitorid) !== null) {
                return false;
            }
            throw $e;
        }

        event\course_rated::create([
            'context'  => \context_course::instance($courseid),
            'objectid' => $record->id,
            'courseid' => $courseid,
            'other'    => ['stars' => $stars],
        ])->trigger();

        return true;
    }

    /**
     * Stars the visitor gave the course, if any.
     *
     * @param int    $courseid  Course ID.
     * @param string $visitorid Visitor ID.
     * @return int|null
     */
    private static function get_existing_rating(int $courseid, string $visitorid): ?int {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['courseid' => $courseid, 'cookiehash' => $visitorid],
            'stars',
            IGNORE_MISSING
        );
        return $record ? (int)$record->stars : null;
    }
}
