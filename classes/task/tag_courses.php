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
 * Scheduled task: generate AI tag suggestions for untagged courses.
 *
 * Only anonymised course data is sent to the AI (fullname, shortname,
 * category name, truncated plain-text summary). No user IDs, teacher names,
 * enrolment data or Moodle-internal identifiers leave the server.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter\task;

use block_kursfilter\ai_connector;

/**
 * Processes a batch of courses without AI-generated tags and
 * applies tag suggestions directly or stores them for review.
 */
class tag_courses extends \core\task\scheduled_task {
    /**
     * Returns the task display name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_tag_courses', 'block_kursfilter');
    }

    /**
     * Finds courses without AI-generated tags and sends anonymised
     * course data to the configured AI backend.
     *
     * Transmitted data: fullname, shortname, category name and the first
     * 800 characters of the plain-text summary only.
     * Not transmitted: user IDs, teacher names, enrolment data, timestamps.
     */
    public function execute(): void {
        global $DB;

        $batchsize = (int)(get_config('block_kursfilter', 'ai_batch_size') ?: 20);
        $autoapply = (bool)get_config('block_kursfilter', 'ai_autoapply');
        $enabled = (bool)get_config('block_kursfilter', 'ai_enabled');

        if (!$enabled) {
            mtrace('block_kursfilter tag_courses: KI-Verschlagwortung ist deaktiviert.');
            return;
        }

        $connector = new ai_connector();

        // Kurse laden, die noch keine KI-Tags haben.
        // Nur oeffentlich sichtbare Kurse; keine Nutzerdaten abgefragt.
        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary,
                       cc.name AS categoryname
                  FROM {course} c
                  JOIN {course_categories} cc ON cc.id = c.category
                 WHERE c.id != :siteid
                   AND c.visible = 1
                   AND NOT EXISTS (
                       SELECT 1
                         FROM {tag_instance} ti
                         JOIN {tag} t ON t.id = ti.tagid
                        WHERE ti.itemtype = 'course'
                          AND ti.itemid   = c.id
                          AND ti.component = 'block_kursfilter'
                   )
              ORDER BY c.id ASC";

        $courses = $DB->get_records_sql($sql, ['siteid' => SITEID], 0, $batchsize);

        if (empty($courses)) {
            mtrace('block_kursfilter tag_courses: Keine unverschlagworteten Kurse gefunden.');
            return;
        }

        $processed = 0;

        foreach ($courses as $course) {
            $tags = $connector->suggest_tags(
                $course->fullname,
                $course->shortname,
                $course->summary,
                $course->categoryname
            );

            if (empty($tags)) {
                mtrace("block_kursfilter tag_courses: Kurs {$course->id} ({$course->shortname}) – keine Tags vorgeschlagen.");
                // Platzhalter-Tag setzen damit der Kurs nicht endlos neu versucht wird.
                // Platzhalter ueber block_kursfilter-Komponente setzen,
                // damit der Kurs nicht endlos erneut versucht wird.
                \core_tag_tag::set_item_tags(
                    'block_kursfilter',
                    'course',
                    (int)$course->id,
                    \context_course::instance((int)$course->id),
                    ['kursfilter:processed']
                );
                continue;
            }

            if ($autoapply) {
                // Tags ueber core-Komponente setzen, damit sie im Kurs sichtbar
                // sind und von der Suche gefunden werden (wie manuell gesetzte Tags).
                \core_tag_tag::set_item_tags(
                    'core',
                    'course',
                    (int)$course->id,
                    \context_course::instance((int)$course->id),
                    $tags
                );
                mtrace('block_kursfilter tag_courses: Kurs ' . $course->id .
                    ' (' . $course->shortname . ') -> ' . implode(', ', $tags));
            } else {
                // Vorschlag in der mdl_block_kursfilter_tag_pending Tabelle speichern.
                $this->store_pending($course->id, $tags);
                mtrace('block_kursfilter tag_courses: Kurs ' . $course->id .
                    ' (' . $course->shortname . ') - Vorschlag: ' . implode(', ', $tags));
            }

            $processed++;
        }

        mtrace("block_kursfilter tag_courses: {$processed} Kurse verarbeitet.");
    }

    /**
     * Stores tag suggestions in the pending review table.
     *
     * @param int      $courseid Course ID.
     * @param string[] $tags     List of suggested tags.
     */
    private function store_pending(int $courseid, array $tags): void {
        global $DB;

        $now = time();
        $record = $DB->get_record('block_kursfilter_tag_pending', ['courseid' => $courseid]);

        if ($record) {
            $record->tags = implode(',', $tags);
            $record->status = 'pending';
            $record->timemodified = $now;
            $DB->update_record('block_kursfilter_tag_pending', $record);
        } else {
            $DB->insert_record('block_kursfilter_tag_pending', (object)[
                'courseid'     => $courseid,
                'tags'         => implode(',', $tags),
                'status'       => 'pending',
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }
    }
}
