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

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the public course visibility check shared by the public endpoints.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(course_access::class)]
final class course_access_test extends \advanced_testcase {
    public function test_visible_course_is_returned(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'OPEN1']);

        $found = course_access::get_public_course((int)$course->id);

        $this->assertNotNull($found);
        $this->assertSame('OPEN1', $found->shortname);
    }

    public function test_hidden_unknown_and_site_course_are_not_returned(): void {
        $this->resetAfterTest();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        $this->assertNull(course_access::get_public_course((int)$hidden->id));
        $this->assertNull(course_access::get_public_course(99999));
        $this->assertNull(course_access::get_public_course(SITEID));
    }

    public function test_public_course_sql_selects_exactly_the_public_courses(): void {
        global $DB;
        $this->resetAfterTest();
        $open = $this->getDataGenerator()->create_course();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        [$sql, $params] = course_access::public_course_sql('c');
        $ids = $DB->get_fieldset_sql("SELECT c.id FROM {course} c WHERE $sql", $params);

        $this->assertContains((string)$open->id, $ids);
        $this->assertNotContains((string)$hidden->id, $ids);
        $this->assertNotContains((string)SITEID, $ids);
    }

    public function test_root_category_ids_returns_null_when_not_configured(): void {
        $this->resetAfterTest();
        set_config('rootcategory', '', 'block_kursfilter');

        $this->assertNull(course_access::root_category_ids());
    }

    public function test_root_category_ids_returns_empty_for_unknown_name(): void {
        $this->resetAfterTest();
        set_config('rootcategory', 'Gibts Nicht', 'block_kursfilter');

        $this->assertSame([], course_access::root_category_ids());
    }

    public function test_root_category_ids_includes_root_and_all_descendants(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $root = $gen->create_category(['name' => 'Kurse']);
        $child = $gen->create_category(['parent' => $root->id]);
        $grandchild = $gen->create_category(['parent' => $child->id]);
        $other = $gen->create_category(['name' => 'Andere']);

        set_config('rootcategory', 'Kurse', 'block_kursfilter');
        $ids = course_access::root_category_ids();

        $this->assertContains($root->id, $ids);
        $this->assertContains($child->id, $ids);
        $this->assertContains($grandchild->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_root_category_ids_accepts_numeric_id(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $root = $gen->create_category(['name' => 'Kurse']);
        $child = $gen->create_category(['parent' => $root->id]);

        set_config('rootcategory', (string)$root->id, 'block_kursfilter');
        $ids = course_access::root_category_ids();

        $this->assertContains($root->id, $ids);
        $this->assertContains($child->id, $ids);
    }

    public function test_public_course_sql_with_root_category_excludes_courses_in_other_categories(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $rootcat = $gen->create_category(['name' => 'Kurse']);
        $othercat = $gen->create_category(['name' => 'Andere']);
        $courseinkurse = $gen->create_course(['category' => $rootcat->id]);
        $courseinandere = $gen->create_course(['category' => $othercat->id]);

        set_config('rootcategory', 'Kurse', 'block_kursfilter');
        [$sql, $params] = course_access::public_course_sql('c');
        $ids = $DB->get_fieldset_sql("SELECT c.id FROM {course} c WHERE $sql", $params);

        $this->assertContains((string)$courseinkurse->id, $ids);
        $this->assertNotContains((string)$courseinandere->id, $ids);
    }

    public function test_public_course_sql_without_root_category_includes_all_visible_courses(): void {
        global $DB;
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $cat1 = $gen->create_category();
        $cat2 = $gen->create_category();
        $c1 = $gen->create_course(['category' => $cat1->id]);
        $c2 = $gen->create_course(['category' => $cat2->id]);

        set_config('rootcategory', '', 'block_kursfilter');
        [$sql, $params] = course_access::public_course_sql('c');
        $ids = $DB->get_fieldset_sql("SELECT c.id FROM {course} c WHERE $sql", $params);

        $this->assertContains((string)$c1->id, $ids);
        $this->assertContains((string)$c2->id, $ids);
    }
}
