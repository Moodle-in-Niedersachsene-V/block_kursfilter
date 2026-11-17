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
 * Tag backend through Moodle's AI subsystem (core_ai, action generate_text).
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tag_backend_coreai implements tag_backend {
    /**
     * Whether the core_ai generate_text action is available.
     *
     * @return bool
     */
    public function is_available(): bool {
        return \core\di::get(\core_ai\manager::class)->is_action_available(\core_ai\aiactions\generate_text::class);
    }

    /**
     * Send the prompt through core_ai and return the generated text.
     *
     * @param string $prompt Prompt.
     * @param int $contextid Course context the request is logged for.
     * @param int $userid User the request is logged for.
     * @return string Generated text.
     * @throws \moodle_exception error_ai_request_failed if the request fails.
     */
    public function ask(string $prompt, int $contextid, int $userid): string {
        $action = new \core_ai\aiactions\generate_text(contextid: $contextid, userid: $userid, prompttext: $prompt);
        $response = \core\di::get(\core_ai\manager::class)->process_action($action);
        if (!$response->get_success()) {
            throw new \moodle_exception('error_ai_request_failed', 'block_kursfilter', '', $response->get_errormessage());
        }
        return (string)($response->get_response_data()['generatedcontent'] ?? '');
    }
}
