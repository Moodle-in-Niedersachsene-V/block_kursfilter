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
        $this->assertSame('2026102501', get_config('block_kursfilter', 'version'));
    }

    public function test_upgrade_from_main_1_4_0_moves_pool_accounts_from_teacher_to_pool_role(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/blocks/kursfilter/db/upgrade.php');
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacherid = (int)$DB->get_field('role', 'id', ['shortname' => 'teacher']);
        pool_manager::create_pool_accounts();
        pool_manager::remove_role();
        $poolids = array_keys($DB->get_records_list('user', 'username', pool_manager::get_pool_usernames(), '', 'id'));
        foreach ($poolids as $userid) {
            role_assign($teacherid, $userid, $context->id);
        }
        set_config('version', 2026100811, 'block_kursfilter');

        xmldb_block_kursfilter_upgrade(2026100811);

        $poolroleid = (int)$DB->get_field('role', 'id', ['shortname' => pool_manager::ROLE_SHORTNAME]);
        foreach ($poolids as $userid) {
            $this->assertFalse(user_has_role_assignment($userid, $teacherid, $context->id));
            $this->assertTrue(user_has_role_assignment($userid, $poolroleid, $context->id));
        }
    }

    public function test_upgrade_from_main_2026101006_still_migrates_pool_role_and_drops_ai_backend(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/blocks/kursfilter/db/upgrade.php');
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacherid = (int)$DB->get_field('role', 'id', ['shortname' => 'teacher']);
        pool_manager::create_pool_accounts();
        pool_manager::remove_role();
        $userid = (int)$DB->get_field('user', 'id', ['username' => pool_manager::get_pool_usernames()[0]]);
        role_assign($teacherid, $userid, $context->id);
        set_config('ai_claude_apikey', 'secret-key', 'block_kursfilter');
        set_config('version', 2026101006, 'block_kursfilter');

        xmldb_block_kursfilter_upgrade(2026101006);

        $poolroleid = (int)$DB->get_field('role', 'id', ['shortname' => pool_manager::ROLE_SHORTNAME]);
        $this->assertTrue(user_has_role_assignment($userid, $poolroleid, $context->id));
        $this->assertFalse(user_has_role_assignment($userid, $teacherid, $context->id));
        $this->assertFalse(get_config('block_kursfilter', 'ai_claude_apikey'));
    }

    public function test_upgrade_from_2026101500_renames_pool_accounts_and_tag_suggestion_table_without_data_loss(): void {
        global $CFG, $DB;
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/blocks/kursfilter/db/upgrade.php');
        $this->preventResetByRollback();
        $this->resetAfterTest();
        pool_manager::create_pool_accounts();
        $userid = (int)$DB->get_field('user', 'id', ['username' => pool_manager::get_pool_usernames()[0]]);
        $DB->set_field('user', 'username', 'kursfilter_guest01', ['id' => $userid]);
        $DB->set_field('user', 'email', 'kursfilter_guest01@kursfilter.invalid', ['id' => $userid]);
        $dbman = $DB->get_manager();
        $dbman->rename_table(new \xmldb_table('block_kursfilter_tag_suggest'), 'block_kursfilter_tag_pending');
        $old = new \xmldb_table('block_kursfilter_tag_pending');
        $index = new \xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $dbman->drop_index($old, $index);
        $dbman->change_field_default($old, new \xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending'));
        $dbman->add_index($old, $index);
        $DB->insert_record('block_kursfilter_tag_pending', [
            'courseid' => 7, 'tags' => 'Deutsch', 'status' => 'pending', 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $DB->insert_record('block_kursfilter_tag_pending', [
            'courseid' => 8, 'tags' => 'Mathematik', 'status' => 'applied', 'timecreated' => 1, 'timemodified' => 1,
        ]);
        set_config('version', 2026101500, 'block_kursfilter');

        xmldb_block_kursfilter_upgrade(2026101500);

        $user = $DB->get_record('user', ['id' => $userid], 'username, email', MUST_EXIST);
        $this->assertSame('kursfilter_pool01', $user->username);
        $this->assertSame('kursfilter_pool01@kursfilter.invalid', $user->email);
        $this->assertFalse($DB->record_exists('user', ['username' => 'kursfilter_guest01']));
        $this->assertFalse($dbman->table_exists($old));
        $this->assertSame(
            ['7' => 'suggested', '8' => 'applied'],
            $DB->get_records_menu('block_kursfilter_tag_suggest', null, 'courseid', 'courseid, status')
        );
        $this->assertSame('Deutsch', $DB->get_field('block_kursfilter_tag_suggest', 'tags', ['courseid' => 7]));
        $id = $DB->insert_record('block_kursfilter_tag_suggest', [
            'courseid' => 9, 'tags' => '', 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $this->assertSame('suggested', $DB->get_field('block_kursfilter_tag_suggest', 'status', ['id' => $id]));
    }
}
