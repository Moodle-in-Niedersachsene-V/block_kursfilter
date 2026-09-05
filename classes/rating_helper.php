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
 * Rating helper for block_kursfilter.
 *
 * Verwaltet anonyme Kursbewertungen per Cookie-Hash.
 * Kein Nutzerkonto erforderlich – auch Gaeste koennen bewerten.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter;

/**
 * Handles anonymous course ratings via browser cookie.
 */
class rating_helper {
    /** Cookie name stored in the browser. */
    const COOKIE_NAME = 'kf_rater_id';

    /** Table name for ratings. */
    const TABLE = 'block_kursfilter_ratings';

    /**
     * Get or create the rater cookie hash for the current visitor.
     * Sets the cookie in the response if it does not exist yet.
     * Cookie has no expiry (session=false, expires=0 → permanent).
     *
     * @return string SHA-256 hash identifying this browser.
     */
    public static function get_or_create_cookie_hash(): string {
        if (!empty($_COOKIE[self::COOKIE_NAME])) {
            $raw = $_COOKIE[self::COOKIE_NAME];
            // Validate: must be 64 hex chars.
            if (preg_match('/^[0-9a-f]{64}$/', $raw)) {
                return $raw;
            }
        }

        // Generate a new random identifier and store as cookie.
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);

        // Permanent cookie (expires = 0 means session; use far-future date for permanent).
        setcookie(
            self::COOKIE_NAME,
            $hash,
            [
                'expires'  => 0, // Session cookie – survives until browser data cleared.
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );

        return $hash;
    }

    /**
     * Save a rating for a course.
     * If the visitor already rated this course, the rating is not changed
     * (one rating per cookie per course).
     *
     * @param int    $courseid   Course ID.
     * @param string $cookiehash SHA-256 hash from get_or_create_cookie_hash().
     * @param int    $stars      Rating 1–5.
     * @return bool True if saved, false if already rated.
     */
    public static function save_rating(int $courseid, string $cookiehash, int $stars): bool {
        global $DB;

        // Validate stars range.
        if ($stars < 1 || $stars > 5) {
            return false;
        }

        // Check if already rated.
        if ($DB->record_exists(self::TABLE, ['courseid' => $courseid, 'cookiehash' => $cookiehash])) {
            return false;
        }

        $record              = new \stdClass();
        $record->courseid    = $courseid;
        $record->cookiehash  = $cookiehash;
        $record->stars       = $stars;
        $record->timecreated = time();

        $DB->insert_record(self::TABLE, $record);
        return true;
    }

    /**
     * Check if a visitor has already rated a course.
     *
     * @param int    $courseid   Course ID.
     * @param string $cookiehash Visitor cookie hash.
     * @return int|null The star rating if already rated, null otherwise.
     */
    public static function get_existing_rating(int $courseid, string $cookiehash): ?int {
        global $DB;

        $record = $DB->get_record(
            self::TABLE,
            ['courseid' => $courseid, 'cookiehash' => $cookiehash],
            'stars',
            IGNORE_MISSING
        );
        return $record ? (int)$record->stars : null;
    }

    /**
     * Get average rating and count for a course.
     *
     * @param int $courseid Course ID.
     * @return array{avg: float, count: int}
     */
    public static function get_course_rating(int $courseid): array {
        global $DB;

        $sql = "SELECT AVG(stars) AS avg, COUNT(*) AS cnt
                  FROM {" . self::TABLE . "}
                 WHERE courseid = :courseid";

        $result = $DB->get_record_sql($sql, ['courseid' => $courseid]);
        return [
            'avg'   => $result && $result->cnt > 0 ? round((float)$result->avg, 1) : 0.0,
            'count' => $result ? (int)$result->cnt : 0,
        ];
    }
}
