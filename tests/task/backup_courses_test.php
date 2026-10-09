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

namespace block_kursfilter\task;

use block_kursfilter\backup_helper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the nightly backup task.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(backup_courses::class)]
final class backup_courses_test extends \advanced_testcase {
    public function test_task_backs_up_public_courses_only(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $public = $generator->create_course();
        $hidden = $generator->create_course(['visible' => 0]);

        $this->expectOutputRegex('/Course ' . $public->id . ': backup stored/');
        (new backup_courses())->execute();

        $this->assertTrue(backup_helper::has_backup((int)$public->id));
        $this->assertFalse(backup_helper::has_backup((int)$hidden->id));
    }

    public function test_task_fails_with_cause_when_backup_user_lacks_permission(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        set_config('backup_adminid', $generator->create_user()->id, 'block_kursfilter');

        $this->expectOutputRegex('/Course ' . $course->id . ': backup failed: /');
        try {
            (new backup_courses())->execute();
            $this->fail('The task must fail when a backup fails.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error_backups_failed', $e->errorcode);
        }
        $this->assertFalse(backup_helper::has_backup((int)$course->id));
    }

    public function test_failed_backup_keeps_the_previous_backup(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $previous = backup_helper::backup_course((int)$course->id, (int)get_admin()->id);

        try {
            backup_helper::backup_course((int)$course->id, (int)$generator->create_user()->id);
            $this->fail('A backup without permission must fail.');
        } catch (\moodle_exception $e) {
            $this->assertEquals($previous->get_id(), backup_helper::get_backup_file((int)$course->id)->get_id());
        }
    }
}
