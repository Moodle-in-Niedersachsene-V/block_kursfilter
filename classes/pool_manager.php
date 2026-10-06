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
 * Manages a pool of fixed accounts that let guests visit a course
 * as a teacher without editing rights.
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

    /** Role shortname for course access (non-editing teacher without access to personal data). */
    const ROLE_SHORTNAME = 'kursfilter_pool';

    /** Capabilities prohibited for the pool role: they would expose participants of real courses. */
    const PROHIBITED_CAPABILITIES = [
        'moodle/course:viewparticipants',
        'moodle/site:viewuseridentity',
        'moodle/grade:viewall',
    ];

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
            $user->lang             = get_string_manager()->translation_exists('de') ? 'de' : $CFG->lang;
            $user->timecreated      = time();
            $user->timemodified     = time();
            $user->description      = 'Automatisch angelegter Gastnutzer fuer den Kursfilter-Block.';

            user_create_user($user, false, false);
            $created++;
        }
        return $created;
    }

    /**
     * Create the pool role if missing: a non-editing teacher that may not see participants, user identity or grades.
     *
     * @return int Role ID.
     */
    public static function ensure_role(): int {
        global $DB;

        $roleid = $DB->get_field('role', 'id', ['shortname' => self::ROLE_SHORTNAME]);
        if ($roleid) {
            return (int)$roleid;
        }
        $roleid = create_role(
            get_string('role_pool_name', 'block_kursfilter'),
            self::ROLE_SHORTNAME,
            get_string('role_pool_description', 'block_kursfilter'),
            'teacher'
        );
        set_role_contextlevels($roleid, get_default_contextlevels('teacher'));
        // Take over the default capabilities of the archetype (create_role() does not).
        reset_role_capabilities($roleid);
        foreach (self::PROHIBITED_CAPABILITIES as $capability) {
            assign_capability($capability, CAP_PROHIBIT, $roleid, \context_system::instance()->id, true);
        }
        return (int)$roleid;
    }

    /**
     * Delete the pool role together with its assignments.
     */
    public static function remove_role(): void {
        global $DB;

        $roleid = $DB->get_field('role', 'id', ['shortname' => self::ROLE_SHORTNAME]);
        if ($roleid) {
            delete_role($roleid);
        }
    }

    /**
     * Replace the former teacher role of pool users by the pool role. Safe to run repeatedly.
     */
    public static function migrate_to_pool_role(): void {
        global $DB;

        $teacherid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        $poolroleid = self::ensure_role();
        [$insql, $params] = $DB->get_in_or_equal(self::get_pool_usernames(), SQL_PARAMS_NAMED);
        $assignments = $DB->get_records_sql(
            "SELECT ra.id, ra.userid, ra.contextid
               FROM {role_assignments} ra
               JOIN {user} u ON u.id = ra.userid
              WHERE ra.roleid = :roleid AND ra.component = '' AND u.username $insql",
            ['roleid' => $teacherid] + $params
        );
        foreach ($assignments as $assignment) {
            role_unassign($teacherid, $assignment->userid, $assignment->contextid);
            role_assign($poolroleid, $assignment->userid, $assignment->contextid);
        }
    }

    /**
     * Enrol all pool users into a course with the pool role (no editing).
     * Skips users already enroled.
     *
     * @param int $courseid Target course ID.
     * @return int Number of newly enroled users.
     */
    public static function enrol_pool_into_course(int $courseid): int {
        global $DB;

        $roleid = self::ensure_role();

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
            $enrol->enrol_user($instance, $user->id, $roleid);
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
