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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the pool accounts (creation, enrolment, role rights, occupancy).
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(pool_manager::class)]
final class pool_manager_test extends \advanced_testcase {
    /**
     * Returns the pool user IDs according to the current pool size.
     *
     * @return int[] User IDs.
     */
    private function pool_userids(): array {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal(pool_manager::get_pool_usernames());
        return array_keys($DB->get_records_select('user', "username $insql", $params, '', 'id'));
    }

    public function test_pool_size_uses_default_and_is_capped(): void {
        $this->resetAfterTest();

        $this->assertSame(pool_manager::POOL_SIZE_DEFAULT, pool_manager::get_pool_size());
        set_config('poolsize', -3, 'block_kursfilter');
        $this->assertSame(pool_manager::POOL_SIZE_DEFAULT, pool_manager::get_pool_size());
        set_config('poolsize', 100000, 'block_kursfilter');
        $this->assertSame(pool_manager::POOL_SIZE_MAX, pool_manager::get_pool_size());
        $this->assertCount(pool_manager::POOL_SIZE_MAX, pool_manager::get_pool_usernames());
    }

    public function test_create_pool_users_creates_missing_accounts_only_once(): void {
        $this->resetAfterTest();
        set_config('poolsize', 12, 'block_kursfilter');

        $first = pool_manager::create_pool_users();
        $second = pool_manager::create_pool_users();

        // New accounts must not trigger Moodle validation warnings (e.g. language not installed).
        $this->assertDebuggingNotCalled();

        $this->assertSame(0, $second);
        $this->assertCount(12, $this->pool_userids());
        $this->assertLessThanOrEqual(12, $first);
    }

    public function test_pool_accounts_cannot_be_logged_into_with_guessable_passwords(): void {
        $this->resetAfterTest();
        pool_manager::create_pool_users();
        $username = pool_manager::get_pool_usernames()[0];
        $_SERVER['HTTP_USER_AGENT'] = 'phpunit';

        foreach (['', $username, 'password', 'Kursbesucher', 'kursfilter'] as $password) {
            $this->assertFalse(authenticate_user_login($username, $password), "Passwort '$password'");
        }
    }

    public function test_enrol_pool_into_course_assigns_pool_role_once(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('poolsize', 3, 'block_kursfilter');
        pool_manager::create_pool_users();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);

        $first = pool_manager::enrol_pool_into_course($course->id);
        $second = pool_manager::enrol_pool_into_course($course->id);
        $roleid = $DB->get_field('role', 'id', ['shortname' => pool_manager::ROLE_SHORTNAME]);

