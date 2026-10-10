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
 * Tests for the public course visibility check shared by the public endpoints.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(course_access::class)]
final class course_access_test extends \advanced_testcase {
    public function test_visible_course_is_returned(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'OPEN1']);

        $found = course_access::get_public_course((int)$course->id);

        $this->assertNotNull($found);
        $this->assertSame('OPEN1', $found->shortname);
    }

    public function test_hidden_unknown_and_site_course_are_not_returned(): void {
        $this->resetAfterTest();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        $this->assertNull(course_access::get_public_course((int)$hidden->id));
        $this->assertNull(course_access::get_public_course(99999));
        $this->assertNull(course_access::get_public_course(SITEID));
    }
}
