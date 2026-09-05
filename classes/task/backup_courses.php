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
     * Execute the task.
     */
    public function execute(): void {
        global $DB, $CFG;

        // Find an admin user to run backups as.
        $adminid = (int)get_config('block_kursfilter', 'backup_adminid');
        if ($adminid < 1) {
            // Fall back to the first site admin.
            $admins  = get_admins();
            $admin   = reset($admins);
            $adminid = (int)$admin->id;
        }

        // Fetch all visible non-site courses.
        $courses = $DB->get_records_select(
            'course',
            'visible = 1 AND id != :siteid',
            ['siteid' => SITEID],
            'fullname ASC',
            'id, fullname'
        );

        $success = 0;
        $failed  = 0;

        foreach ($courses as $course) {
            mtrace("  Sichere Kurs: [{$course->id}] {$course->fullname}");
            $file = \block_kursfilter\backup_helper::backup_course((int)$course->id, $adminid);
            if ($file) {
                mtrace("    → OK ({$file->get_filename()}, " . display_size($file->get_filesize()) . ")");
                $success++;
            } else {
                mtrace("    → FEHLER");
                $failed++;
            }
        }

        mtrace("Kursfilter-Backup abgeschlossen: {$success} erfolgreich, {$failed} fehlgeschlagen.");
    }
}
