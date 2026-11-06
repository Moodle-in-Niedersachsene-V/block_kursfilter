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
 * Tag backend that calls the Anthropic Messages API directly (ADR 0007).
 *
 * Goes through Moodle's HTTP client, so proxy and blocked-host settings apply. Nothing but the prompt is sent.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_backend_claude implements tag_backend {
    /** Anthropic Messages API endpoint. */
    const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    /** Claude model used when none is configured. */
    const DEFAULT_MODEL = 'claude-haiku-4-5';

    /** Seconds before a request is given up. */
    const TIMEOUT = 30;

    /** @inheritDoc */
    public function is_available(): bool {
        return (string)get_config('block_kursfilter', 'ai_claude_apikey') !== '';
    }

    /** @inheritDoc */
    public function ask(string $prompt, int $contextid, int $userid): string {
        $model = (string)get_config('block_kursfilter', 'ai_claude_model') ?: self::DEFAULT_MODEL;
        try {
            $response = \core\di::get(\core\http_client::class)->post(self::ENDPOINT, [
                'headers' => [
                    'x-api-key' => (string)get_config('block_kursfilter', 'ai_claude_apikey'),
                    'anthropic-version' => '2023-06-01',
                ],
                'json' => [
                    'model' => $model,
                    'max_tokens' => 300,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                ],
                'timeout' => self::TIMEOUT,
                'http_errors' => false,
            ]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            throw new \moodle_exception('error_ai_request_failed', 'block_kursfilter', '', $e->getMessage());
        }

        $data = json_decode((string)$response->getBody(), true);
        if ($response->getStatusCode() >= 400 || !isset($data['content'][0]['text'])) {
            $message = $data['error']['message'] ?? ('HTTP ' . $response->getStatusCode());
            throw new \moodle_exception('error_ai_request_failed', 'block_kursfilter', '', $message);
        }
        return (string)$data['content'][0]['text'];
    }
}
