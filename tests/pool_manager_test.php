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
class pool_manager_test extends advanced_testcase {
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
     */
    public function test_get_pool_size_reads_config(): void {
        $this->assertEquals(3, pool_manager::get_pool_size());
    }

    /**
     * Test get_pool_size uses default when no config is set.
     */
    public function test_get_pool_size_uses_default(): void {
        unset_config('poolsize', 'block_kursfilter');
        $this->assertEquals(pool_manager::POOL_SIZE_DEFAULT, pool_manager::get_pool_size());
    }

    /**
     * Test get_pool_size caps at maximum.
     */
    public function test_get_pool_size_capped_at_maximum(): void {
        set_config('poolsize', 999, 'block_kursfilter');
        $this->assertEquals(pool_manager::POOL_SIZE_MAX, pool_manager::get_pool_size());
    }

    /**
     * Test get_pool_usernames returns correct usernames.
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
     */
    public function test_mark_active_and_mark_free(): void {
        pool_manager::create_pool_users();
        $user = pool_manager::get_free_pool_user();
        $this->assertNotNull($user);

        // mark_active / mark_free expect the username string, not the user object.
        pool_manager::mark_active($user->username);
        pool_manager::mark_free($user->username);

        // After mark_free, user should be available again.
        $freeuser = pool_manager::get_free_pool_user();
        $this->assertNotNull($freeuser);
    }
}
