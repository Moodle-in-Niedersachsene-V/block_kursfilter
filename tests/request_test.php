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

/**
 * Request-Tests gegen die echten Endpunkte rate.php und backup.php.
 *
 * Benoetigt einen laufenden Webserver auf denselben (PHPUnit-)Tabellen; die URL
 * steht in der Umgebungsvariable KURSFILTER_WEB_URL. Ohne sie werden die Tests uebersprungen.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class request_test extends \advanced_testcase {
    /** @var string Basis-URL des Testwebservers. */
    private string $baseurl;

    /** @var string Cookie-Datei dieser Test-Sitzung. */
    private string $cookiejar;

    protected function setUp(): void {
        global $DB;
        parent::setUp();
        // Der Webserver sieht Testdaten nur, wenn sie committet sind: Test-Transaktion beenden.
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
        global $DB;
        if (!empty($this->cookiejar)) {
            @unlink($this->cookiejar);
        }
        // Committete Testdaten zuruecksetzen; Schreibzugriffe des Webservers erkennt PHPUnit nicht.
        $DB->delete_records('block_kursfilter_ratings');
        \phpunit_util::$lastdbwrites = null;
        self::resetAllData(false);
        parent::tearDown();
    }

    /**
     * Sendet eine HTTP-Anfrage an den Testwebserver.
     *
     * @param string $method GET oder POST.
     * @param string $path Pfad ab Webroot.
     * @param array $fields POST-Felder bzw. GET-Query.
     * @param string $cookie Zusaetzlicher Cookie-Header.
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
     * Holt einen gueltigen Sesskey fuer die laufende Sitzung.
     *
     * @return string Sesskey.
     */
    private function sesskey(): string {
        [, , $body] = $this->request('GET', '/login/index.php');
        $this->assertSame(1, preg_match('/"sesskey":"([A-Za-z0-9]+)"/', $body, $m), 'Kein Sesskey gefunden.');
        return $m[1];
    }

    /**
     * Sendet eine Bewertung und liefert die dekodierte JSON-Antwort.
     *
     * @param array $fields POST-Felder.
     * @param string $cookie Optionaler Cookie-Header.
     * @return array JSON-Antwort.
     */
    private function rate(array $fields, string $cookie = ''): array {
        [, , $body] = $this->request('POST', '/blocks/kursfilter/rate.php', $fields, $cookie);
        $json = json_decode($body, true);
        $this->assertIsArray($json, 'Keine JSON-Antwort: ' . $body);
        return $json;
    }

    /**
     * Legt eine Backup-Datei im Bereich des Blocks ab.
     *
     * @param int $courseid Kurs-ID (itemid).
     * @param string $content Dateiinhalt.
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

    /**
     * @dataProvider invalid_stars_provider
     * @param int $stars Unzulaessiger Wert.
     */
    public function test_rate_rejects_out_of_range_stars(int $stars): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();

        $result = $this->rate(['courseid' => $course->id, 'stars' => $stars, 'sesskey' => $this->sesskey()]);

        $this->assertSame('Invalid rating', $result['error']);
        $this->assertSame(0, $DB->count_records('block_kursfilter_ratings'));
    }

    /**
     * Unzulaessige Sternewerte.
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
}
