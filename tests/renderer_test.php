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
 * Tests for the block renderer (filter chips from the settings).
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\block_kursfilter_renderer::class)]
final class renderer_test extends \advanced_testcase {
    public function test_block_shows_one_chip_per_configured_filter_value(): void {
        global $PAGE;
        $this->resetAfterTest();
        set_config('schooltypes', "Gymnasium\n\n  Grundschule  \n", 'block_kursfilter');
        set_config('subjects', 'Mathematik', 'block_kursfilter');
        set_config('levels', '', 'block_kursfilter');

        $html = $PAGE->get_renderer('block_kursfilter')->render_block(7);

        $this->assertSame(2, substr_count($html, 'data-filter="schooltype"'));
        $this->assertStringContainsString('data-value="Grundschule"', $html);
        $this->assertSame(1, substr_count($html, 'data-filter="subject"'));
        $this->assertSame(0, substr_count($html, 'data-filter="level"'));
        $this->assertStringContainsString('id="kf-block-7"', $html);
    }
}
