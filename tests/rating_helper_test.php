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
 * Tests for storing course ratings and the Moodle event raised by it.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(rating_helper::class)]
final class rating_helper_test extends \advanced_testcase {
    /** @var string Visitor cookie hash used by the tests. */
    private const HASH = 'a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1';

    public function test_saving_a_rating_raises_course_rated_event_without_visitor_hash(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $sink = $this->redirectEvents();

        $saved = rating_helper::save_rating((int)$course->id, self::HASH, 4);

        $events = array_values(array_filter(
            $sink->get_events(),
            fn($event) => $event instanceof event\course_rated
        ));
        $this->assertTrue($saved);
        $this->assertCount(1, $events);
        $this->assertEquals(\context_course::instance($course->id), $events[0]->get_context());
        $this->assertSame((int)$course->id, (int)$events[0]->courseid);
        $this->assertSame(4, (int)$events[0]->other['stars']);
        $this->assertStringNotContainsString(self::HASH, json_encode($events[0]->get_data()));
    }

    public function test_repeated_or_invalid_rating_raises_no_event(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        rating_helper::save_rating((int)$course->id, self::HASH, 3);
        $sink = $this->redirectEvents();

        $again = rating_helper::save_rating((int)$course->id, self::HASH, 5);
        $invalid = rating_helper::save_rating((int)$course->id, str_repeat('b', 64), 6);

        $this->assertFalse($again);
        $this->assertFalse($invalid);
        $this->assertCount(0, $sink->get_events());
        $this->assertSame(3, rating_helper::get_existing_rating((int)$course->id, self::HASH));
    }

    public function test_submit_saves_a_rating_for_a_public_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $result = rating_helper::submit((int)$course->id, 4, fn() => self::HASH);

        $this->assertSame(['success' => true, 'saved' => true, 'already_rated' => false], $result);
        $this->assertSame(4, rating_helper::get_existing_rating((int)$course->id, self::HASH));
    }

    public function test_submit_reports_a_repeated_rating_without_changing_it(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        rating_helper::submit((int)$course->id, 2, fn() => self::HASH);

        $result = rating_helper::submit((int)$course->id, 5, fn() => self::HASH);

        $this->assertSame(['success' => true, 'saved' => false, 'already_rated' => true], $result);
        $this->assertSame(2, rating_helper::get_existing_rating((int)$course->id, self::HASH));
    }

    public function test_submit_rejects_invalid_stars_before_touching_the_cookie(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $cookiecalls = 0;
        $cookie = function () use (&$cookiecalls) {
            $cookiecalls++;
            return self::HASH;
        };

        $zero = rating_helper::submit((int)$course->id, 0, $cookie);
        $six = rating_helper::submit((int)$course->id, 6, $cookie);

        $this->assertSame(['success' => false, 'error' => 'Invalid rating'], $zero);
        $this->assertSame(['success' => false, 'error' => 'Invalid rating'], $six);
        $this->assertSame(0, $cookiecalls);
    }

    public function test_submit_rejects_hidden_and_unknown_courses_before_touching_the_cookie(): void {
        global $DB;
        $this->resetAfterTest();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);
        $cookiecalls = 0;
        $cookie = function () use (&$cookiecalls) {
            $cookiecalls++;
            return self::HASH;
        };

        $resulthidden = rating_helper::submit((int)$hidden->id, 3, $cookie);
        $resultunknown = rating_helper::submit(99999, 3, $cookie);

        $this->assertSame(['success' => false, 'error' => 'Course not found'], $resulthidden);
        $this->assertSame(['success' => false, 'error' => 'Course not found'], $resultunknown);
        $this->assertSame(0, $cookiecalls);
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
    }
}
