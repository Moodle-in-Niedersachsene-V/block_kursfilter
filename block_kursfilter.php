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
 * Block kursfilter main class.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Kursfilter block class.
 */
class block_kursfilter extends block_base {
    /**
     * Initialise the block.
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_kursfilter');
    }

    /**
     * Applicable formats.
     */
    public function applicable_formats(): array {
        return [
            'site-index'  => true,
            'course-view' => true,
            'my'          => true,
        ];
    }

    /**
     * Block has global configuration.
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Only one instance allowed.
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Return block content.
     */
    public function get_content(): stdClass {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $this->page->requires->js_call_amd('block_kursfilter/filter', 'init', [
            [
                'blockid'   => (int)$this->instance->id,
                'contextid' => (int)$this->page->context->id,
            ],
        ]);

        $renderer = $this->page->get_renderer('block_kursfilter');
        $this->content->text   = $renderer->render_block((int)$this->instance->id);
        $this->content->footer = '';

        return $this->content;
    }
}
