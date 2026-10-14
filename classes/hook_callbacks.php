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
 * Hook callbacks of block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /** Scripts a pool account may not open: Moodle always allows users their own profile, no capability prevents it. */
    const PROTECTED_SCRIPTS = [
        '/user/profile.php',
        '/user/view.php',
        '/user/edit.php',
        '/user/editadvanced.php',
        '/user/preferences.php',
        '/login/change_password.php',
    ];

    /**
     * Send pool accounts away from profile and account pages.
     *
     * @param \core\hook\output\before_http_headers $hook Hook.
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        global $USER, $SCRIPT;

        if (!isloggedin() || !in_array($SCRIPT, self::PROTECTED_SCRIPTS, true)) {
            return;
        }
        if (pool_manager::is_pool_account($USER->username)) {
            redirect(new \moodle_url('/'));
        }
    }
}
