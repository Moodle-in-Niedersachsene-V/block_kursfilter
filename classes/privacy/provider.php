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

namespace block_kursfilter\privacy;

/**
 * Metadata for pseudonymous ratings and data passed to Moodle subsystems.
 *
 * Rating events can link a rating to a user and IP in the configured logstore.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {
    /**
     * Describe stored ratings, the browser cookie, event logs and AI requests.
     *
     * @param \core_privacy\local\metadata\collection $collection Metadata collection.
     * @return \core_privacy\local\metadata\collection Completed collection.
     */
    public static function get_metadata(
        \core_privacy\local\metadata\collection $collection
    ): \core_privacy\local\metadata\collection {
        $collection->add_database_table('block_kursfilter_ratings', [
            'courseid' => 'privacy:metadata:ratings:courseid',
            'cookiehash' => 'privacy:metadata:ratings:cookiehash',
            'stars' => 'privacy:metadata:ratings:stars',
            'timecreated' => 'privacy:metadata:ratings:timecreated',
        ], 'privacy:metadata:ratings');
        $collection->add_external_location_link('kf_rater_id', [
            'cookiehash' => 'privacy:metadata:ratings:cookiehash',
        ], 'privacy:metadata:cookie');
        $collection->add_subsystem_link('core_ai', [
            'prompt' => 'privacy:metadata:ai:prompt',
            'userid' => 'privacy:metadata:ai:userid',
            'contextid' => 'privacy:metadata:ai:contextid',
        ], 'privacy:metadata:ai');
        $collection->add_plugintype_link('logstore', [
            'userid' => 'privacy:metadata:logs:userid',
            'ip' => 'privacy:metadata:logs:ip',
            'objectid' => 'privacy:metadata:logs:objectid',
            'other' => 'privacy:metadata:logs:other',
        ], 'privacy:metadata:logs');
        return $collection;
    }
}
