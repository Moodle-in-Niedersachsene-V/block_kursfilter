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
 * Unit tests for block_kursfilter\observer.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\observer
 */

namespace block_kursfilter;

use advanced_testcase;

/**
 * Tests for the observer class.
 */
final class observer_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that logout event for a non-pool user is silently ignored.
     *
     * @covers \block_kursfilter\observer::user_loggedout
     */
    public function test_logout_of_non_pool_user_does_not_error(): void {
        $user = $this->getDataGenerator()->create_user();

        $event = \core\event\user_loggedout::create([
            'objectid' => $user->id,
            'context'  => \context_system::instance(),
            'other'    => ['sessionid' => 'testsession'],
        ]);

        // Should not throw.
        observer::user_loggedout($event);
        $this->assertTrue(true);
    }

    /**
     * Test that logout event for a pool user calls mark_free.
     *
     * We verify indirectly that no exception is thrown and the cache
     * entry is handled gracefully (pool user without an active cache entry).
     *
     * @covers \block_kursfilter\observer::user_loggedout
     */
    public function test_logout_of_pool_user_does_not_error(): void {
        $pooluser = $this->getDataGenerator()->create_user([
            'username' => pool_manager::USERNAME_PREFIX . '01',
        ]);

        $event = \core\event\user_loggedout::create([
            'objectid' => $pooluser->id,
            'context'  => \context_system::instance(),
            'other'    => ['sessionid' => 'testsession'],
        ]);

        // Should not throw even when no active session is in the cache.
        observer::user_loggedout($event);
        $this->assertTrue(true);
    }
}
