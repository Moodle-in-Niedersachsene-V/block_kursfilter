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
 * Releases pool accounts when their session ends.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Free the pool account of a user who logged out; other users are ignored.
     *
     * @param \core\event\user_loggedout $event Logout event.
     */
    public static function user_loggedout(\core\event\user_loggedout $event): void {
        global $DB;

        $username = $DB->get_field('user', 'username', ['id' => $event->userid]);
        if ($username !== false && in_array($username, pool_manager::get_pool_usernames(), true)) {
            pool_manager::mark_free($username);
        }
    }
}
