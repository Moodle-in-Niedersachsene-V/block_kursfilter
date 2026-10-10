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

namespace block_kursfilter\task;

use block_kursfilter\tag_suggester;

/**
 * Scheduled task: AI tagging of public courses that have no tag suggestion yet.
 *
 * Each course is processed once; the outcome is kept in block_kursfilter_tag_pending:
 * 'applied' (tags added), 'pending' (waiting for review) or 'empty' (no value fits).
 * A failed request leaves no record, so the course is retried on the next run.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_courses extends \core\task\scheduled_task {
    /** Courses per run when the setting is missing (documented in the setting). */
    const DEFAULT_BATCH_SIZE = 20;

    /**
     * Return the task name shown in the admin interface.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_tag_courses', 'block_kursfilter');
    }

    /**
     * Tag the next batch of courses; a failed course does not stop the others, but fails the task.
     *
     * @throws \moodle_exception If AI tagging is enabled without an AI provider, or a course failed.
     */
    public function execute(): void {
        if (!get_config('block_kursfilter', 'ai_enabled')) {
            mtrace('AI tagging is disabled in the Course Filter settings.');
            return;
        }
        if (!tag_suggester::is_available()) {
            throw new \moodle_exception('error_ai_unavailable', 'block_kursfilter');
        }

        $autoapply = (bool)get_config('block_kursfilter', 'ai_autoapply');
        $userid = (int)get_admin()->id;
        $courses = self::next_courses((int)get_config('block_kursfilter', 'ai_batch_size') ?: self::DEFAULT_BATCH_SIZE);

        $failed = 0;
        foreach ($courses as $course) {
            try {
                $tags = tag_suggester::suggest($course, $course->categoryname, $userid);
                $status = self::record_outcome($course, $tags, $autoapply);
                mtrace("Course {$course->id}: {$status} (" . implode(', ', $tags) . ')');
            } catch (\Throwable $e) {
                mtrace("Course {$course->id}: AI tagging failed: " . $e->getMessage());
                $failed++;
            }
        }

        if ($failed > 0) {
            throw new \moodle_exception('error_ai_tagging_failed', 'block_kursfilter', '', (object)[
                'failed' => $failed,
                'total' => count($courses),
            ]);
        }
    }

    /**
     * Public courses without a tag suggestion yet.
     *
     * @param int $limit Maximum number of courses.
     * @return \stdClass[] Course records with categoryname.
     */
    private static function next_courses(int $limit): array {
        global $DB;

        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, cc.name AS categoryname
                  FROM {course} c
                  JOIN {course_categories} cc ON cc.id = c.category
             LEFT JOIN {block_kursfilter_tag_pending} tp ON tp.courseid = c.id
                 WHERE c.id != :siteid AND c.visible = 1 AND tp.id IS NULL
              ORDER BY c.id ASC";
        return $DB->get_records_sql($sql, ['siteid' => SITEID], 0, $limit);
    }

    /**
     * Apply or keep the suggestion and record the outcome for the course.
     *
     * @param \stdClass $course Course record.
     * @param string[] $tags Suggested filter values.
     * @param bool $autoapply Whether suggestions become course tags at once.
     * @return string Recorded status.
     */
    private static function record_outcome(\stdClass $course, array $tags, bool $autoapply): string {
        global $DB;

        if (!$tags) {
            $status = 'empty';
        } else if ($autoapply) {
            $context = \context_course::instance($course->id);
            foreach ($tags as $tag) {
                \core_tag_tag::add_item_tag('core', 'course', $course->id, $context, $tag);
            }
            $status = 'applied';
        } else {
            $status = 'pending';
        }

        $now = time();
        $DB->insert_record('block_kursfilter_tag_pending', [
            'courseid' => $course->id,
            'tags' => implode(',', $tags),
            'status' => $status,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return $status;
    }
}
