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

/**
 * Tests for the default values declared in settings.php.
 *
 * Covers nothing: settings.php is a script, not a class or function coverage could point at.
 *
 * @package    block_kursfilter
 * @category   test
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class settings_test extends \advanced_testcase {
    /**
     * Loads settings.php and returns the default of one setting.
     *
     * @param string $name setting name without component prefix
     * @return string
     */
    private function default_of(string $name): string {
        global $CFG, $ADMIN;
        require_once($CFG->libdir . '/adminlib.php');
        $this->resetAfterTest();
        $ADMIN = admin_get_root(true, true);
        $settings = new \admin_settingpage('block_kursfilter_test', 'test');
        require($CFG->dirroot . '/blocks/kursfilter/settings.php');
        foreach ($settings->settings as $setting) {
            if ($setting->name === $name) {
                return $setting->get_defaultsetting();
            }
        }
        $this->fail("Setting {$name} not found");
    }

    public function test_default_schooltypes_cover_all_school_types_of_lower_saxony(): void {
        $types = explode("\n", $this->default_of('schooltypes'));

        $this->assertCount(19, $types);
        $this->assertContains('Oberschule', $types);
        $this->assertContains('IGS', $types);
        $this->assertContains('Fachschule', $types);
    }

    public function test_default_subjects_contain_the_school_subjects(): void {
        $subjects = explode("\n", $this->default_of('subjects'));

        $this->assertCount(24, $subjects);
        $this->assertContains('Informatik', $subjects);
        $this->assertContains('Politik-Wirtschaft', $subjects);
    }
}
