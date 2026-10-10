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

namespace block_kursfilter\event;

/**
 * Event raised when a visitor rates a course.
 *
 * The anonymous visitor cookie hash is deliberately not part of the event data.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_rated extends \core\event\base {
    /**
     * Set the static event properties.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'block_kursfilter_ratings';
    }

    /**
     * Localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_course_rated', 'block_kursfilter');
    }

    /**
     * Non-localised description for the log.
     *
     * @return string
     */
    public function get_description(): string {
        return "A visitor rated the course with id '{$this->courseid}' with {$this->other['stars']} stars.";
    }

    /**
     * Validate the custom data.
     *
     * @throws \coding_exception If the star count is missing.
     */
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['stars'])) {
            throw new \coding_exception('The \'stars\' value must be set in other.');
        }
    }

    /**
     * No backup mapping: ratings are not part of course backups.
     *
     * @return bool
     */
    public static function get_objectid_mapping(): bool {
        return false;
    }

    /**
     * No backup mapping for the other data.
     *
     * @return bool
     */
    public static function get_other_mapping(): bool {
        return false;
    }
}
