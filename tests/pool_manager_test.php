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
 * Unit tests for block_kursfilter\pool_manager.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\pool_manager
 */

namespace block_kursfilter;

use advanced_testcase;

/**
 * Tests for the pool_manager class.
 */
final class pool_manager_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('poolsize', 3, 'block_kursfilter');
    }

    /**
     * Test get_pool_size respects configuration.
     *
     * @covers \block_kursfilter\pool_manager::get_pool_size
     */
    public function test_get_pool_size_reads_config(): void {
        $this->assertEquals(3, pool_manager::get_pool_size());
    }

    /**
     * Test get_pool_size uses default when no config is set.
     *
     * @covers \block_kursfilter\pool_manager::get_pool_size
     */
    public function test_get_pool_size_uses_default(): void {
        unset_config('poolsize', 'block_kursfilter');
        $this->assertEquals(pool_manager::POOL_SIZE_DEFAULT, pool_manager::get_pool_size());
    }

    /**
     * Test get_pool_size caps at maximum.
     *
     * @covers \block_kursfilter\pool_manager::get_pool_size
     */
    public function test_get_pool_size_capped_at_maximum(): void {
        set_config('poolsize', 999, 'block_kursfilter');
        $this->assertEquals(pool_manager::POOL_SIZE_MAX, pool_manager::get_pool_size());
    }

    /**
     * Test get_pool_usernames returns correct usernames.
     *
     * @covers \block_kursfilter\pool_manager::get_pool_usernames
     */
    public function test_get_pool_usernames(): void {
        $names = pool_manager::get_pool_usernames();
        $this->assertCount(3, $names);
        foreach ($names as $name) {
            $this->assertStringStartsWith(pool_manager::USERNAME_PREFIX, $name);
        }
    }

    /**
     * Test create_pool_users creates the configured number of users.
     *
     * @covers \block_kursfilter\pool_manager::create_pool_users
     */
    public function test_create_pool_users_creates_users(): void {
        global $DB;

        pool_manager::create_pool_users();

        $count = $DB->count_records_select(
            'user',
            $DB->sql_like('username', ':prefix'),
            ['prefix' => pool_manager::USERNAME_PREFIX . '%']
        );
        $this->assertEquals(3, $count);
    }

    /**
     * Test create_pool_users is idempotent (running twice creates no duplicates).
     *
     * @covers \block_kursfilter\pool_manager::create_pool_users
     */
    public function test_create_pool_users_is_idempotent(): void {
        global $DB;

        pool_manager::create_pool_users();
        pool_manager::create_pool_users();

        $count = $DB->count_records_select(
            'user',
            $DB->sql_like('username', ':prefix'),
            ['prefix' => pool_manager::USERNAME_PREFIX . '%']
        );
        $this->assertEquals(3, $count);
    }

    /**
     * Test mark_active and mark_free cycle.
     *
     * @covers \block_kursfilter\pool_manager::mark_active
     * @covers \block_kursfilter\pool_manager::mark_free
     * @covers \block_kursfilter\pool_manager::get_free_pool_user
     */
    public function test_mark_active_and_mark_free(): void {
        pool_manager::create_pool_users();
        $user = pool_manager::get_free_pool_user();
        $this->assertNotNull($user);

        // Mark_active / mark_free expect the username string, not the user object.
        pool_manager::mark_active($user->username);
        pool_manager::mark_free($user->username);

        // After mark_free, user should be available again.
        $freeuser = pool_manager::get_free_pool_user();
        $this->assertNotNull($freeuser);
    }

    // -------------------------------------------------------------------------
    // Tests for pool restriction role (kursfilter_pooluser)
    // -------------------------------------------------------------------------

    /**
     * ensure_pool_role() creates the kursfilter_pooluser role on first call.
     *
     * @covers \block_kursfilter\pool_manager::ensure_pool_role
     */
    public function test_ensure_pool_role_creates_role(): void {
        global $DB;

        // Role must not exist yet.
        $this->assertFalse(
            $DB->record_exists('role', ['shortname' => pool_manager::RESTRICT_ROLE_SHORTNAME])
        );

        $roleid = pool_manager::ensure_pool_role();

        $this->assertGreaterThan(0, $roleid);
        $this->assertTrue(
            $DB->record_exists('role', ['shortname' => pool_manager::RESTRICT_ROLE_SHORTNAME])
        );
    }

    /**
     * ensure_pool_role() is idempotent: two calls return the same role ID.
     *
     * @covers \block_kursfilter\pool_manager::ensure_pool_role
     */
    public function test_ensure_pool_role_is_idempotent(): void {
        global $DB;

        $first  = pool_manager::ensure_pool_role();
        $second = pool_manager::ensure_pool_role();

        $this->assertSame($first, $second);

        // Exactly one role with this shortname.
        $this->assertEquals(
            1,
            $DB->count_records('role', ['shortname' => pool_manager::RESTRICT_ROLE_SHORTNAME])
        );
    }

    /**
     * Data provider: all capabilities that must be prohibited on the restriction role.
     *
     * @return array<string, array{string}>
     */
    public static function prohibited_capabilities_provider(): array {
        return [
            'editownprofile'        => ['moodle/user:editownprofile'],
            'changeownpassword'     => ['moodle/user:changeownpassword'],
            'editownmessageprofile' => ['moodle/user:editownmessageprofile'],
            'manageownfiles'        => ['moodle/user:manageownfiles'],
            'sendmessage'           => ['moodle/site:sendmessage'],
        ];
    }

    /**
     * ensure_pool_role() sets CAP_PROHIBIT on every restricted capability.
     *
     * @dataProvider prohibited_capabilities_provider
     * @covers \block_kursfilter\pool_manager::ensure_pool_role
     */
    public function test_ensure_pool_role_prohibits_capability(string $capability): void {
        $roleid        = pool_manager::ensure_pool_role();
        $systemcontext = \context_system::instance();

        $caps = role_context_capabilities($roleid, $systemcontext);

        $this->assertArrayHasKey($capability, $caps,
            "Capability {$capability} missing from restriction role.");
        $this->assertEquals(CAP_PROHIBIT, $caps[$capability],
            "Capability {$capability} must be CAP_PROHIBIT.");
    }

    /**
     * assign_restrict_role() assigns the role to a user at system context.
     *
     * @covers \block_kursfilter\pool_manager::assign_restrict_role
     */
    public function test_assign_restrict_role_assigns_to_user(): void {
        global $DB;

        $user          = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();
        $roleid        = pool_manager::ensure_pool_role();

        pool_manager::assign_restrict_role((int)$user->id);

        $this->assertTrue(
            $DB->record_exists('role_assignments', [
                'roleid'    => $roleid,
                'userid'    => $user->id,
                'contextid' => $systemcontext->id,
            ])
        );
    }

    /**
     * assign_restrict_role() is idempotent: calling it twice does not create duplicates.
     *
     * @covers \block_kursfilter\pool_manager::assign_restrict_role
     */
    public function test_assign_restrict_role_is_idempotent(): void {
        global $DB;

        $user          = $this->getDataGenerator()->create_user();
        $systemcontext = \context_system::instance();
        $roleid        = pool_manager::ensure_pool_role();

        pool_manager::assign_restrict_role((int)$user->id);
        pool_manager::assign_restrict_role((int)$user->id);

        $count = $DB->count_records('role_assignments', [
            'roleid'    => $roleid,
            'userid'    => $user->id,
            'contextid' => $systemcontext->id,
        ]);
        $this->assertEquals(1, $count);
    }

    /**
     * create_pool_users() automatically assigns the restriction role to new pool users.
     *
     * After pool creation every pool user must have the kursfilter_pooluser
     * role assigned at system context so that profile editing and password
     * changes are effectively prohibited.
     *
     * @covers \block_kursfilter\pool_manager::create_pool_users
     * @covers \block_kursfilter\pool_manager::ensure_pool_role
     * @covers \block_kursfilter\pool_manager::assign_restrict_role
     */
    public function test_create_pool_users_assigns_restrict_role(): void {
        global $DB;

        pool_manager::create_pool_users();

        $roleid        = pool_manager::ensure_pool_role();
        $systemcontext = \context_system::instance();

        foreach (pool_manager::get_pool_usernames() as $username) {
            $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], 'id', MUST_EXIST);

            $this->assertTrue(
                $DB->record_exists('role_assignments', [
                    'roleid'    => $roleid,
                    'userid'    => $user->id,
                    'contextid' => $systemcontext->id,
                ]),
                "Pool user {$username} is missing the restriction role assignment."
            );
        }
    }

    /**
     * Pool users with the restriction role cannot edit their own profile.
     *
     * This verifies that the CAP_PROHIBIT actually prevents the capability
     * for a pool user after role assignment — i.e., the end-to-end effect
     * of create_pool_users() on the Moodle capability system.
     *
     * @covers \block_kursfilter\pool_manager::create_pool_users
     * @covers \block_kursfilter\pool_manager::ensure_pool_role
     */
    public function test_pool_user_cannot_edit_own_profile(): void {
        pool_manager::create_pool_users();

        $username  = pool_manager::get_pool_usernames()[0];
        $pooluser  = \core_user::get_user_by_username($username);
        $userctx   = \context_user::instance($pooluser->id);

        // The system-context prohibition must cascade into the user context.
        $this->assertFalse(
            has_capability('moodle/user:editownprofile', $userctx, $pooluser->id),
            'Pool user should not be able to edit own profile after restriction role is assigned.'
        );
    }

    /**
     * Data provider: end-to-end capability checks for a pool user.
     *
     * @return array<string, array{string, string}>
     */
    public static function pool_user_capability_provider(): array {
        return [
            'editownprofile'        => ['moodle/user:editownprofile',        'user'],
            'changeownpassword'     => ['moodle/user:changeownpassword',      'user'],
            'editownmessageprofile' => ['moodle/user:editownmessageprofile',  'user'],
            'manageownfiles'        => ['moodle/user:manageownfiles',         'user'],
            'sendmessage'           => ['moodle/site:sendmessage',            'system'],
        ];
    }

    /**
     * Pool users with the restriction role have all sensitive capabilities prohibited.
     *
     * Checks the end-to-end effect: has_capability() must return false
     * for every restricted capability after pool users are created.
     *
     * @dataProvider pool_user_capability_provider
     * @covers \block_kursfilter\pool_manager::create_pool_users
     * @covers \block_kursfilter\pool_manager::ensure_pool_role
     */
    public function test_pool_user_capability_is_prohibited(string $capability, string $contexttype): void {
        pool_manager::create_pool_users();

        $username = pool_manager::get_pool_usernames()[0];
        $pooluser = \core_user::get_user_by_username($username);

        if ($contexttype === 'user') {
            $ctx = \context_user::instance($pooluser->id);
        } else {
            $ctx = \context_system::instance();
        }

        $this->assertFalse(
            has_capability($capability, $ctx, $pooluser->id),
            "Pool user must not have capability {$capability}."
        );
    }
}
