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
 * Filter kinds (Filterarten) and their filter values (Filterwerte).
 *
 * Each kind is a search parameter (schooltype, subject, level) and is backed by one admin setting
 * holding one value per line. This class is the single reader for these lists.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_values {
    /** Filter kind (search parameter) => admin setting name. */
    const SETTINGS = [
        'schooltype' => 'schooltypes',
        'subject' => 'subjects',
        'level' => 'levels',
    ];

    /**
     * All filter kinds.
     *
     * @return string[]
     */
    public static function kinds(): array {
        return array_keys(self::SETTINGS);
    }

    /**
     * Admin setting that holds the values of a filter kind.
     *
     * @param string $kind Filter kind.
     * @return string Setting name.
     */
    public static function setting(string $kind): string {
        return self::SETTINGS[$kind];
    }

    /**
     * Cleaned filter values of a filter kind, one per line in the setting.
     *
     * @param string $kind Filter kind.
     * @return string[] Non-empty, cleaned values in their configured order.
     */
    public static function values(string $kind): array {
        $lines = explode("\n", (string)get_config('block_kursfilter', self::setting($kind)));
        $values = array_map(fn($line) => clean_param(trim($line), PARAM_TEXT), $lines);
        return array_values(array_filter($values, 'strlen'));
    }

    /**
     * Find the configured spelling of a value, ignoring case and surrounding spaces.
     *
     * @param string $kind Filter kind.
     * @param string $value Value as given by a user or a model.
     * @return string|null Value in its configured spelling, or null if the kind has no such value.
     */
    public static function match(string $kind, string $value): ?string {
        $key = \core_text::strtolower(trim($value));
        foreach (self::values($kind) as $configured) {
            if (\core_text::strtolower($configured) === $key) {
                return $configured;
            }
        }
        return null;
    }
}
