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
 * Tests for the logout observer that releases pool accounts.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(observer::class)]
final class observer_test extends \advanced_testcase {
    /**
     * Triggers a logout event for the given user.
     *
     * @param int $userid User ID.
     */
    private function logout(int $userid): void {
        \core\event\user_loggedout::create([
            'userid' => $userid,
            'objectid' => $userid,
            'other' => ['sessionid' => 'phpunit'],
        ])->trigger();
    }

    public function test_logout_of_pool_account_frees_it(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_accounts();
        $username = pool_manager::get_pool_usernames()[0];
        pool_manager::mark_occupied($username);
        $this->assertNull(pool_manager::get_free_pool_account());

        $this->logout((int)$DB->get_field('user', 'id', ['username' => $username]));

        $this->assertSame($username, pool_manager::get_free_pool_account()->username);
    }

    public function test_logout_of_other_user_keeps_pool_occupied(): void {
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_accounts();
        pool_manager::mark_occupied(pool_manager::get_pool_usernames()[0]);

        $this->logout($this->getDataGenerator()->create_user()->id);

        $this->assertNull(pool_manager::get_free_pool_account());
    }
}
