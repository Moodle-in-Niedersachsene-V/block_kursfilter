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
 * Erlaubt auch nicht angemeldeten Nutzern (Gäste) den Download
 * einer vorgefertigten Kurssicherung (.mbz).
 * Die Datei wird täglich durch den Scheduled Task erzeugt.
 *
 * Aufruf: /blocks/kursfilter/backup.php?courseid=42
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No require_login() call: this endpoint intentionally serves public backup downloads.
// Access is controlled by course visibility (visible = 1 check below).
require_once(__DIR__ . '/../../config.php'); // @codingStandardsIgnoreLine
require_once($CFG->dirroot . '/blocks/kursfilter/classes/backup_helper.php');

// Parameter einlesen und validieren.
$courseid = required_param('courseid', PARAM_INT);

// Kurs muss sichtbar und vorhanden sein.
$course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], '*', IGNORE_MISSING);
if (!$course || $course->id === SITEID) {
    send_file_not_found();
}

// Backup-Datei aus dem Moodle-Dateibereich laden.
$backupfile = \block_kursfilter\backup_helper::get_backup_file($courseid);
if (!$backupfile) {
    // Noch keine Sicherung vorhanden – Hinweis ausgeben.
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

// Datei als Download ausliefern.
// send_stored_file() setzt alle nötigen Header und streamt die Datei.
// forcedownload = true, damit Browser die Datei speichert statt öffnet.
send_stored_file(
    $backupfile,
    0, // Lifetime: kein Browser-Caching.
    0, // Filter: keine Nachbearbeitung.
    true, // Force download.
    [
        'filename'  => clean_filename($course->shortname . '_backup.mbz'),
        'dontdie'   => false,
    ]
);
