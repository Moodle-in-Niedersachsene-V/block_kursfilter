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
 * Uninstall hook for block_kursfilter.
 *
 * Deletes all pool accounts and the pool role, so that no leftover accounts remain.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Pre-uninstall tasks for block_kursfilter.
 */
function xmldb_block_kursfilter_uninstall(): void {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/user/lib.php');

    // All pool accounts up to the maximum: the pool size may have been reduced since they were created.
    $usernames = \block_kursfilter\pool_manager::get_pool_usernames(\block_kursfilter\pool_manager::POOL_SIZE_MAX);
    foreach ($DB->get_records_list('user', 'username', $usernames) as $user) {
        if (!$user->deleted) {
            delete_user($user);
        }
    }

    \block_kursfilter\pool_manager::remove_role();
}
