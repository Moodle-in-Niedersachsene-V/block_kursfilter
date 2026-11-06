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
 * Tests for the filter kinds and their value lists.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(filter_values::class)]
final class filter_values_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_kinds_are_school_type_subject_and_level(): void {
        $this->assertSame(['schooltype', 'subject', 'level'], filter_values::kinds());
    }

    public function test_each_kind_has_its_setting(): void {
        $this->assertSame('schooltypes', filter_values::setting('schooltype'));
        $this->assertSame('subjects', filter_values::setting('subject'));
        $this->assertSame('levels', filter_values::setting('level'));
    }

    public function test_values_are_trimmed_cleaned_and_without_empty_lines(): void {
        set_config('subjects', "  Mathematik \r\n\n<b>Deutsch</b>\n   \n", 'block_kursfilter');

        $this->assertSame(['Mathematik', 'Deutsch'], filter_values::values('subject'));
    }

    public function test_values_keep_the_order_of_the_setting(): void {
        set_config('levels', "Oberstufe\nKlasse 5-6\nKlasse 10", 'block_kursfilter');

        $this->assertSame(['Oberstufe', 'Klasse 5-6', 'Klasse 10'], filter_values::values('level'));
    }

    public function test_match_ignores_case_and_surrounding_spaces_and_returns_the_original_spelling(): void {
        set_config('schooltypes', "Gymnasium\nGrundschule", 'block_kursfilter');

        $this->assertSame('Gymnasium', filter_values::match('schooltype', '  gymnasium '));
        $this->assertSame('Grundschule', filter_values::match('schooltype', 'GRUNDSCHULE'));
    }

    public function test_match_of_an_unknown_value_is_null(): void {
        set_config('levels', "Klasse 5-6\nOberstufe", 'block_kursfilter');

        $this->assertNull(filter_values::match('level', 'Klasse 99'));
        $this->assertNull(filter_values::match('level', '   '));
    }

    public function test_match_only_looks_at_the_list_of_its_kind(): void {
        set_config('subjects', 'Mathematik', 'block_kursfilter');
        set_config('levels', 'Oberstufe', 'block_kursfilter');

        $this->assertNull(filter_values::match('level', 'Mathematik'));
    }
}
