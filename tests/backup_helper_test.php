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
 * Unit tests for block_kursfilter\backup_helper.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\backup_helper
 */

namespace block_kursfilter;

use advanced_testcase;

/**
 * Tests for the backup_helper class.
 */
final class backup_helper_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test has_backup returns false when no backup exists.
     */
    public function test_has_backup_returns_false_when_no_backup(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->assertFalse(backup_helper::has_backup($course->id));
    }

    /**
     * Test get_backup_file returns null when no backup exists.
     */
    public function test_get_backup_file_returns_null_when_no_backup(): void {
        $course = $this->getDataGenerator()->create_course();
        $this->assertNull(backup_helper::get_backup_file($course->id));
    }

    /**
     * Test delete_existing_backup does not error when no file exists.
     */
    public function test_delete_existing_backup_no_error_when_empty(): void {
        $context = \context_system::instance();
        // Should not throw.
        backup_helper::delete_existing_backup($context, 99999);
        $this->assertTrue(true);
    }

    /**
     * Test FILEAREA and COMPONENT constants are defined correctly.
     */
    public function test_constants(): void {
        $this->assertEquals('course_backups', backup_helper::FILEAREA);
        $this->assertEquals('block_kursfilter', backup_helper::COMPONENT);
    }
}
