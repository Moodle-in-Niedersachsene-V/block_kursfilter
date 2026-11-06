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
 * Seam for the AI backend that answers tag requests (ADR 0006, ADR 0007).
 *
 * Implementations: tag_backend_coreai (Moodle's AI subsystem) and tag_backend_claude (direct Claude API).
 * The implementation is chosen through Moodle's DI container.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface tag_backend {
    /**
     * Whether this backend is configured and can answer.
     *
     * @return bool
     */
    public function is_available(): bool;

    /**
     * Send the prompt and return the generated text.
     *
     * @param string $prompt Prompt.
     * @param int $contextid Course context the request is logged for (used by core_ai only).
     * @param int $userid User the request is logged for (used by core_ai only).
     * @return string Generated text.
     * @throws \moodle_exception error_ai_request_failed if the request fails.
     */
    public function ask(string $prompt, int $contextid, int $userid): string;
}
