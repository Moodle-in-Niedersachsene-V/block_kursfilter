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
 * Public rating endpoint for block_kursfilter.
 *
 * Nimmt eine Sternebewertung entgegen, setzt den Cookie
 * und speichert die Bewertung in der Datenbank.
 * Antwortet mit JSON.
 *
 * Aufruf: POST /blocks/kursfilter/rate.php
 *         Body: courseid=42&stars=4&sesskey=...
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// No require_login() – ratings are open to all visitors including guests.
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php'); // @codingStandardsIgnoreLine
require_once($CFG->dirroot . '/blocks/kursfilter/classes/rating_helper.php');

header('Content-Type: application/json; charset=utf-8');

// Only POST allowed.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate sesskey to prevent CSRF from external sites.
// Guests without a session get a temporary sesskey from Moodle.
if (!confirm_sesskey()) {
    echo json_encode(['success' => false, 'error' => 'Invalid sesskey']);
    exit;
}

$courseid = required_param('courseid', PARAM_INT);
$stars    = required_param('stars', PARAM_INT);

// Validate stars range.
if ($stars < 1 || $stars > 5) {
    echo json_encode(['success' => false, 'error' => 'Invalid rating']);
    exit;
}

// Course must exist and be visible.
$course = $DB->get_record('course', ['id' => $courseid, 'visible' => 1], 'id', IGNORE_MISSING);
if (!$course || $course->id === SITEID) {
    echo json_encode(['success' => false, 'error' => 'Course not found']);
    exit;
}

// Get or create visitor cookie hash.
$cookiehash = \block_kursfilter\rating_helper::get_or_create_cookie_hash();

// Save rating (returns false if already rated).
$saved = \block_kursfilter\rating_helper::save_rating($courseid, $cookiehash, $stars);

echo json_encode([
    'success'      => true,
    'saved'        => $saved,
    'already_rated' => !$saved,
]);
exit;
