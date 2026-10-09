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
 * Tests for the uninstall hook.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('xmldb_block_kursfilter_uninstall')]
final class uninstall_test extends \advanced_testcase {
    public function test_uninstall_deletes_pool_accounts_beyond_a_reduced_pool_size_and_the_pool_role(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/blocks/kursfilter/db/uninstall.php');
        $this->resetAfterTest();
        set_config('poolsize', 3, 'block_kursfilter');
        pool_manager::create_pool_accounts();
        pool_manager::ensure_role();
        $realuser = $this->getDataGenerator()->create_user();
        set_config('poolsize', 1, 'block_kursfilter');

        xmldb_block_kursfilter_uninstall();

        [$insql, $params] = $DB->get_in_or_equal(pool_manager::get_pool_usernames(3));
        $this->assertSame(0, $DB->count_records_select('user', "username $insql AND deleted = 0", $params));
        $this->assertFalse($DB->record_exists('role', ['shortname' => pool_manager::ROLE_SHORTNAME]));
        $this->assertFalse((bool)$DB->get_field('user', 'deleted', ['id' => $realuser->id]));
    }
}
