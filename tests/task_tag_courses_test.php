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
 * Unit tests for block_kursfilter\task\tag_courses.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\task\tag_courses
 */

namespace block_kursfilter;

use advanced_testcase;
use block_kursfilter\task\tag_courses;

/**
 * Tests for the tag_courses scheduled task.
 */
final class task_tag_courses_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('ai_enabled', 0, 'block_kursfilter');
        set_config('ai_batch_size', 5, 'block_kursfilter');
    }

    /**
     * Test get_name returns a non-empty string.
     *
     * @covers \block_kursfilter\task\tag_courses::get_name
     */
    public function test_get_name(): void {
        $task = new tag_courses();
        $name = $task->get_name();
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * Test execute does not run when AI is disabled.
     *
     * When ai_enabled = 0, execute() should return early without errors.
     *
     * @covers \block_kursfilter\task\tag_courses::execute
     */
    public function test_execute_does_nothing_when_disabled(): void {
        set_config('ai_enabled', 0, 'block_kursfilter');
        $this->getDataGenerator()->create_course(['visible' => 1]);

        $task = new tag_courses();
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        $this->assertStringContainsString('deaktiviert', $output);
    }
}
