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
 * Picks a free pool user, logs them in and redirects
 * the visitor straight to the requested course.
 * No require_login(): the endpoint is public.
 *
 * Usage: /blocks/kursfilter/guest_login.php?courseid=42
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignore moodle.Files.RequireLogin.Missing -- Public endpoint: it is the login step itself, via a pool account.
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/kursfilter/classes/pool_manager.php');

$courseid = required_param('courseid', PARAM_INT);

// The course must exist and be visible.
$course = \block_kursfilter\course_access::get_public_course($courseid);
if (!$course) {
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

// If the user is already logged in (real user or pool user),
// redirect straight to the course.
if (isloggedin() && !isguestuser()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

// Find a free pool user.
$pooluser = \block_kursfilter\pool_manager::get_free_pool_user();

if (!$pooluser) {
    // All pool users are taken: show a notice.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url(new moodle_url('/blocks/kursfilter/guest_login.php', ['courseid' => $courseid]));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('pool_full', 'block_kursfilter'),
        \core\output\notification::NOTIFY_WARNING
    );
    // Link back to the search.
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

// Make sure the pool user is enrolled in the course.
\block_kursfilter\pool_manager::enrol_pool_into_course($courseid);

// Mark the pool user as active (TTL: 2 hours).
\block_kursfilter\pool_manager::mark_active($pooluser->username, 7200);

// Start the Moodle session of the pool user.
// complete_user_login() sets all required session variables.
$pooluser = get_complete_user_data('id', $pooluser->id);
complete_user_login($pooluser);

// Course URL with notice parameter.
$courseurl = new moodle_url('/course/view.php', [
    'id'         => $courseid,
    'kf_preview' => 1,
]);

redirect($courseurl);
