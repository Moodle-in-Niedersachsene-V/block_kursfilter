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
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the hook callbacks.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(hook_callbacks::class)]
final class hook_callbacks_test extends \advanced_testcase {
    /**
     * Run the callback as the given user on the given script.
     *
     * @param \stdClass $user User.
     * @param string $script Script path relative to wwwroot.
     */
    private function call(\stdClass $user, string $script): void {
        global $PAGE, $SCRIPT;
        $SCRIPT = $script;
        $this->setUser($user);
        hook_callbacks::before_http_headers(new \core\hook\output\before_http_headers($PAGE->get_renderer('core')));
    }

    /**
     * Scripts that are protected.
     *
     * @return array
     */
    public static function protected_scripts(): array {
        return array_map(fn($script) => [$script], hook_callbacks::PROTECTED_SCRIPTS);
    }

    #[DataProvider('protected_scripts')]
    public function test_pool_account_is_redirected_from_profile_pages(string $script): void {
        global $DB;
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_accounts();
        $pool = $DB->get_record('user', ['username' => pool_manager::get_pool_usernames()[0]]);

        $this->expectException(\moodle_exception::class);
        $this->call($pool, $script);
    }

    public function test_pool_account_may_open_other_pages(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_accounts();
        $pool = $DB->get_record('user', ['username' => pool_manager::get_pool_usernames()[0]]);

        $this->call($pool, '/course/view.php');

        $this->assertTrue(true);
    }

    public function test_regular_user_may_open_profile(): void {
        $this->resetAfterTest();

        $this->call($this->getDataGenerator()->create_user(), '/user/profile.php');

        $this->assertTrue(true);
    }

    public function test_pool_role_prohibits_profile_capabilities(): void {
        global $DB;
        $this->resetAfterTest();
        $roleid = pool_manager::ensure_role();

        foreach (['moodle/user:viewdetails', 'moodle/user:editownprofile', 'moodle/user:changeownpassword'] as $capability) {
            $this->assertSame(CAP_PROHIBIT, (int)$DB->get_field('role_capabilities', 'permission', [
                'roleid' => $roleid,
                'capability' => $capability,
                'contextid' => \context_system::instance()->id,
            ]));
        }
    }
}
