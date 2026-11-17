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
 * Tests for the visitor ID cookie (name, lifetime, attributes, renewal).
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(visitor_id::class)]
final class visitor_id_test extends \advanced_testcase {
    public function test_cookie_options_expire_after_one_year_and_stay_protected(): void {
        $before = time();
        $options = visitor_id::get_cookie_options();

        $this->assertGreaterThanOrEqual($before + YEARSECS, $options['expires']);
        $this->assertLessThanOrEqual(time() + YEARSECS, $options['expires']);
        $this->assertTrue($options['secure']);
        $this->assertTrue($options['httponly']);
        $this->assertSame('Lax', $options['samesite']);
        $this->assertSame('/', $options['path']);
    }

    public function test_cookie_name_is_kf_rater_id(): void {
        $this->assertSame('kf_rater_id', visitor_id::COOKIE_NAME);
    }

    public function test_renew_returns_a_valid_cookie_and_ignores_an_invalid_one(): void {
        $hash = str_repeat('a', 64);
        $_COOKIE[visitor_id::COOKIE_NAME] = $hash;
        $this->assertSame($hash, visitor_id::renew());

        $_COOKIE[visitor_id::COOKIE_NAME] = 'not-a-hash';
        $this->assertNull(visitor_id::renew());

        unset($_COOKIE[visitor_id::COOKIE_NAME]);
        $this->assertNull(visitor_id::renew());
    }

    public function test_get_or_create_keeps_a_valid_cookie(): void {
        $hash = str_repeat('c', 64);
        $_COOKIE[visitor_id::COOKIE_NAME] = $hash;

        $this->assertSame($hash, visitor_id::get_or_create());
    }

    public function test_get_or_create_creates_a_new_hash_without_cookie(): void {
        unset($_COOKIE[visitor_id::COOKIE_NAME]);

        $hash = visitor_id::get_or_create();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
        $this->assertSame($hash, $_COOKIE[visitor_id::COOKIE_NAME]);
    }
}
