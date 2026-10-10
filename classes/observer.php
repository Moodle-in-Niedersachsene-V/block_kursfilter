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
 * Event observer for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter;

/**
 * Handles Moodle events relevant to block_kursfilter.
 */
class observer {
    /**
     * Frees a pool user account when it logs out.
     *
     * Called on \core\event\user_loggedout. If the logging-out user is one of
     * the kursfilter pool accounts, it is marked as free so another visitor
     * can reuse it.
     *
     * @param \core\event\user_loggedout $event The logout event.
     */
    public static function user_loggedout(\core\event\user_loggedout $event): void {
        $userid = (int)$event->objectid;
        // Only act on pool users (username prefix check).
        $user = \core_user::get_user($userid, 'id, username', IGNORE_MISSING);
        if (!$user) {
            return;
        }
        if (strpos($user->username, pool_manager::USERNAME_PREFIX) !== 0) {
            return;
        }
        pool_manager::mark_free($userid);
    }
}
