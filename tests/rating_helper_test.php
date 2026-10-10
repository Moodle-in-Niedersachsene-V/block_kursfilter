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
 * Unit tests for block_kursfilter\rating_helper.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\rating_helper
 */

namespace block_kursfilter;

use advanced_testcase;

/**
 * Tests for the rating_helper class.
 */
final class rating_helper_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test save_rating stores a new rating successfully.
     */
    public function test_save_rating_stores_new_rating(): void {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $cookiehash = hash('sha256', 'test-visitor-' . uniqid());

        $saved = rating_helper::save_rating($course->id, $cookiehash, 4);

        $this->assertTrue($saved);
        $this->assertTrue($DB->record_exists('block_kursfilter_ratings', [
            'courseid'   => $course->id,
            'cookiehash' => $cookiehash,
            'stars'      => 4,
        ]));
    }

    /**
     * Test save_rating returns false when rating already exists.
     */
    public function test_save_rating_returns_false_for_duplicate(): void {
        $course = $this->getDataGenerator()->create_course();
        $cookiehash = hash('sha256', 'test-visitor-' . uniqid());

        rating_helper::save_rating($course->id, $cookiehash, 3);
        $saved = rating_helper::save_rating($course->id, $cookiehash, 5);

        $this->assertFalse($saved);
    }

    /**
     * Test get_existing_rating returns the stored stars.
     */
    public function test_get_existing_rating_returns_stored_stars(): void {
        $course = $this->getDataGenerator()->create_course();
        $cookiehash = hash('sha256', 'test-visitor-' . uniqid());

        rating_helper::save_rating($course->id, $cookiehash, 5);
        $existing = rating_helper::get_existing_rating($course->id, $cookiehash);

        $this->assertEquals(5, $existing);
    }

    /**
     * Test get_existing_rating returns null when no rating exists.
     */
    public function test_get_existing_rating_returns_null_when_absent(): void {
        $course = $this->getDataGenerator()->create_course();
        $cookiehash = hash('sha256', 'no-rating-' . uniqid());

        $this->assertNull(rating_helper::get_existing_rating($course->id, $cookiehash));
    }

    /**
     * Test get_course_rating returns zero avg and count when no ratings.
     */
    public function test_get_course_rating_returns_zeros_when_no_ratings(): void {
        $course = $this->getDataGenerator()->create_course();
        $result = rating_helper::get_course_rating($course->id);

        $this->assertEquals(0.0, $result['avg']);
        $this->assertEquals(0, $result['count']);
    }

    /**
     * Test get_course_rating computes correct average.
     */
    public function test_get_course_rating_computes_average(): void {
        $course = $this->getDataGenerator()->create_course();

        rating_helper::save_rating($course->id, hash('sha256', 'v1'), 4);
        rating_helper::save_rating($course->id, hash('sha256', 'v2'), 2);

        $result = rating_helper::get_course_rating($course->id);

        $this->assertEquals(2, $result['count']);
        $this->assertEquals(3.0, $result['avg']);
    }
}
