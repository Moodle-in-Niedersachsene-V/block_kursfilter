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
 * Unit tests for block_kursfilter\course_access.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\course_access
 */

namespace block_kursfilter;

use advanced_testcase;

/**
 * Tests for the course_access class.
 */
final class course_access_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test get_public_course returns a visible course.
     *
     * @covers \block_kursfilter\course_access::get_public_course
     */
    public function test_get_public_course_returns_visible_course(): void {
        $course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $result = course_access::get_public_course($course->id);

        $this->assertNotNull($result);
        $this->assertEquals($course->id, $result->id);
    }

    /**
     * Test get_public_course returns null for hidden course.
     *
     * @covers \block_kursfilter\course_access::get_public_course
     */
    public function test_get_public_course_returns_null_for_hidden_course(): void {
        global $DB;
        $course = $this->getDataGenerator()->create_course(['visible' => 1]);
        $DB->set_field('course', 'visible', 0, ['id' => $course->id]);

        $result = course_access::get_public_course($course->id);
        $this->assertNull($result);
    }

    /**
     * Test get_public_course returns null for non-existent course.
     *
     * @covers \block_kursfilter\course_access::get_public_course
     */
    public function test_get_public_course_returns_null_for_nonexistent(): void {
        $result = course_access::get_public_course(999999);
        $this->assertNull($result);
    }

    /**
     * Test get_public_course returns null for site course.
     *
     * @covers \block_kursfilter\course_access::get_public_course
     */
    public function test_get_public_course_returns_null_for_site_course(): void {
        $result = course_access::get_public_course(SITEID);
        $this->assertNull($result);
    }
}
