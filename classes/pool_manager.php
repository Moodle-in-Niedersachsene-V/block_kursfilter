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
 * Pool manager for block_kursfilter guest access.
 *
 * Verwaltet einen Pool aus festen Testnutzern, die Gaesten
 * den Kursbesuch als Trainer ohne Bearbeitungsrecht ermoeglichen.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter;

/**
 * Manages the guest pool user accounts.
 */
class pool_manager {
    /** Default number of pool users (used if no config value is set). */
    const POOL_SIZE_DEFAULT = 10;

    /** Maximum allowed pool size (hard cap). */
    const POOL_SIZE_MAX = 50;

    /** Username prefix for pool users. */
    const USERNAME_PREFIX = 'kursfilter_guest';

    /** Role shortname for course access. */
    const ROLE_SHORTNAME = 'teacher';

    /** Cache key for tracking active sessions per pool user. */
    const CACHE_PREFIX = 'pool_active_';

    /**
     * Return the configured pool size (from admin settings, capped at POOL_SIZE_MAX).
     *
     * @return int
     */
    public static function get_pool_size(): int {
        $configured = (int)get_config('block_kursfilter', 'poolsize');
        if ($configured < 1) {
            $configured = self::POOL_SIZE_DEFAULT;
        }
        return min($configured, self::POOL_SIZE_MAX);
    }

    /**
     * Return all pool usernames based on the current configured pool size.
     *
     * @return string[]
     */
    public static function get_pool_usernames(): array {
        $names = [];
        for ($i = 1; $i <= self::get_pool_size(); $i++) {
            $names[] = self::USERNAME_PREFIX . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        }
        return $names;
    }

    /**
     * Create all pool users if they do not exist yet.
     * Existing users are left unchanged.
     *
     * @return int Number of newly created users.
     */
    public static function create_pool_users(): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $created = 0;
        foreach (self::get_pool_usernames() as $username) {
            if ($DB->record_exists('user', ['username' => $username, 'deleted' => 0])) {
                continue;
            }

            $user                   = new \stdClass();
            $user->auth             = 'manual';
            $user->confirmed        = 1;
            $user->mnethostid       = $CFG->mnet_localhost_id;
            $user->username         = $username;
            $user->password         = hash_internal_user_password(self::generate_password());
            $user->firstname        = 'Kursbesucher';
            $user->lastname         = ltrim(substr($username, strlen(self::USERNAME_PREFIX)));
            $user->email            = $username . '@kursfilter.invalid';
            $user->emailstop        = 1;
            $user->lang             = 'de';
            $user->timecreated      = time();
            $user->timemodified     = time();
            $user->description      = 'Automatisch angelegter Gastnutzer fuer den Kursfilter-Block.';

            user_create_user($user, false, false);
            $created++;
        }
        return $created;
    }

    /**
     * Enrol all pool users into a course with the teacher role (no editing).
     * Skips users already enroled.
     *
     * @param int $courseid Target course ID.
     * @return int Number of newly enroled users.
     */
    public static function enrol_pool_into_course(int $courseid): int {
        global $DB;

        $role = $DB->get_record('role', ['shortname' => self::ROLE_SHORTNAME], '*', IGNORE_MISSING);
        if (!$role) {
            debugging('block_kursfilter pool_manager: role "' . self::ROLE_SHORTNAME . '" not found.', DEBUG_DEVELOPER);
            return 0;
        }

        // Use manual enrolment plugin.
        $enrol  = enrol_get_plugin('manual');
        $instance = $DB->get_record(
            'enrol',
            ['courseid' => $courseid, 'enrol' => 'manual'],
            '*',
            IGNORE_MISSING
        );
        if (!$instance) {
            // Create manual enrol instance if missing.
            $instanceid = $enrol->add_default_instance(get_course($courseid));
            $instance   = $DB->get_record('enrol', ['id' => $instanceid]);
        }

        $context  = \context_course::instance($courseid);
        $enrolled = 0;

        foreach (self::get_pool_usernames() as $username) {
            $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], 'id', IGNORE_MISSING);
            if (!$user) {
                continue;
            }
            // Skip if already enroled.
            if (is_enrolled($context, $user->id, '', true)) {
                continue;
            }
            $enrol->enrol_user($instance, $user->id, $role->id);
            $enrolled++;
        }
        return $enrolled;
    }

    /**
     * Enrol pool users into all visible non-site courses.
     *
     * @return int Total number of new enrolments.
     */
    public static function enrol_pool_into_all_courses(): int {
        global $DB;

        $courses = $DB->get_records_select(
            'course',
            'visible = 1 AND id != :siteid',
            ['siteid' => SITEID],
            '',
            'id'
        );

        $total = 0;
        foreach ($courses as $course) {
            $total += self::enrol_pool_into_course((int)$course->id);
        }
        return $total;
    }

    /**
     * Find a free pool user (one without an active session marker).
     * Returns the first available user object, or null if all are busy.
     *
     * @return \stdClass|null Moodle user record or null.
     */
    public static function get_free_pool_user(): ?\stdClass {
        global $DB;

        $cache = \cache::make('block_kursfilter', 'poolsessions');

        foreach (self::get_pool_usernames() as $username) {
            $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], '*', IGNORE_MISSING);
            if (!$user) {
                continue;
            }
            // Check if this pool user has an active session marker.
            $active = $cache->get(self::CACHE_PREFIX . $username);
            if ($active === false) {
                return $user;
            }
        }
        return null;
    }

    /**
     * Mark a pool user as active (session started).
     *
     * @param string $username Pool username.
     * @param int    $ttl      Seconds until the session marker expires.
     */
    public static function mark_active(string $username, int $ttl = 3600): void {
        $cache = \cache::make('block_kursfilter', 'poolsessions');
        $cache->set(self::CACHE_PREFIX . $username, time());
    }

    /**
     * Release a pool user (session ended or expired).
     *
     * @param string $username Pool username.
     */
    public static function mark_free(string $username): void {
        $cache = \cache::make('block_kursfilter', 'poolsessions');
        $cache->delete(self::CACHE_PREFIX . $username);
    }

    /**
     * Generate a secure random password for pool users.
     *
     * @return string
     */
    private static function generate_password(): string {
        return random_string(20) . 'Aa1!';
    }
}
