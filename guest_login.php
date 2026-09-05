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
 * Guest login endpoint for block_kursfilter.
 *
 * Waehlt einen freien Pool-Nutzer, loggt ihn ein und leitet
 * den Besucher direkt zum angefragten Kurs weiter.
 * Kein require_login() – der Endpunkt ist oeffentlich.
 *
 * Aufruf: /blocks/kursfilter/guest_login.php?courseid=42
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No require_login() call: this endpoint intentionally allows guest access via pool users.
// Authentication is handled below by complete_user_login().
require_once(__DIR__ . '/../../config.php'); // @codingStandardsIgnoreLine
require_once($CFG->dirroot . '/blocks/kursfilter/classes/pool_manager.php');

$courseid = required_param('courseid', PARAM_INT);

// Kurs muss sichtbar und vorhanden sein.
$course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], '*', IGNORE_MISSING);
if (!$course || $course->id === SITEID) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/blocks/kursfilter/guest_login.php'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('course_not_found', 'block_kursfilter'),
        \core\output\notification::NOTIFY_ERROR
    );
    echo $OUTPUT->footer();
    exit;
}

// Wenn der Nutzer bereits angemeldet ist (echter Nutzer oder Pool-Nutzer),
// direkt zum Kurs weiterleiten.
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Freien Pool-Nutzer suchen.
$pooluser = \block_kursfilter\pool_manager::get_free_pool_user();

if (!$pooluser) {
    // Alle Pool-Nutzer belegt – Hinweis anzeigen.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/blocks/kursfilter/guest_login.php', ['courseid' => $courseid]));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('pool_full', 'block_kursfilter'),
        \core\output\notification::NOTIFY_WARNING
    );
    // Link zurueck zur Suche.
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/'),
            get_string('back_to_search', 'block_kursfilter'),
            ['class' => 'btn btn-secondary mt-3']
        )
    );
    echo $OUTPUT->footer();
    exit;
}

// Pool-Nutzer sicherstellen: muss im Kurs eingeschrieben sein.
\block_kursfilter\pool_manager::enrol_pool_into_course($courseid);

// Pool-Nutzer als aktiv markieren (TTL: 2 Stunden).
\block_kursfilter\pool_manager::mark_active($pooluser->username, 7200);

// Moodle-Session des Pool-Nutzers starten.
// complete_user_login() setzt alle noetigen Session-Variablen.
$pooluser = get_complete_user_data('id', $pooluser->id);
complete_user_login($pooluser);

// Kurs-URL mit Hinweis-Parameter.
$courseurl = new moodle_url('/course/view.php', [
    'id'         => $courseid,
    'kf_preview' => 1,
]);

redirect($courseurl);
