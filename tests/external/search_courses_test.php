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

namespace block_kursfilter\external;

use core_external\external_api;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the course search web service (visibility, filters, limits, access).
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(search_courses::class)]
final class search_courses_test extends \advanced_testcase {
    /**
     * Runs the search the way a web service client does, including return value cleaning.
     *
     * @param array $args Parameters for the search (name => value).
     * @return array Cleaned search result.
     */
    private function search(array $args = []): array {
        $result = search_courses::execute(...$args);
        return external_api::clean_returnvalue(search_courses::execute_returns(), $result);
    }

    /**
     * Returns the course IDs of a search result.
     *
     * @param array $result Search result.
     * @return int[] Course IDs.
     */
    private function ids(array $result): array {
        return array_map(fn($c) => $c['id'], $result['courses']);
    }

    public function test_only_visible_non_site_courses_are_found(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $visible = $this->getDataGenerator()->create_course(['visible' => 1]);
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        $ids = $this->ids($this->search());

        $this->assertContains((int)$visible->id, $ids);
        $this->assertNotContains((int)$hidden->id, $ids);
        $this->assertNotContains((int)SITEID, $ids);
    }

    public function test_name_search_finds_matching_course_only(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $match = $this->getDataGenerator()->create_course(['fullname' => 'Mathematik Klasse 7']);
        $other = $this->getDataGenerator()->create_course(['fullname' => 'Deutsch Klasse 7']);

        $ids = $this->ids($this->search(['searchterm' => 'mathematik']));

        $this->assertSame([(int)$match->id], $ids);
        $this->assertNotContains((int)$other->id, $ids);
    }

    public function test_like_wildcards_in_search_term_are_escaped(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->getDataGenerator()->create_course(['fullname' => 'Physik']);

        $this->assertSame([], $this->ids($this->search(['searchterm' => '%'])));
        $this->assertSame([], $this->ids($this->search(['searchterm' => '_hysik'])));
    }

    public function test_category_filter_includes_subcategories_and_excludes_others(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $parent = $this->getDataGenerator()->create_category();
        $child = $this->getDataGenerator()->create_category(['parent' => $parent->id]);
        $inparent = $this->getDataGenerator()->create_course(['category' => $parent->id]);
        $inchild = $this->getDataGenerator()->create_course(['category' => $child->id]);
        $elsewhere = $this->getDataGenerator()->create_course();

        $ids = $this->ids($this->search(['category' => $parent->id]));

        $this->assertEqualsCanonicalizing([(int)$inparent->id, (int)$inchild->id], $ids);
        $this->assertNotContains((int)$elsewhere->id, $ids);
    }

    public function test_tag_filter_matches_only_tagged_courses(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $tagged = $this->getDataGenerator()->create_course();
        $untagged = $this->getDataGenerator()->create_course();
        \core_tag_tag::set_item_tags('core', 'course', $tagged->id, \context_course::instance($tagged->id), ['Gymnasium']);

        $ids = $this->ids($this->search(['schooltype' => 'gymnasium']));

        $this->assertSame([(int)$tagged->id], $ids);
        $this->assertNotContains((int)$untagged->id, $ids);
    }

    public function test_result_limit_is_capped_by_configured_limit(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_config('resultlimit', 2, 'block_kursfilter');
        for ($i = 0; $i < 4; $i++) {
            $this->getDataGenerator()->create_course();
        }

        $this->assertCount(2, $this->search(['limit' => 1000])['courses']);
        $this->assertCount(1, $this->search(['limit' => 1])['courses']);
    }

    public function test_non_positive_limit_falls_back_to_configured_limit(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_config('resultlimit', 2, 'block_kursfilter');
        for ($i = 0; $i < 4; $i++) {
            $this->getDataGenerator()->create_course();
        }

        $this->assertCount(2, $this->search(['limit' => 0])['courses']);
        $this->assertCount(2, $this->search(['limit' => -5])['courses']);
    }

    public function test_configured_limit_above_hard_maximum_falls_back_to_default(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        set_config('resultlimit', 100000, 'block_kursfilter');
        $category = $this->getDataGenerator()->create_category();
        // Plain course rows keep the setup fast; the search reads only the course table.
        for ($i = 0; $i <= search_courses::DEFAULT_RESULT_LIMIT; $i++) {
            $DB->insert_record('course', [
                'category' => $category->id, 'fullname' => "Course $i", 'shortname' => "c$i", 'summary' => '', 'visible' => 1,
            ]);
        }

        $this->assertCount(search_courses::DEFAULT_RESULT_LIMIT, $this->search(['limit' => 100000])['courses']);
    }

    public function test_rate_limit_blocks_request_over_threshold(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        for ($i = 0; $i < search_courses::RATE_LIMIT_REQUESTS; $i++) {
            $this->search();
        }

        $this->expectException(\moodle_exception::class);
        $this->search();
    }

    public function test_rate_limit_is_tracked_per_user(): void {
        $this->resetAfterTest();
        $first = $this->getDataGenerator()->create_user();
        $second = $this->getDataGenerator()->create_user();

        $this->setUser($first);
        for ($i = 0; $i < search_courses::RATE_LIMIT_REQUESTS; $i++) {
            $this->search();
        }

        $course = $this->getDataGenerator()->create_course();
        $this->setUser($second);
        $this->assertSame([(int)$course->id], $this->ids($this->search()));
    }

    public function test_search_requires_the_search_capability(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_course();
        $userrole = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('block/kursfilter:search', CAP_PROHIBIT, $userrole, \context_system::instance()->id, true);
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        $this->search();
    }

    public function test_guest_may_search(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->setGuestUser();

        $this->assertSame([(int)$course->id], $this->ids($this->search()));
    }

    public function test_course_summary_markup_is_not_returned_as_html(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());
        $this->getDataGenerator()->create_course([
            'summary' => '<script>alert(1)</script><b>Text</b>',
            'summaryformat' => FORMAT_HTML,
        ]);

        $summary = $this->search()['courses'][0]['summary'];

        $this->assertStringNotContainsString('<script', $summary);
        $this->assertStringNotContainsString('<b>', $summary);
    }
}
