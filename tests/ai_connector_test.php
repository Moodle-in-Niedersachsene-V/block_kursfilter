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

/**
 * Unit tests for block_kursfilter\ai_connector.
 *
 * Tests only the parsing and prompt-building logic, not actual API calls.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \block_kursfilter\ai_connector
 */

namespace block_kursfilter;

use advanced_testcase;
use ReflectionClass;

/**
 * Tests for the ai_connector class.
 *
 * Because the actual API calls depend on external services, we test
 * only the internal parsing logic via reflection.
 */
final class ai_connector_test extends advanced_testcase {
    /** @var ReflectionClass */
    private ReflectionClass $ref;

    /** @var ai_connector */
    private ai_connector $connector;

    /**
     * Set up each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('ai_backend', 'ollama', 'block_kursfilter');
        set_config('ai_ollama_url', 'http://localhost:11434', 'block_kursfilter');
        set_config('ai_ollama_model', 'gemma3:4b', 'block_kursfilter');
        set_config('schooltypes', "Grundschule\nGymnasium", 'block_kursfilter');
        set_config('subjects', "Mathematik\nDeutsch", 'block_kursfilter');
        set_config('levels', "Klasse 1-4\nOberstufe", 'block_kursfilter');

        $this->connector = new ai_connector();
        $this->ref = new ReflectionClass(ai_connector::class);
    }

    /**
     * Call a private method via reflection.
     *
     * @param string $name   Method name.
     * @param array  $args   Arguments.
     * @return mixed
     */
    private function call_private(string $name, array $args = []) {
        $method = $this->ref->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($this->connector, $args);
    }

    /**
     * Test parse_tags accepts valid prefixed tags and strips the prefix.
     *
     * parse_tags() speichert nur den Wert (ohne Praefix), damit KI-Tags
     * mit manuell gesetzten Moodle-Tags uebereinstimmen und die Suche greift.
     *
     * @covers \block_kursfilter\ai_connector
     */
    public function test_parse_tags_accepts_valid_tags(): void {
        $result = $this->call_private('parse_tags', ['schulform:Gymnasium, fach:Mathematik, niveaustufe:Oberstufe']);
        // Nur der Wert ohne Praefix wird gespeichert.
        $this->assertContains('Gymnasium', $result);
        $this->assertContains('Mathematik', $result);
        $this->assertContains('Oberstufe', $result);
        // Praefix darf nicht im Ergebnis stehen.
        $this->assertNotContains('schulform:Gymnasium', $result);
        $this->assertNotContains('fach:Mathematik', $result);
    }

    /**
     * Test parse_tags rejects unknown prefixes.
     *
     * @covers \block_kursfilter\ai_connector
     */
    public function test_parse_tags_rejects_unknown_prefix(): void {
        $result = $this->call_private('parse_tags', ['unknown:Something, fach:Deutsch']);
        $this->assertNotContains('Something', $result);
        $this->assertContains('Deutsch', $result);
    }

    /**
     * Test parse_tags rejects values that are too short.
     *
     * @covers \block_kursfilter\ai_connector
     */
    public function test_parse_tags_rejects_too_short_value(): void {
        $result = $this->call_private('parse_tags', ['fach:A']);
        $this->assertEmpty($result);
    }

    /**
     * Test parse_tags handles empty input.
     *
     * @covers \block_kursfilter\ai_connector
     */
    public function test_parse_tags_handles_empty_input(): void {
        $result = $this->call_private('parse_tags', ['']);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test parse_tags deduplicates tags.
     *
     * @covers \block_kursfilter\ai_connector
     */
    public function test_parse_tags_deduplicates(): void {
        $result = $this->call_private('parse_tags', ['fach:Mathematik, fach:Mathematik']);
        $this->assertCount(1, $result);
    }

    /**
     * Test build_prompt contains course data.
     *
     * @covers \block_kursfilter\ai_connector
     */
    public function test_build_prompt_contains_course_data(): void {
        $prompt = $this->call_private('build_prompt', [
            'Grundlagen der Mathematik',
            'MATH01',
            'Einführung in die Mengenlehre',
            'Naturwissenschaften',
            ['Grundschule', 'Gymnasium'],
            ['Mathematik', 'Deutsch'],
            ['Klasse 1-4', 'Oberstufe'],
        ]);

        $this->assertStringContainsString('Grundlagen der Mathematik', $prompt);
        $this->assertStringContainsString('MATH01', $prompt);
        $this->assertStringContainsString('Mengenlehre', $prompt);
        $this->assertStringContainsString('schulform:', $prompt);
        $this->assertStringContainsString('fach:', $prompt);
        $this->assertStringContainsString('niveaustufe:', $prompt);
    }

    /**
     * Test suggest_tags returns empty array when no API key is configured.
     *
     * Backend wird auf 'claude' gesetzt, aber ohne API-Key, damit sofort
     * ein leeres Array zurueckgegeben wird – ohne HTTP-Request und ohne
     * mtrace()-Output, der PHPUnit --fail-on-warning ausloesen wuerde.
     *
     * @covers \block_kursfilter\ai_connector::suggest_tags
     */
    public function test_suggest_tags_returns_array_on_backend_failure(): void {
        // Claude-Backend ohne API-Key: gibt '' zurueck, kein HTTP-Call, kein Output.
        set_config('ai_backend', 'claude', 'block_kursfilter');
        set_config('ai_claude_apikey', '', 'block_kursfilter');
        $connector = new ai_connector();

        $result = $connector->suggest_tags(
            'Test course',
            'TEST01',
            '<p>Lernmaterialien für die Schule.</p>',
            'Allgemein'
        );
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
