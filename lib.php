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
 * Library functions for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Called by Moodle after config.php is loaded (every request).
 *
 * For pool users this callback:
 *  - disables the messaging icon/system ($CFG->messaging = false)
 *  - redirects any attempt to reach profile, preferences, grades,
 *    calendar, messaging, dashboard or "my" pages to the site home,
 *    so those menu entries effectively do nothing.
 *
 * The redirect uses a plain header() call because at this stage the
 * Moodle output buffer is not yet started and redirect() is not yet
 * available.  We only redirect when the path is not already the home
 * page to avoid an infinite loop.
 */
function block_kursfilter_after_config(): void {
    global $CFG, $USER;

    // Only act when a real pool user is logged in.
    if (!isloggedin() || isguestuser()) {
        return;
    }
    if (!\block_kursfilter\pool_manager::is_pool_user()) {
        return;
    }

    // 1. Kill the messaging icon and system entirely for this session.
    $CFG->messaging = false;

    // 2. Redirect blocked paths to site home.
    //    SCRIPT_NAME holds the PHP file path relative to the web root,
    //    e.g. "/user/profile.php" or "/message/index.php".
    $blockedprefixes = [
        '/user/profile.php',
        '/user/edit.php',
        '/user/preferences.php',
        '/user/managefiles.php',
        '/message/',
        '/calendar/',
        '/grade/report/overview/',
        '/grade/report/user/',
        '/badges/',
        '/blog/',
        '/my/',
        '/user/files.php',
    ];

    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    // Normalise: strip any subdirectory prefix Moodle may be installed in.
    $wwwroot = rtrim(parse_url($CFG->wwwroot, PHP_URL_PATH) ?? '', '/');
    if ($wwwroot !== '' && strpos($script, $wwwroot) === 0) {
        $script = substr($script, strlen($wwwroot));
    }

    foreach ($blockedprefixes as $prefix) {
        if (strpos($script, $prefix) === 0) {
            // Redirect to site home without creating a Moodle session loop.
            header('Location: ' . $CFG->wwwroot . '/');
            exit;
        }
    }
}

/**
 * Serve backup files from the kursfilter file area.
 * Delegiert an backup.php – diese Funktion wird fuer den
 * Moodle pluginfile()-Mechanismus nicht benoetigt, da der
 * Download ueber backup.php direkt erfolgt.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context  $context
 * @param string   $filearea
 * @param array    $args
 * @param bool     $forcedownload
 * @param array    $options
 */
function block_kursfilter_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
): void {
    // Downloads laufen ueber blocks/kursfilter/backup.php.
    send_file_not_found();
}
