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
 * Tests fuer die oeffentlich verteilten Kurssicherungen (Dateiverwaltung, Archivinhalt).
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \block_kursfilter\backup_helper
 */
final class backup_helper_test extends \advanced_testcase {
    /** @var string Eindeutiger Teil der Schueler-Mailadresse. */
    private const STUDENT_EMAIL = 'privat.kind@schule-geheim.example';

    /**
     * Entpackt eine Sicherung und liefert den Inhalt aller Dateien als ein String.
     *
     * @param \stored_file $file Sicherungsdatei.
     * @return string Verketteter Dateiinhalt.
     */
    private function extracted_content(\stored_file $file): string {
        global $CFG;
        $dir = make_request_directory();
        $packer = get_file_packer('application/vnd.moodle.backup');
        $this->assertNotFalse($file->extract_to_pathname($packer, $dir));
        $content = '';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $path) {
            if ($path->isFile() && $path->getExtension() === 'xml') {
                $content .= file_get_contents($path->getPathname());
            }
        }
        return $content;
    }

    public function test_backup_contains_course_but_no_participant_data(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['fullname' => 'Materialkurs Demo']);
        $student = $generator->create_user([
            'username' => 'schueler.geheim', 'email' => self::STUDENT_EMAIL,
            'firstname' => 'Geheimvorname', 'lastname' => 'Geheimnachname',
        ]);
        $generator->enrol_user($student->id, $course->id, 'student');
        $forum = $generator->create_module('forum', ['course' => $course->id, 'name' => 'Austausch']);
        $generator->get_plugin_generator('mod_forum')->create_discussion([
            'course' => $course->id, 'forum' => $forum->id, 'userid' => $student->id,
            'name' => 'Beitrag des Kindes', 'message' => 'Text des Kindes',
        ]);

        $file = backup_helper::backup_course($course->id, get_admin()->id);

        $this->assertNotNull($file);
        $content = $this->extracted_content($file);
        $this->assertStringContainsString('Materialkurs Demo', $content);
        $this->assertStringContainsString('Austausch', $content);
        foreach ([self::STUDENT_EMAIL, get_admin()->email, 'schueler.geheim', 'Geheimvorname', 'Geheimnachname', 'Text des Kindes'] as $secret) {
            $this->assertStringNotContainsString($secret, $content, "Archiv enthaelt '$secret'");
        }
    }

    public function test_backup_keeps_one_file_per_course_and_replaces_older_one(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();

        $first = backup_helper::backup_course($course->id, get_admin()->id);
        backup_helper::backup_course($other->id, get_admin()->id);
        $second = backup_helper::backup_course($course->id, get_admin()->id);

        $this->assertNotNull($first);
        $this->assertEquals($second->get_id(), backup_helper::get_backup_file($course->id)->get_id());
        $this->assertTrue(backup_helper::has_backup($other->id));
        $this->assertCount(1, get_file_storage()->get_area_files(
            \context_system::instance()->id, 'block_kursfilter', 'course_backups', $course->id, 'id', false
        ));
    }

    public function test_has_backup_is_false_for_course_without_backup(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse(backup_helper::has_backup($course->id));
        $this->assertNull(backup_helper::get_backup_file($course->id));
    }
}
