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
 * Creates a Moodle course backup (.mbz) and stores it in the
 * Moodle file area. Only one file per course exists at any time.
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
     * The previous backup of the course is replaced only after the new one exists (one file per course).
     *
     * @param int $courseid Course ID to back up.
     * @param int $userid   User the backup runs as (needs the backup capability).
     * @return \stored_file The stored backup file.
     * @throws \moodle_exception If the course is missing or the backup fails.
     */
    public static function backup_course(int $courseid, int $userid): \stored_file {
        get_course($courseid);

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $courseid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $userid
        );
        try {
            // No user data and no logs: the backup is public.
            $bc->get_plan()->get_setting('users')->set_value(0);
            $bc->get_plan()->get_setting('role_assignments')->set_value(0);
            $bc->get_plan()->get_setting('logs')->set_value(0);
            $bc->get_plan()->get_setting('grade_histories')->set_value(0);
            $bc->execute_plan();
            $backupfile = $bc->get_results()['backup_destination'] ?? null;
        } finally {
            $bc->destroy();
        }
        if (!$backupfile) {
            throw new \moodle_exception('error_backup_not_created', 'block_kursfilter', '', $courseid);
        }

        $context = \context_system::instance();
        self::delete_existing_backup($context, $courseid);
        $storedfile = get_file_storage()->create_file_from_storedfile([
            'contextid' => $context->id,
            'component' => self::COMPONENT,
            'filearea'  => self::FILEAREA,
            'itemid'    => $courseid,
            'filepath'  => '/',
            'filename'  => 'backup_course_' . $courseid . '_' . date('Ymd') . '.mbz',
        ], $backupfile);
        $backupfile->delete();

        return $storedfile;
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
