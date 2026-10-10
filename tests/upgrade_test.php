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

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for the upgrade steps.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('xmldb_block_kursfilter_upgrade')]
final class upgrade_test extends \advanced_testcase {
    public function test_upgrade_from_1_4_1_moves_settings_to_their_english_names_and_drops_the_own_ai_backend(): void {
        global $CFG;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/blocks/kursfilter/db/upgrade.php');
        $this->resetAfterTest();
        set_config('version', 2026100601, 'block_kursfilter');
        set_config('schulformen', "Gymnasium\nOberschule", 'block_kursfilter');
        set_config('faecher', 'Mathematik', 'block_kursfilter');
        set_config('niveaustufen', 'Klasse 5-6', 'block_kursfilter');
        set_config('backup_adminid', '2', 'block_kursfilter');
        set_config('ai_claude_apikey', 'secret-key', 'block_kursfilter');
        unset_config('levels', 'block_kursfilter');

        xmldb_block_kursfilter_upgrade(2026100601);

        $this->assertSame("Gymnasium\nOberschule", get_config('block_kursfilter', 'schooltypes'));
        $this->assertSame('Mathematik', get_config('block_kursfilter', 'subjects'));
        $this->assertSame('Klasse 5-6', get_config('block_kursfilter', 'levels'));
        $this->assertSame('2', get_config('block_kursfilter', 'backup_userid'));
        foreach (['schulformen', 'faecher', 'niveaustufen', 'backup_adminid', 'ai_claude_apikey'] as $old) {
            $this->assertFalse(get_config('block_kursfilter', $old), $old);
        }
        $this->assertSame('2026101000', get_config('block_kursfilter', 'version'));
    }
}
