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
 * Loescht alle Pool-Nutzer (kursfilter_guest01 … kursfilter_guest10)
 * sauber aus der Moodle-Nutzerverwaltung, damit nach einer
 * Deinstallation kein Datenmüll zurueckbleibt.
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
    global $DB;

    require_once($CFG->dirroot . '/user/lib.php');

    $usernames = \block_kursfilter\pool_manager::get_pool_usernames();

    foreach ($usernames as $username) {
        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0], '*', IGNORE_MISSING);
        if (!$user) {
            continue;
        }
        // Delete_user() setzt deleted = 1, entfernt Einschreibungen und bereinigt Nutzerdaten.
        delete_user($user);
    }
}
