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

namespace block_kursfilter;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\types;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Privacy metadata contract for ratings, the browser cookie and AI requests.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(privacy\provider::class)]
final class privacy_provider_test extends \advanced_testcase {
    public function test_metadata_declares_ratings_cookie_ai_and_event_logs(): void {
        $metadata = privacy\provider::get_metadata(new collection('block_kursfilter'));
        $items = [];
        foreach ($metadata->get_collection() as $item) {
            $items[$item->get_name()] = $item;
            $this->assertTrue(get_string_manager()->string_exists($item->get_summary(), 'block_kursfilter'));
            foreach ($item->get_privacy_fields() as $description) {
                $this->assertTrue(get_string_manager()->string_exists($description, 'block_kursfilter'));
            }
        }

        $this->assertInstanceOf(types\database_table::class, $items['block_kursfilter_ratings']);
        $this->assertSame(
            ['courseid', 'cookiehash', 'stars', 'timecreated'],
            array_keys($items['block_kursfilter_ratings']->get_privacy_fields())
        );
        $this->assertInstanceOf(types\external_location::class, $items['kf_rater_id']);
        $this->assertSame(['cookiehash'], array_keys($items['kf_rater_id']->get_privacy_fields()));
        $this->assertInstanceOf(types\subsystem_link::class, $items['core_ai']);
        $this->assertSame(['prompt', 'userid', 'contextid'], array_keys($items['core_ai']->get_privacy_fields()));
        $this->assertInstanceOf(types\plugintype_link::class, $items['logstore']);
        $this->assertSame(['userid', 'ip', 'objectid', 'other'], array_keys($items['logstore']->get_privacy_fields()));
    }
}
