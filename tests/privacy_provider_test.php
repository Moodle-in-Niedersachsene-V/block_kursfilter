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
 * Unit tests for block_kursfilter privacy provider.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\privacy\provider
 */

namespace block_kursfilter;

use advanced_testcase;
use block_kursfilter\privacy\provider;
use core_privacy\local\metadata\null_provider;

/**
 * Tests for the privacy provider.
 */
final class privacy_provider_test extends advanced_testcase {
    /**
     * Test provider implements null_provider.
     */
    public function test_provider_implements_null_provider(): void {
        $this->assertInstanceOf(null_provider::class, new provider());
    }

    /**
     * Test get_reason returns a non-empty string.
     */
    public function test_get_reason_returns_string(): void {
        $reason = provider::get_reason();
        $this->assertIsString($reason);
        $this->assertNotEmpty($reason);
    }

    /**
     * Test get_reason returns the expected language string key.
     */
    public function test_get_reason_key(): void {
        $this->assertEquals('privacy:metadata', provider::get_reason());
    }
}
