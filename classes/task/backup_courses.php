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
 * Scheduled task: back up all visible courses nightly.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter\task;

/**
 * Nightly backup task for block_kursfilter.
 *
 * Runs at 02:00 by default (configured in db/tasks.php).
 * Creates one mbz backup per visible course via the Moodle backup API.
 * Existing backups are replaced (one file per course, no accumulation).
 */
class backup_courses extends \core\task\scheduled_task {
    /**
     * Return the task name shown in admin interface.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_backup_courses', 'block_kursfilter');
    }

    /**
     * Back up every public course; a failed course does not stop the others, but fails the task.
     *
     * @throws \moodle_exception If at least one backup failed.
     */
    public function execute(): void {
        global $DB;

        $userid = (int)get_config('block_kursfilter', 'backup_adminid');
        if ($userid < 1) {
            // Documented in the setting: an empty value means the first site admin.
            $userid = (int)get_admin()->id;
        }

        $courses = $DB->get_records_select(
            'course',
            'visible = 1 AND id != :siteid',
            ['siteid' => SITEID],
            'fullname ASC',
            'id, fullname'
        );

        $failed = 0;
        foreach ($courses as $course) {
            try {
                $file = \block_kursfilter\backup_helper::backup_course((int)$course->id, $userid);
                $size = display_size($file->get_filesize());
                mtrace("Course {$course->id}: backup stored ({$file->get_filename()}, {$size})");
            } catch (\Throwable $e) {
                mtrace("Course {$course->id}: backup failed: " . $e->getMessage());
                $failed++;
            }
        }

        if ($failed > 0) {
            throw new \moodle_exception('error_backups_failed', 'block_kursfilter', '', (object)[
                'failed' => $failed,
                'total' => count($courses),
            ]);
        }
    }
}
