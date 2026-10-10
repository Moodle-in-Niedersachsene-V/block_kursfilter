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
 * Unit tests for block_kursfilter\external\search_courses.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\external\search_courses
 */

namespace block_kursfilter;

use advanced_testcase;
use block_kursfilter\external\search_courses;

/**
 * Tests for the search_courses external function.
 */
final class search_courses_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('resultlimit', 100, 'block_kursfilter');
    }

    /**
     * Test that search_courses returns all visible courses without filters.
     */
    public function test_search_returns_visible_courses(): void {
        $this->setAdminUser();
        $course1 = $this->getDataGenerator()->create_course(['visible' => 1, 'fullname' => 'Alpha-Kurs']);
        $course2 = $this->getDataGenerator()->create_course(['visible' => 1, 'fullname' => 'Beta-Kurs']);
        $this->getDataGenerator()->create_course(['visible' => 0, 'fullname' => 'Unsichtbar']);

        $context = \context_system::instance();
        $result = search_courses::execute(0, '', '', '', '', '', $context->id, 100);

        $ids = array_column($result['courses'], 'id');
        $this->assertContains((int)$course1->id, $ids);
        $this->assertContains((int)$course2->id, $ids);

        // Hidden course must not appear.
        foreach ($result['courses'] as $c) {
            $this->assertNotEquals('Unsichtbar', $c['fullname']);
        }
    }

    /**
     * Test that searchterm filters by fullname.
     */
    public function test_search_filters_by_searchterm(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['fullname' => 'Mathematik Grundschule', 'visible' => 1]);
        $this->getDataGenerator()->create_course(['fullname' => 'Deutsch Gymnasium', 'visible' => 1]);

        $context = \context_system::instance();
        $result = search_courses::execute(0, '', '', '', '', 'Mathematik', $context->id, 100);

        $fullnames = array_column($result['courses'], 'fullname');
        $this->assertCount(1, array_filter($fullnames, fn($n) => str_contains($n, 'Mathematik')));
    }

    /**
     * Test that server enforces the result limit.
     */
    public function test_server_enforces_result_limit(): void {
        $this->setAdminUser();
        set_config('resultlimit', 2, 'block_kursfilter');

        for ($i = 1; $i <= 5; $i++) {
            $this->getDataGenerator()->create_course(['fullname' => "Kurs $i", 'visible' => 1]);
        }

        $context = \context_system::instance();
        $result = search_courses::execute(0, '', '', '', '', '', $context->id, 100);

        $this->assertLessThanOrEqual(2, count($result['courses']));
    }

    /**
     * Test total matches courses count.
     */
    public function test_total_matches_courses_count(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['fullname' => 'Kurs A', 'visible' => 1]);
        $this->getDataGenerator()->create_course(['fullname' => 'Kurs B', 'visible' => 1]);

        $context = \context_system::instance();
        $result = search_courses::execute(0, '', '', '', '', '', $context->id, 100);

        $this->assertEquals(count($result['courses']), $result['total']);
    }

    /**
     * Test result structure contains required fields.
     */
    public function test_result_structure_has_required_fields(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['fullname' => 'Struktur-Test', 'visible' => 1]);

        $context = \context_system::instance();
        $result = search_courses::execute(0, '', '', '', '', 'Struktur-Test', $context->id, 100);

        $this->assertNotEmpty($result['courses']);
        $course = $result['courses'][0];

        foreach (
            ['id', 'fullname', 'shortname', 'summary', 'categoryname', 'tags',
                  'courseurl', 'exporturl', 'hasexport', 'ratingavg', 'ratingcount',
                  'userrating', 'alreadyrated'] as $field
        ) {
            $this->assertArrayHasKey($field, $course, "Missing field: $field");
        }
    }

    /**
     * Test site course is never returned.
     */
    public function test_site_course_excluded(): void {
        $this->setAdminUser();
        $context = \context_system::instance();
        $result = search_courses::execute(0, '', '', '', '', '', $context->id, 100);

        foreach ($result['courses'] as $c) {
            $this->assertNotEquals(SITEID, $c['id']);
        }
    }
}
