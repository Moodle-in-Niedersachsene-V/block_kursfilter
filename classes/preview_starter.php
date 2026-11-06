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
 * Starts course previews and course-independent previews through the shared pool.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class preview_starter {
    /** @var string Keep the real login; no pool account was taken. */
    public const REDIRECT = 'redirect';
    /** @var string The course is not a public course. */
    public const COURSE_NOT_FOUND = 'course_not_found';
    /** @var string All pool accounts are occupied. */
    public const POOL_FULL = 'pool_full';
    /** @var string This IP address already has a running preview. */
    public const IP_IN_USE = 'ip_in_use';
    /** @var string A pool account was occupied and is now logged in. */
    public const LOGGED_IN = 'logged_in';

    /**
     * Decide the outcome of a preview and, if needed, log the visitor in as pool account.
     *
     * Callers must accept only POST with a valid sesskey before calling this method.
     * A null course ID starts a course-independent preview without enrolment (ADR 0002).
     * The occupancy check and the login run under one lock, so two visitors cannot take the same account.
     *
     * @param int|null $courseid Requested public course, or null to skip course access and enrolment.
     * @param bool $hasreallogin True if the visitor is logged in with a real account; it is never replaced.
     * @return string One of the outcome constants.
     * @throws \moodle_exception If the pool lock times out.
     */
    public static function start(?int $courseid, bool $hasreallogin): string {
        global $DB, $CFG;

        if ($courseid !== null && !course_access::get_public_course($courseid)) {
            return self::COURSE_NOT_FOUND;
        }
        if ($hasreallogin) {
            return self::REDIRECT;
        }

        $lock = \core\lock\lock_config::get_lock_factory('block_kursfilter_pool')->get_lock('occupancy', 10);
        if (!$lock) {
            throw new \moodle_exception('locktimeout');
        }
        try {
            // Two fixed queries: all pool accounts up to the maximum, and the sessions that occupy them.
            [$allsql, $allparams] = $DB->get_in_or_equal(
                pool_manager::get_pool_usernames(pool_manager::POOL_SIZE_MAX),
                SQL_PARAMS_NAMED
            );
            $accounts = $DB->get_records_select(
                'user',
                "username $allsql AND deleted = 0",
                $allparams,
                'username',
                'id, username'
            );
            $allids = array_keys($accounts);
            $sessions = [];
            if ($allids) {
                // Only pool sessions count: guest sessions of the same IP must not block the preview.
                [$idsql, $idparams] = $DB->get_in_or_equal($allids, SQL_PARAMS_NAMED);
                $sessions = $DB->get_records_select(
                    'sessions',
                    "userid $idsql AND timemodified >= :cutoff",
                    $idparams + ['cutoff' => time() - $CFG->sessiontimeout],
                    '',
                    'id, userid, firstip, lastip'
                );
            }

            $ip = getremoteaddr();
            $occupied = [];
            foreach ($sessions as $session) {
                if ($session->firstip === $ip || $session->lastip === $ip) {
                    return self::IP_IN_USE;
                }
                $occupied[(int)$session->userid] = true;
            }

            $free = null;
            foreach ($accounts as $account) {
                $isconfigured = in_array($account->username, pool_manager::get_pool_usernames(), true);
                if ($isconfigured && !isset($occupied[(int)$account->id])) {
                    $free = $account;
                    break;
                }
            }
            if (!$free) {
                return self::POOL_FULL;
            }

            if ($courseid !== null) {
                pool_manager::enrol_pool_into_course($courseid);
            }
            \core\session\manager::destroy_user_sessions((int)$free->id);
            // Keep the lock until Moodle has recorded the new session, with no reservation/login gap.
            complete_user_login(get_complete_user_data('id', $free->id));
            return self::LOGGED_IN;
        } finally {
            $lock->release();
        }
    }
}
