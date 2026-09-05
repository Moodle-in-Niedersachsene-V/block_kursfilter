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
        // Kursbereiche (Kategorien).
        $categories   = core_course_category::make_categories_list('', 0, ' / ');
        $kursbereiche = [['value' => '', 'label' => get_string('filter_all', 'block_kursfilter')]];
        foreach ($categories as $id => $name) {
            $kursbereiche[] = ['value' => (string)$id, 'label' => $name];
        }

        // Schulformen aus Admin-Settings.
        $schulformraw = get_config('block_kursfilter', 'schulformen')
            ?? "Grundschule\nHauptschule\nRealschule\nGymnasium\nGesamtschule\nBerufsschule";
        $schulformen  = array_values(array_filter(array_map(function ($v) {
            return clean_param(trim($v), PARAM_TEXT);
        }, explode("\n", $schulformraw))));

        // Faecher aus Admin-Settings.
        $faecherraw = get_config('block_kursfilter', 'faecher')
            ?? "Mathematik\nDeutsch\nEnglisch\nNaturwissenschaften\nGeschichte\nKunst\nMusik\nSport";
        $faecher    = array_values(array_filter(array_map(function ($v) {
            return clean_param(trim($v), PARAM_TEXT);
        }, explode("\n", $faecherraw))));

        // Niveaustufen aus Admin-Settings.
        $niveauraw  = get_config('block_kursfilter', 'niveaustufen')
            ?? "Klasse 1-4\nKlasse 5-6\nKlasse 7-9\nKlasse 10\nOberstufe";
        $niveaus    = array_values(array_filter(array_map(function ($v) {
            return clean_param(trim($v), PARAM_TEXT);
        }, explode("\n", $niveauraw))));

        $templatedata = [
            'blockid'      => $blockid,
            'kursbereiche' => $kursbereiche,
            'schulformen'  => $schulformen,
            'faecher'      => $faecher,
            'niveaustufen' => $niveaus,
        ];

        return $this->render_from_template('block_kursfilter/block', $templatedata);
    }
}
