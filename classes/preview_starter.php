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
 * Decides what a preview request leads to; the endpoint preview.php only renders the outcome.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class preview_starter {
    /** @var string Send the visitor to the course; no pool account was taken. */
    public const REDIRECT = 'redirect';
    /** @var string The course is not a public course. */
    public const COURSE_NOT_FOUND = 'course_not_found';
    /** @var string All pool accounts are occupied. */
    public const POOL_FULL = 'pool_full';
    /** @var string This IP address already has a running preview. */
    public const IP_IN_USE = 'ip_in_use';
    /** @var string A pool account was occupied and is now logged in; send the visitor to the course. */
    public const LOGGED_IN = 'logged_in';

    /**
     * Decide the outcome of a preview and, if needed, log the visitor in as pool account.
     *
     * @param int $courseid Requested course.
     * @param bool $hasreallogin True if the visitor is logged in with a real account; it is never replaced.
     * @return string One of the outcome constants.
     * @throws \moodle_exception If the pool lock times out.
     */
    public static function start(int $courseid, bool $hasreallogin): string {
        if (!course_access::get_public_course($courseid)) {
            return self::COURSE_NOT_FOUND;
        }
        if ($hasreallogin) {
            return self::REDIRECT;
        }
        try {
            return pool_manager::occupy_free_account($courseid) ? self::LOGGED_IN : self::POOL_FULL;
        } catch (\moodle_exception $e) {
            if ($e->errorcode !== 'preview_in_use') {
                throw $e;
            }
            return self::IP_IN_USE;
        }
    }
}
