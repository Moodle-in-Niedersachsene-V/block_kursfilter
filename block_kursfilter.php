<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Blockklasse des Plugins block_kursfilter.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @author     Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Blockklasse des Kursfilters.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_kursfilter extends block_base {
    /**
     * Initialisiert den Block und setzt den Titel.
     *
     * @return void
     */
    public function init(): void {
        $this->title = get_string('pluginname', 'block_kursfilter');
    }

    /**
     * Legt fest, auf welchen Seitentypen der Block verwendet werden darf.
     *
     * @return array Seitentypen mit Erlaubnis-Kennzeichen.
     */
    public function applicable_formats(): array {
        return [
            'site-index' => true,
            'course-view' => true,
            'my' => true,
        ];
    }

    /**
     * Gibt an, dass der Block globale Einstellungen besitzt.
     *
     * @return bool Immer true.
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Gibt an, ob der Block mehrfach auf einer Seite platziert werden darf.
     *
     * @return bool Immer false.
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Erzeugt den Inhalt des Blocks.
     *
     * @return stdClass Inhaltsobjekt mit gerendertem Text.
     */
    public function get_content(): stdClass {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        $this->page->requires->js_call_amd(
            'block_kursfilter/filter',
            'init',
            [
                [
                    'blockid' => (int)$this->instance->id,
                    'contextid' => (int)$this->page->context->id,
                ],
            ]
        );

        $renderer = $this->page->get_renderer('block_kursfilter');
        $this->content->text = $renderer->render_block((int)$this->instance->id);
        $this->content->footer = '';

        return $this->content;
    }
}