        $this->assertSame(3, $first);
        $this->assertSame(0, $second);
        foreach ($this->pool_userids() as $id) {
            $this->assertTrue(is_enrolled($context, $id, '', true));
            $this->assertTrue(user_has_role_assignment($id, $roleid, $context->id));
            $this->assertFalse(is_enrolled(\context_course::instance($other->id), $id));
        }
    }

    public function test_pool_role_has_no_write_rights_in_course(): void {
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_users();
        $course = $this->getDataGenerator()->create_course();
        pool_manager::enrol_pool_into_course($course->id);
        $userid = $this->pool_userids()[0];
        $context = \context_course::instance($course->id);

        $denied = [
            'moodle/course:update', 'moodle/course:manageactivities', 'moodle/course:delete',
            'moodle/backup:backupcourse', 'moodle/restore:restorecourse', 'moodle/role:assign',
            'moodle/course:enrolreview', 'enrol/manual:enrol', 'moodle/user:update', 'moodle/site:config',
        ];
        foreach ($denied as $capability) {
            $this->assertFalse(has_capability($capability, $context, $userid), $capability);
        }
    }

    public function test_enrol_into_all_courses_skips_hidden_courses_and_site(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('poolsize', 2, 'block_kursfilter');
        pool_manager::create_pool_users();
        $visible = $this->getDataGenerator()->create_course();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        pool_manager::enrol_pool_into_all_courses();

        $userid = $this->pool_userids()[0];
        $this->assertTrue(is_enrolled(\context_course::instance($visible->id), $userid, '', true));
        $this->assertFalse(is_enrolled(\context_course::instance($hidden->id), $userid));
        $this->assertFalse($DB->record_exists('enrol', ['courseid' => SITEID, 'enrol' => 'manual']));
    }

    public function test_free_pool_user_changes_with_occupancy(): void {
        $this->resetAfterTest();
        set_config('poolsize', 2, 'block_kursfilter');
        pool_manager::create_pool_users();
        [$first, $second] = pool_manager::get_pool_usernames();

        $this->assertSame($first, pool_manager::get_free_pool_user()->username);
        pool_manager::mark_active($first);
        $this->assertSame($second, pool_manager::get_free_pool_user()->username);
        pool_manager::mark_active($second);
        $this->assertNull(pool_manager::get_free_pool_user());
        pool_manager::mark_free($first);
        $this->assertSame($first, pool_manager::get_free_pool_user()->username);
    }

    public function test_pool_role_cannot_see_participants_identity_or_grades(): void {
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_users();
        $course = $this->getDataGenerator()->create_course();
        pool_manager::enrol_pool_into_course($course->id);
        $userid = $this->pool_userids()[0];
        $context = \context_course::instance($course->id);

        foreach (['moodle/course:viewparticipants', 'moodle/site:viewuseridentity', 'moodle/grade:viewall'] as $capability) {
            $this->assertFalse(has_capability($capability, $context, $userid), $capability);
        }
    }

    public function test_pool_role_still_sees_hidden_activities(): void {
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_users();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'visible' => 0]);
        pool_manager::enrol_pool_into_course($course->id);

        $this->assertTrue(has_capability(
            'moodle/course:viewhiddenactivities',
            \context_module::instance($page->cmid),
            $this->pool_userids()[0]
        ));
    }

    public function test_migrate_moves_teacher_assignments_of_pool_users_only(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_users();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacherid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        $poolid = $this->pool_userids()[0];
        $realteacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($poolid, $course->id, 'teacher');
        $this->getDataGenerator()->enrol_user($realteacher->id, $course->id, 'teacher');

        pool_manager::migrate_to_pool_role();
        pool_manager::migrate_to_pool_role();

        $poolroleid = $DB->get_field('role', 'id', ['shortname' => pool_manager::ROLE_SHORTNAME]);
        $this->assertTrue(user_has_role_assignment($poolid, $poolroleid, $context->id));
        $this->assertFalse(user_has_role_assignment($poolid, $teacherid, $context->id));
        $this->assertTrue(user_has_role_assignment($realteacher->id, $teacherid, $context->id));
        $this->assertFalse(has_capability('moodle/course:viewparticipants', $context, $poolid));
    }

    public function test_remove_role_deletes_the_pool_role(): void {
        global $DB;
        $this->resetAfterTest();
        pool_manager::ensure_role();
        pool_manager::ensure_role();
        $this->assertSame(1, $DB->count_records('role', ['shortname' => pool_manager::ROLE_SHORTNAME]));

        pool_manager::remove_role();

        $this->assertFalse($DB->record_exists('role', ['shortname' => pool_manager::ROLE_SHORTNAME]));
    }

    public function test_marking_active_ends_existing_sessions_of_the_account(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('poolsize', 2, 'block_kursfilter');
        pool_manager::create_pool_users();
        [$first, $second] = $this->pool_userids();
        $usernames = pool_manager::get_pool_usernames();
        foreach ([[$first, 'sid-first'], [$second, 'sid-second']] as [$userid, $sid]) {
            $DB->insert_record('sessions', (object)[
                'state' => 0, 'sid' => $sid, 'userid' => $userid, 'sessdata' => null,
                'timecreated' => time(), 'timemodified' => time(), 'firstip' => '127.0.0.1', 'lastip' => '127.0.0.1',
            ]);
        }

        pool_manager::mark_active($usernames[0]);

        $this->assertSame(0, $DB->count_records('sessions', ['userid' => $first]));
        $this->assertSame(1, $DB->count_records('sessions', ['userid' => $second]));
    }
}
