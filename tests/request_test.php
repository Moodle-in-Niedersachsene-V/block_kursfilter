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

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Request tests against the real endpoints rate.php, backup.php and guest_login.php.
 *
 * Needs a running web server on the same (PHPUnit) tables; the URL is
 * given in the environment variable KURSFILTER_WEB_URL. Without it the tests are skipped.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class request_test extends \advanced_testcase {
    /** @var string Base URL of the test web server. */
    private string $baseurl;

    /** @var string Cookie file of this test session. */
    private string $cookiejar;

    protected function setUp(): void {
        global $DB;
        parent::setUp();
        // The web server only sees test data once it is committed: end the test transaction.
        if ($DB->is_transaction_started()) {
            $DB->force_transaction_rollback();
        }
        $url = getenv('KURSFILTER_WEB_URL');
        if (!$url) {
            $this->markTestSkipped('KURSFILTER_WEB_URL nicht gesetzt.');
        }
        $this->baseurl = rtrim($url, '/');
        $this->cookiejar = tempnam(sys_get_temp_dir(), 'kfjar');
    }

    protected function tearDown(): void {
        global $CFG;
        if (!empty($this->cookiejar)) {
            @unlink($this->cookiejar);
        }
        // PHPUnit does not detect writes by the web server: an empty table list forces a full reset.
        \testing_util::$tableupdated = [];
        // Pool occupancy markers live in the web server's file cache.
        fulldelete($CFG->dataroot . '/cache/cachestore_file/default_application/block_kursfilter_poolsessions');
        \phpunit_util::$lastdbwrites = null;
        self::resetAllData(false);
        parent::tearDown();
    }

    /**
     * Sends an HTTP request to the test web server.
     *
     * @param string $method GET or POST.
     * @param string $path Path from the web root.
     * @param array $fields POST fields or GET query.
     * @param string $cookie Additional cookie header.
     * @return array [status, headers, body].
     */
    private function request(string $method, string $path, array $fields = [], string $cookie = ''): array {
        $ch = curl_init();
        $url = $this->baseurl . $path;
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields, '', '&'));
        } else if ($fields) {
            $url .= '?' . http_build_query($fields, '', '&');
        }
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_COOKIEJAR => $this->cookiejar,
            CURLOPT_COOKIEFILE => $this->cookiejar,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($cookie !== '') {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        $raw = curl_exec($ch);
        $this->assertNotFalse($raw, 'Testwebserver nicht erreichbar: ' . curl_error($ch));
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headersize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        return [$status, substr($raw, 0, $headersize), substr($raw, $headersize)];
    }

    /**
     * Fetches a valid sesskey for the current session.
     *
     * @return string Sesskey.
     */
    private function sesskey(): string {
        [, , $body] = $this->request('GET', '/login/index.php');
        $this->assertSame(1, preg_match('/"sesskey":"([A-Za-z0-9]+)"/', $body, $m), 'Kein Sesskey gefunden.');
        return $m[1];
    }

    /**
     * Sends a rating and returns the decoded JSON response.
     *
     * @param array $fields POST fields.
     * @param string $cookie Optional cookie header.
     * @return array JSON response.
     */
    private function rate(array $fields, string $cookie = ''): array {
        [, , $body] = $this->request('POST', '/blocks/kursfilter/rate.php', $fields, $cookie);
        $json = json_decode($body, true);
        $this->assertIsArray($json, 'Keine JSON-Antwort: ' . $body);
        return $json;
    }

    /**
     * Stores a backup file in the block's file area.
     *
     * @param int $courseid Course ID (itemid).
     * @param string $content File content.
     */
    private function seed_backup(int $courseid, string $content): void {
        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'block_kursfilter',
            'filearea' => 'course_backups',
            'itemid' => $courseid,
            'filepath' => '/',
            'filename' => 'backup_' . $courseid . '.mbz',
        ], $content);
    }

    public function test_rate_rejects_get_request(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        [, , $body] = $this->request('GET', '/blocks/kursfilter/rate.php', ['courseid' => $course->id, 'stars' => 5]);

        $this->assertSame('Method not allowed', json_decode($body, true)['error']);
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
    }

    public function test_rate_rejects_missing_or_wrong_sesskey(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $fields = ['courseid' => $course->id, 'stars' => 5];

        $this->assertSame('missingparam', $this->rate($fields)['errorcode']);
        $this->assertSame('Invalid sesskey', $this->rate($fields + ['sesskey' => 'falsch12345'])['error']);
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
    }

    #[DataProvider('invalid_stars_provider')]
    public function test_rate_rejects_out_of_range_stars(int $stars): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $result = $this->rate(['courseid' => $course->id, 'stars' => $stars, 'sesskey' => $this->sesskey()]);

        $this->assertSame('Invalid rating', $result['error']);
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
    }

    /**
     * Invalid star values.
     *
     * @return array[]
     */
    public static function invalid_stars_provider(): array {
        return ['null' => [0], 'sechs' => [6], 'negativ' => [-1], 'riesig' => [2147483647]];
    }

    public function test_rate_rejects_hidden_and_unknown_courses(): void {
        global $DB;
        $this->resetAfterTest();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);
        $sesskey = $this->sesskey();

        foreach ([$hidden->id, 99999] as $courseid) {
            $result = $this->rate(['courseid' => $courseid, 'stars' => 4, 'sesskey' => $sesskey]);
            $this->assertSame('Course not found', $result['error']);
        }
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
    }

    public function test_rate_rejects_site_course(): void {
        global $DB;
        $this->resetAfterTest();

        $result = $this->rate(['courseid' => SITEID, 'stars' => 5, 'sesskey' => $this->sesskey()]);

        $this->assertFalse($result['success']);
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings', ['courseid' => SITEID]));
    }

    public function test_rate_stores_once_per_cookie(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $sesskey = $this->sesskey();
        $cookie = 'kf_rater_id=' . str_repeat('a', 64);
        $fields = ['courseid' => $course->id, 'sesskey' => $sesskey];

        $first = $this->rate($fields + ['stars' => 4], $cookie);
        $second = $this->rate($fields + ['stars' => 1], $cookie);

        $this->assertTrue($first['saved']);
        $this->assertFalse($second['saved']);
        $this->assertTrue($second['already_rated']);
        $this->assertSame(4, (int)$DB->get_field('block_kursfilter_ratings', 'stars', ['courseid' => $course->id]));
        $this->assertSame(1, $DB->count_records('block_kursfilter_ratings'));
    }

    public function test_rate_replaces_malformed_cookie_value(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $this->rate(
            ['courseid' => $course->id, 'stars' => 3, 'sesskey' => $this->sesskey()],
            "kf_rater_id=x'; DROP TABLE mdl_user;--"
        );

        $hash = $DB->get_field('block_kursfilter_ratings', 'cookiehash', ['courseid' => $course->id]);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function test_backup_download_serves_file_of_visible_course(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'OFFEN1']);
        $this->seed_backup($course->id, 'MBZ-INHALT');

        [$status, $headers, $body] = $this->request('GET', '/blocks/kursfilter/backup.php', ['courseid' => $course->id]);

        $this->assertSame(200, $status);
        $this->assertStringContainsStringIgnoringCase('attachment', $headers);
        $this->assertStringContainsString('OFFEN1_backup.mbz', $headers);
        $this->assertSame('MBZ-INHALT', $body);
    }

    public function test_backup_download_rejects_hidden_course_with_file(): void {
        $this->resetAfterTest();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);
        $this->seed_backup($hidden->id, 'GEHEIM');

        [$status, , $body] = $this->request('GET', '/blocks/kursfilter/backup.php', ['courseid' => $hidden->id]);

        $this->assertSame(404, $status);
        $this->assertStringNotContainsString('GEHEIM', $body);
    }

    public function test_backup_download_rejects_unknown_course(): void {
        $this->resetAfterTest();

        [$status] = $this->request('GET', '/blocks/kursfilter/backup.php', ['courseid' => 99999]);

        $this->assertSame(404, $status);
    }

    public function test_backup_download_rejects_site_course_with_file(): void {
        $this->resetAfterTest();
        $this->seed_backup(SITEID, 'STARTSEITE');

        [$status, , $body] = $this->request('GET', '/blocks/kursfilter/backup.php', ['courseid' => SITEID]);

        $this->assertSame(404, $status);
        $this->assertStringNotContainsString('STARTSEITE', $body);
    }

    public function test_backup_download_without_file_sends_no_attachment(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        [$status, $headers] = $this->request('GET', '/blocks/kursfilter/backup.php', ['courseid' => $course->id]);

        $this->assertSame(200, $status);
        $this->assertStringNotContainsStringIgnoringCase('attachment', $headers);
    }

    /**
     * Creates pool accounts and returns their user IDs.
     *
     * @param int $size Pool size.
     * @return int[] User IDs.
     */
    private function create_pool(int $size): array {
        global $DB;
        set_config('poolsize', $size, 'block_kursfilter');
        pool_manager::create_pool_users();
        [$insql, $params] = $DB->get_in_or_equal(pool_manager::get_pool_usernames());
        return array_keys($DB->get_records_select('user', "username $insql", $params, '', 'id'));
    }

    /**
     * Counts active sessions of the given users.
     *
     * @param int[] $userids User IDs.
     * @return int Number.
     */
    private function count_sessions(array $userids): int {
        global $DB;
        [$insql, $params] = $DB->get_in_or_equal($userids);
        return $DB->count_records_select('sessions', "userid $insql", $params);
    }

    public function test_guest_login_signs_in_pool_user_and_redirects_to_preview(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $poolids = $this->create_pool(2);

        [$status, $headers] = $this->request('GET', '/blocks/kursfilter/guest_login.php', ['courseid' => $course->id]);

        $this->assertSame(303, $status);
        $this->assertStringContainsString('/course/view.php?id=' . $course->id . '&kf_preview=1', $headers);
        $this->assertSame(1, $this->count_sessions($poolids));
        $context = \context_course::instance($course->id);
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'teacher']);
        foreach ($poolids as $id) {
            $this->assertTrue(is_enrolled($context, $id, '', true));
            $this->assertTrue(user_has_role_assignment($id, $roleid, $context->id));
        }
    }

    public function test_guest_login_rejects_hidden_unknown_and_site_course(): void {
        global $DB;
        $this->resetAfterTest();
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);
        $poolids = $this->create_pool(2);

        foreach ([$hidden->id, 99999, SITEID] as $courseid) {
            [$status, $headers, $body] = $this->request('GET', '/blocks/kursfilter/guest_login.php', ['courseid' => $courseid]);
            $this->assertStringNotContainsString('Location:', $headers, "Kurs $courseid");
            $this->assertStringContainsString(get_string('course_not_found', 'block_kursfilter'), $body, "Kurs $courseid");
        }
        $this->assertSame(0, $this->count_sessions($poolids));
        [$insql, $params] = $DB->get_in_or_equal($poolids);
        $this->assertSame(0, $DB->count_records_select('user_enrolments', "userid $insql", $params));
    }

    public function test_guest_login_shows_pool_full_when_all_accounts_are_taken(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $poolids = $this->create_pool(1);

        $this->request('GET', '/blocks/kursfilter/guest_login.php', ['courseid' => $course->id]);
        $this->cookiejar = tempnam(sys_get_temp_dir(), 'kfjar');
        [, $headers, $body] = $this->request('GET', '/blocks/kursfilter/guest_login.php', ['courseid' => $course->id]);

        $this->assertStringNotContainsString('Location:', $headers);
        $this->assertStringContainsString(get_string('pool_full', 'block_kursfilter'), $body);
        $this->assertSame(1, $this->count_sessions($poolids));
    }

    public function test_guest_login_does_not_replace_a_real_login(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $poolids = $this->create_pool(2);
        $user = $this->getDataGenerator()->create_user(['username' => 'echtnutzer', 'password' => 'Test-Passwort1!']);
        [, , $loginpage] = $this->request('GET', '/login/index.php');
        $this->assertSame(1, preg_match('/name="logintoken" value="([^"]+)"/', $loginpage, $m));
        $this->request('POST', '/login/index.php', [
            'username' => 'echtnutzer', 'password' => 'Test-Passwort1!', 'logintoken' => $m[1],
        ]);

        [$status, $headers] = $this->request('GET', '/blocks/kursfilter/guest_login.php', ['courseid' => $course->id]);

        $this->assertSame(303, $status);
        $this->assertStringNotContainsString('kf_preview', $headers);
        $this->assertSame(0, $this->count_sessions($poolids));
        $this->assertSame(1, $this->count_sessions([$user->id]));
    }
}
