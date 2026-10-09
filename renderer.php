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
 * Renderer for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Kursfilter block renderer.
 */
class block_kursfilter_renderer extends plugin_renderer_base {
    /**
     * Render the filter block.
     *
     * @param int $blockid Block instance ID.
     * @return string HTML output.
     */
    public function render_block(int $blockid): string {
        $categories = [['value' => '', 'label' => get_string('filter_all', 'block_kursfilter')]];
        foreach (core_course_category::make_categories_list('', 0, ' / ') as $id => $name) {
            $categories[] = ['value' => (string)$id, 'label' => $name];
        }

        return $this->render_from_template('block_kursfilter/block', [
            'blockid'     => $blockid,
            'categories'  => $categories,
            'schooltypes' => self::filter_values('schooltypes'),
            'subjects'    => self::filter_values('subjects'),
            'levels'      => self::filter_values('levels'),
        ]);
    }

    /**
     * Read the filter values of a setting, one per line.
     *
     * @param string $setting Setting name.
     * @return string[] Non-empty values.
     */
    private static function filter_values(string $setting): array {
        $lines = explode("\n", (string)get_config('block_kursfilter', $setting));
        return array_values(array_filter(array_map(fn($line) => clean_param(trim($line), PARAM_TEXT), $lines), 'strlen'));
    }
}
