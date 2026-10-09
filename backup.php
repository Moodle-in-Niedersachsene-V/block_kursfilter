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
 * Public backup download endpoint for block_kursfilter.
 *
 * Allows visitors without an account to download
 * a prebuilt course backup (.mbz).
 * The file is generated daily by the scheduled task.
 *
 * Usage: /blocks/kursfilter/backup.php?courseid=42
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public endpoint: backups of public courses are downloadable without an account; access is limited to public courses below.
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/kursfilter/classes/backup_helper.php');

// Read and validate the parameters.
$courseid = required_param('courseid', PARAM_INT);

// The course must exist and be visible.
$course = \block_kursfilter\course_access::get_public_course($courseid);
if (!$course) {
    send_file_not_found();
}

// Load the backup file from the Moodle file area.
$backupfile = \block_kursfilter\backup_helper::get_backup_file($courseid);
if (!$backupfile) {
    // No backup available yet: show a notice.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/blocks/kursfilter/backup.php', ['courseid' => $courseid]));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('backup_not_available', 'block_kursfilter'),
        \core\output\notification::NOTIFY_WARNING
    );
    echo $OUTPUT->footer();
    exit;
}

// Serve the file as a download.
// send_stored_file() sends all required headers and streams the file.
// forcedownload = true so that browsers save the file instead of opening it.
send_stored_file(
    $backupfile,
    0, // Lifetime: no browser caching.
    0, // Filter: no post-processing.
    true, // Force download.
    [
        'filename'  => clean_filename($course->shortname . '_backup.mbz'),
        'dontdie'   => false,
    ]
);
