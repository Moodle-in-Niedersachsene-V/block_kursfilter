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
 * Accepts a star rating, sets the cookie
 * and stores the rating in the database.
 * Responds with JSON.
 *
 * Usage: POST /blocks/kursfilter/rate.php
 *        Body: courseid=42&stars=4&sesskey=...
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
// phpcs:ignore moodle.Files.RequireLogin.Missing,moodle.Commenting.InlineComment.NotCapital,moodle.Commenting.InlineComment.InvalidEndChar -- Public endpoint: visitors without an account may rate (sesskey-protected). The Semgrep marker has a fixed syntax.
require_once(__DIR__ . '/../../config.php'); // nosemgrep: moodle-einstiegsdatei-ohne-login
require_once($CFG->dirroot . '/blocks/kursfilter/classes/rating_helper.php');

header('Content-Type: application/json; charset=utf-8');

// Only POST allowed.
if (!data_submitted()) {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Validate sesskey to prevent CSRF from external sites.
// Visitors without an account get a sesskey from their Moodle session as well.
if (!confirm_sesskey()) {
    echo json_encode(['success' => false, 'error' => 'Invalid sesskey']);
    exit;
}

$courseid = required_param('courseid', PARAM_INT);
$stars = required_param('stars', PARAM_INT);

$result = \block_kursfilter\rating_helper::submit(
    $courseid,
    $stars,
    fn() => \block_kursfilter\rating_helper::get_cookie_hash()
        ?? \block_kursfilter\rating_helper::create_cookie_hash()
);

echo json_encode($result);
exit;
