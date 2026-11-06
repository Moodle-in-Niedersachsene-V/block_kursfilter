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
 * Tests for rating a course, reading course ratings and the Moodle event raised by a rating.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(rating_helper::class)]
final class rating_helper_test extends \advanced_testcase {
    /** @var string Visitor cookie hash used by the tests. */
    private const HASH = 'a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1a1';

    /**
     * Gives the request the visitor cookie used by the tests.
     */
    private function visit(): void {
        $_COOKIE[visitor_id::COOKIE_NAME] = self::HASH;
    }

    public function test_rating_raises_course_rated_event_once_without_visitor_hash(): void {
        $this->resetAfterTest();
        $this->visit();
        $course = $this->getDataGenerator()->create_course();
        $sink = $this->redirectEvents();

        $result = rating_helper::rate((int)$course->id, 4);

        $events = array_values(array_filter(
            $sink->get_events(),
            fn($event) => $event instanceof event\course_rated
        ));
        $this->assertSame(['success' => true, 'saved' => true, 'already_rated' => false, 'stars' => 4], $result);
        $this->assertCount(1, $events);
        $this->assertEquals(\context_course::instance($course->id), $events[0]->get_context());
        $this->assertSame((int)$course->id, (int)$events[0]->courseid);
        $this->assertSame(4, (int)$events[0]->other['stars']);
        $this->assertStringNotContainsString(self::HASH, json_encode($events[0]->get_data()));
    }

    public function test_repeated_rating_keeps_the_first_and_raises_no_event(): void {
        global $DB;
        $this->resetAfterTest();
        $this->visit();
        $course = $this->getDataGenerator()->create_course();
        rating_helper::rate((int)$course->id, 2);
        $sink = $this->redirectEvents();

        $again = rating_helper::rate((int)$course->id, 5);

        $this->assertSame(['success' => true, 'saved' => false, 'already_rated' => true, 'stars' => 2], $again);
        $this->assertCount(0, $sink->get_events());
        $this->assertSame(1, $DB->count_records('block_kursfilter_ratings'));
        $this->assertSame(2, (int)$DB->get_field('block_kursfilter_ratings', 'stars', ['courseid' => $course->id]));
    }

    public function test_invalid_stars_store_nothing_and_set_no_cookie(): void {
        global $DB;
        $this->resetAfterTest();
        unset($_COOKIE[visitor_id::COOKIE_NAME]);
        $course = $this->getDataGenerator()->create_course();
        $sink = $this->redirectEvents();

        foreach ([0, 6] as $stars) {
            $this->assertSame(
                ['success' => false, 'error' => 'Invalid rating'],
                rating_helper::rate((int)$course->id, $stars)
            );
        }

        $this->assertCount(0, $sink->get_events());
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
        $this->assertArrayNotHasKey(visitor_id::COOKIE_NAME, $_COOKIE);
    }

    public function test_hidden_and_unknown_courses_store_nothing_and_set_no_cookie(): void {
        global $DB;
        $this->resetAfterTest();
        unset($_COOKIE[visitor_id::COOKIE_NAME]);
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);

        $resulthidden = rating_helper::rate((int)$hidden->id, 3);
        $resultunknown = rating_helper::rate(99999, 3);

        $this->assertSame(['success' => false, 'error' => 'Course not found'], $resulthidden);
        $this->assertSame(['success' => false, 'error' => 'Course not found'], $resultunknown);
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
        $this->assertArrayNotHasKey(visitor_id::COOKIE_NAME, $_COOKIE);
    }

    public function test_first_rating_without_cookie_creates_one_and_stores_its_hash(): void {
        global $DB;
        $this->resetAfterTest();
        unset($_COOKIE[visitor_id::COOKIE_NAME]);
        $course = $this->getDataGenerator()->create_course();

        rating_helper::rate((int)$course->id, 5);

        $cookie = $_COOKIE[visitor_id::COOKIE_NAME];
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $cookie);
        $this->assertSame($cookie, $DB->get_field('block_kursfilter_ratings', 'cookiehash', ['courseid' => $course->id]));
    }

    public function test_course_ratings_of_unrated_courses_are_zero_zero_null(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $ratings = rating_helper::get_course_ratings([(int)$course->id], self::HASH);

        $this->assertSame(['avg' => 0.0, 'count' => 0, 'own' => null], $ratings[(int)$course->id]);
    }

    public function test_course_ratings_give_average_count_and_own_rating(): void {
        $this->resetAfterTest();
        $rated = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $this->visit();
        rating_helper::rate((int)$rated->id, 4);
        $_COOKIE[visitor_id::COOKIE_NAME] = str_repeat('b', 64);
        rating_helper::rate((int)$rated->id, 5);
        $_COOKIE[visitor_id::COOKIE_NAME] = self::HASH;

        $ratings = rating_helper::get_course_ratings([(int)$rated->id, (int)$other->id], self::HASH);

        $this->assertSame(['avg' => 4.5, 'count' => 2, 'own' => 4], $ratings[(int)$rated->id]);
        $this->assertSame(['avg' => 0.0, 'count' => 0, 'own' => null], $ratings[(int)$other->id]);
    }

    public function test_course_ratings_without_visitor_give_no_own_rating(): void {
        $this->resetAfterTest();
        $this->visit();
        $course = $this->getDataGenerator()->create_course();
        rating_helper::rate((int)$course->id, 3);

        $ratings = rating_helper::get_course_ratings([(int)$course->id], null);

        $this->assertSame(['avg' => 3.0, 'count' => 1, 'own' => null], $ratings[(int)$course->id]);
    }
}
