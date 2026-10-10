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
 * Unit tests for the block_kursfilter block class.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter
 */

namespace block_kursfilter;

use advanced_testcase;

/**
 * Tests for the main block class.
 */
final class block_kursfilter_test extends advanced_testcase {
    /**
     * Set up each test.
     */
    protected function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();

        // Moodle block classes are not PSR-4 autoloaded; require manually.
        require_once($CFG->dirroot . '/blocks/kursfilter/block_kursfilter.php');
    }

    /**
     * Test applicable_formats returns expected pages.
     *
     * @covers \block_kursfilter::applicable_formats
     */
    public function test_applicable_formats(): void {
        $block = new \block_kursfilter();
        $formats = $block->applicable_formats();

        $this->assertArrayHasKey('site-index', $formats);
        $this->assertArrayHasKey('my', $formats);
        $this->assertTrue($formats['site-index']);
        $this->assertTrue($formats['my']);
    }

    /**
     * Test has_config returns true.
     *
     * @covers \block_kursfilter::has_config
     */
    public function test_has_config(): void {
        $block = new \block_kursfilter();
        $this->assertTrue($block->has_config());
    }

    /**
     * Test instance_allow_multiple returns false.
     *
     * @covers \block_kursfilter::instance_allow_multiple
     */
    public function test_instance_allow_multiple(): void {
        $block = new \block_kursfilter();
        $this->assertFalse($block->instance_allow_multiple());
    }
}
