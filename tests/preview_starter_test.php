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
 * Tests for the preview decisions.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(preview_starter::class)]
final class preview_starter_test extends \advanced_testcase {
    public function test_unknown_hidden_and_site_course_are_not_found(): void {
        $this->resetAfterTest();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        $this->assertSame(preview_starter::COURSE_NOT_FOUND, preview_starter::start((int)$hidden->id, false));
        $this->assertSame(preview_starter::COURSE_NOT_FOUND, preview_starter::start(99999, false));
        $this->assertSame(preview_starter::COURSE_NOT_FOUND, preview_starter::start(SITEID, true));
    }

    public function test_real_login_is_redirected_without_taking_a_pool_account(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_accounts();

        $outcome = preview_starter::start((int)$course->id, true);

        $this->assertSame(preview_starter::REDIRECT, $outcome);
        $this->assertFalse(pool_manager::is_pool_account($GLOBALS['USER']->username));
        $this->assertSame(0, $DB->count_records('sessions'));
    }

    public function test_free_pool_account_is_logged_in(): void {
        global $USER;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_accounts();
        // The login regenerates the session id, which PHPUnit's headless run cannot do; only that warning is muted.
        set_error_handler(static fn() => true, E_WARNING);
        try {
            $outcome = preview_starter::start((int)$course->id, false);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(preview_starter::LOGGED_IN, $outcome);
        $this->assertTrue(pool_manager::is_pool_account($USER->username));
    }

    public function test_pool_full_when_every_pool_account_is_occupied(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        set_config('poolsize', 1, 'block_kursfilter');
        pool_manager::create_pool_accounts();
        $poolname = pool_manager::get_pool_usernames()[0];
        $DB->insert_record('sessions', [
            'state' => 0,
            'sid' => 'kfpreviewtest',
            'userid' => $DB->get_field('user', 'id', ['username' => $poolname]),
            'sessdata' => null,
            'timecreated' => time(),
            'timemodified' => time(),
            'firstip' => '203.0.113.9',
            'lastip' => '203.0.113.9',
        ]);

        $this->assertSame(preview_starter::POOL_FULL, preview_starter::start((int)$course->id, false));
    }
}
