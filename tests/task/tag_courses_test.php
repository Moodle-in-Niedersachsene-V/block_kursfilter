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

namespace block_kursfilter\task;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the AI tagging task; core_ai runs for real, only the HTTP answer of the provider is a double.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(tag_courses::class)]
final class tag_courses_test extends \advanced_testcase {
    /** @var \GuzzleHttp\Handler\MockHandler Queue of provider HTTP answers. */
    private $mock;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('ai_enabled', 1, 'block_kursfilter');
        set_config('subjects', "Mathematik\nDeutsch", 'block_kursfilter');
    }

    /**
     * Set up an AI provider whose HTTP answers come from the mock queue.
     */
    private function provider(): void {
        \core\di::get(\core_ai\manager::class)->create_provider_instance(
            classname: '\aiprovider_ollama\provider',
            name: 'test',
            enabled: true,
            config: ['endpoint' => 'http://ollama.example:11434/'],
            actionconfig: [\core_ai\aiactions\generate_text::class => ['enabled' => true, 'settings' => [
                'model' => 'test',
                'systeminstruction' => get_string('action_generate_text_instruction', 'core_ai'),
            ]]],
        );
        ['mock' => $this->mock] = $this->get_mocked_http_client();
    }

    /**
     * Queue the model answer the provider returns next.
     *
     * @param string $answer Generated text.
     */
    private function answer(string $answer): void {
        $this->mock->append(new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'model' => 'test', 'response' => $answer, 'done' => true, 'done_reason' => 'stop',
            'prompt_eval_count' => 1, 'eval_count' => 1,
        ])));
    }

    /**
     * Course tags in the core course tag area.
     *
     * @param int $courseid Course ID.
     * @return string[] Tag names.
     */
    private function course_tags(int $courseid): array {
        return array_values(\core_tag_tag::get_item_tags_array('core', 'course', $courseid));
    }

    public function test_autoapply_adds_course_tags_and_keeps_existing_ones(): void {
        global $DB;
        $this->provider();
        set_config('ai_autoapply', 1, 'block_kursfilter');
        $course = $this->getDataGenerator()->create_course(['tags' => ['Projekt']]);
        $this->answer('{"subjects": ["Mathematik"]}');

        $this->expectOutputRegex('/Course ' . $course->id . ': applied/');
        (new tag_courses())->execute();

        $this->assertEqualsCanonicalizing(['Projekt', 'Mathematik'], $this->course_tags((int)$course->id));
        $this->assertSame('applied', $DB->get_field('block_kursfilter_tag_pending', 'status', ['courseid' => $course->id]));
    }

    public function test_without_autoapply_the_suggestion_waits_for_review_and_is_not_requested_again(): void {
        global $DB;
        $this->provider();
        $course = $this->getDataGenerator()->create_course();
        $this->answer('{"subjects": ["Deutsch"]}');

        $this->expectOutputRegex('/pending/');
        (new tag_courses())->execute();
        (new tag_courses())->execute();

        $this->assertSame([], $this->course_tags((int)$course->id));
        $record = $DB->get_record('block_kursfilter_tag_pending', ['courseid' => $course->id]);
        $this->assertSame(['pending', 'Deutsch'], [$record->status, $record->tags]);
        $this->assertSame(0, $this->mock->count());
    }

    public function test_failed_request_fails_the_task_and_leaves_the_course_for_the_next_run(): void {
        global $DB;
        $this->provider();
        $course = $this->getDataGenerator()->create_course();
        $this->mock->append(new Response(500, [], 'down'));

        $this->expectOutputRegex('/Course ' . $course->id . ': AI tagging failed/');
        try {
            (new tag_courses())->execute();
            $this->fail('The task must fail when a course fails.');
        } catch (\moodle_exception $e) {
            $this->assertSame('error_ai_tagging_failed', $e->errorcode);
        }
        $this->assertFalse($DB->record_exists('block_kursfilter_tag_pending', ['courseid' => $course->id]));
    }

    public function test_enabled_tagging_without_ai_provider_fails_loudly(): void {
        $this->getDataGenerator()->create_course();

        $this->expectExceptionMessageMatches('/no AI provider/');
        (new tag_courses())->execute();
    }

    public function test_disabled_tagging_sends_nothing(): void {
        global $DB;
        $this->provider();
        set_config('ai_enabled', 0, 'block_kursfilter');
        $this->getDataGenerator()->create_course();

        $this->expectOutputRegex('/disabled/');
        (new tag_courses())->execute();

        $this->assertSame(0, $DB->count_records('block_kursfilter_tag_pending'));
    }
}
