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
 * Manages anonymous course ratings via cookie hash.
 * No user account required: visitors without an account can rate as well.
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
     * Rater cookie of the current visitor, if the browser sent a valid one.
     *
     * @return string|null SHA-256 hash identifying this browser, or null.
     */
    public static function get_cookie_hash(): ?string {
        // Moodle offers no *_param() for cookies; only 64 hex characters pass.
        // phpcs:ignore moodle.Commenting.InlineComment.NotCapital,moodle.Commenting.InlineComment.InvalidEndChar -- Semgrep marker syntax.
        $raw = $_COOKIE[self::COOKIE_NAME] ?? ''; // nosemgrep: moodle-superglobal-direkt
        return is_string($raw) && preg_match('/^[0-9a-f]{64}$/', $raw) ? $raw : null;
    }

    /**
     * Give the current visitor a new rater cookie.
     *
     * It is a session cookie: it ends with the browser session, so one rating per course is only
     * enforced within a session (see TODO https://github.com/Moodle-in-Niedersachsene-V/block_kursfilter/issues/2).
     *
     * @return string SHA-256 hash identifying this browser.
     */
    public static function create_cookie_hash(): string {
        $hash = hash('sha256', bin2hex(random_bytes(32)));
        setcookie(self::COOKIE_NAME, $hash, [
            'expires'  => 0,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return $hash;
    }

    /**
     * Save a rating for a course.
     * If the visitor already rated this course, the rating is not changed
     * (one rating per cookie per course; the unique index decides, so double clicks are safe).
     *
     * @param int    $courseid   Course ID.
     * @param string $cookiehash SHA-256 hash from the rater cookie.
     * @param int    $stars      Rating 1–5.
     * @return bool True if saved, false if already rated.
     * @throws \invalid_parameter_exception If the stars are not 1 to 5.
     */
    public static function save_rating(int $courseid, string $cookiehash, int $stars): bool {
        global $DB;

        if ($stars < 1 || $stars > 5) {
            throw new \invalid_parameter_exception('Stars must be between 1 and 5.');
        }

        $record = new \stdClass();
        $record->courseid = $courseid;
        $record->cookiehash = $cookiehash;
        $record->stars = $stars;
        $record->timecreated = time();

        try {
            $record->id = $DB->insert_record(self::TABLE, $record);
        } catch (\dml_write_exception $e) {
            if (self::get_existing_rating($courseid, $cookiehash) !== null) {
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
     * Decide and store a rating request: valid stars, public course, then save.
     *
     * The cookie is only requested once the request is valid, so rejected requests set no cookie.
     *
     * @param int      $courseid   Course ID.
     * @param int      $stars      Rating 1–5.
     * @param callable $cookiehash Returns the rater cookie hash of the visitor (creating it if needed).
     * @return array JSON-ready result: success plus saved/already_rated and the stored stars, or success false plus error.
     */
    public static function submit(int $courseid, int $stars, callable $cookiehash): array {
        if ($stars < 1 || $stars > 5) {
            return ['success' => false, 'error' => 'Invalid rating'];
        }
        if (!course_access::get_public_course($courseid)) {
            return ['success' => false, 'error' => 'Course not found'];
        }

        $hash = $cookiehash();
        $saved = self::save_rating($courseid, $hash, $stars);
        return [
            'success' => true,
            'saved' => $saved,
            'already_rated' => !$saved,
            'stars' => $saved ? $stars : (self::get_existing_rating($courseid, $hash) ?? $stars),
        ];
    }

    /**
     * Check if a visitor has already rated a course.
     *
     * @param int    $courseid   Course ID.
     * @param string $cookiehash Rater cookie hash.
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
