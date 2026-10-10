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
 * Preview endpoint for block_kursfilter.
 *
 * Hands a free pool account to the visitor, logs it in and redirects to the course.
 * Only a POST with a valid sesskey starts a preview; visitors without an account
 * get a sesskey from their Moodle session as well.
 *
 * Usage: POST /blocks/kursfilter/preview.php, body courseid=42&sesskey=...
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public endpoint: it is the login step itself, via a pool account.
require_once(__DIR__ . '/../../config.php'); // nosemgrep: moodle-einstiegsdatei-ohne-login

$courseid = required_param('courseid', PARAM_INT);
if (!data_submitted()) {
    throw new moodle_exception('error_preview_needs_post', 'block_kursfilter');
}
require_sesskey();

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/blocks/kursfilter/preview.php'));

if (!\block_kursfilter\course_access::get_public_course($courseid)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('course_not_found', 'block_kursfilter'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

// A real login is never replaced by a pool account.
if (isloggedin() && !isguestuser()) {
    redirect($courseurl);
}

$account = \block_kursfilter\pool_manager::occupy_free_account($courseid);
if (!$account) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('pool_full', 'block_kursfilter'), \core\output\notification::NOTIFY_WARNING);
    echo html_writer::div(html_writer::link(
        new moodle_url('/'),
        get_string('back_to_search', 'block_kursfilter'),
        ['class' => 'btn btn-secondary mt-3']
    ));
    echo $OUTPUT->footer();
    exit;
}

complete_user_login(get_complete_user_data('id', $account->id));
redirect($courseurl);
