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
 * Backup helper for block_kursfilter.
 *
 * Erzeugt eine Moodle-Kurssicherung (.mbz) und speichert sie
 * im Moodle-Dateibereich. Immer nur eine Datei pro Kurs vorhanden.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Handles course backup creation and file storage for block_kursfilter.
 */
class backup_helper {
    /** File area name used in the Moodle file API. */
    const FILEAREA = 'course_backups';

    /** Component name. */
    const COMPONENT = 'block_kursfilter';

    /**
     * Create a backup for the given course and store it in the Moodle file area.
     * Any existing backup for this course is deleted first (one file per course).
     *
     * @param int $courseid Course ID to back up.
     * @param int $adminid  User ID to run the backup as (must have backup capability).
     * @return stored_file|null The stored backup file, or null on failure.
     */
    public static function backup_course(int $courseid, int $adminid): ?\stored_file {
        global $CFG;

        // Validate course exists.
        $course = get_course($courseid);
        if (!$course) {
            return null;
        }

        // Context for file storage: system context, itemid = courseid.
        $context  = \context_system::instance();
        $itemid   = $courseid;
        $filename = 'backup_course_' . $courseid . '_' . date('Ymd') . '.mbz';

        // Delete existing backup for this course (one file per course rule).
        self::delete_existing_backup($context, $itemid);

        // Create backup in a temp directory.
        $tempdir = make_temp_directory('backup_kursfilter_' . $courseid);

        try {
            $bc = new \backup_controller(
                \backup::TYPE_1COURSE,
                $courseid,
                \backup::FORMAT_MOODLE,
                \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL,
                $adminid
            );

            // Disable user data and logs for smaller, faster, privacy-safe backups.
            $bc->get_plan()->get_setting('users')->set_value(0);
            $bc->get_plan()->get_setting('role_assignments')->set_value(0);
            $bc->get_plan()->get_setting('logs')->set_value(0);
            $bc->get_plan()->get_setting('grade_histories')->set_value(0);

            $bc->execute_plan();

            $results = $bc->get_results();
            $backupfile = $results['backup_destination'];
            $bc->destroy();

            if (!$backupfile) {
                return null;
            }

            // Store the backup file in the Moodle file area.
            $fs      = get_file_storage();
            $fileinfo = [
                'contextid' => $context->id,
                'component' => self::COMPONENT,
                'filearea'  => self::FILEAREA,
                'itemid'    => $itemid,
                'filepath'  => '/',
                'filename'  => $filename,
            ];

            // Store from the backup temp file.
            $storedfile = $fs->create_file_from_storedfile($fileinfo, $backupfile);

            // Clean up the temp backup.
            $backupfile->delete();

            return $storedfile;
        } catch (\Exception $e) {
            debugging('block_kursfilter backup_helper: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }
    }

    /**
     * Delete any existing backup file for a given course.
     *
     * @param \context $context System context.
     * @param int      $itemid  Course ID used as itemid.
     */
    public static function delete_existing_backup(\context $context, int $itemid): void {
        $fs    = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $itemid,
            'timemodified DESC',
            false
        );
        foreach ($files as $file) {
            $file->delete();
        }
    }

    /**
     * Get the stored backup file for a course, if it exists.
     *
     * @param int $courseid Course ID.
     * @return \stored_file|null
     */
    public static function get_backup_file(int $courseid): ?\stored_file {
        $context = \context_system::instance();
        $fs      = get_file_storage();
        $files   = $fs->get_area_files(
            $context->id,
            self::COMPONENT,
            self::FILEAREA,
            $courseid,
            'timemodified DESC',
            false
        );
        return !empty($files) ? reset($files) : null;
    }

    /**
     * Check whether a backup file exists for the given course.
     *
     * @param int $courseid Course ID.
     * @return bool
     */
    public static function has_backup(int $courseid): bool {
        return self::get_backup_file($courseid) !== null;
    }
}
