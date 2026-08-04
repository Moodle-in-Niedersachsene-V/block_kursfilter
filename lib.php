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
 * Bibliotheksfunktionen des Plugins block_kursfilter.
 *
 * @package    block_kursfilter
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @author     Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Datei-Download-Handler des Kursfilters.
 *
 * Das Plugin verwendet aktuell keine eigenen Dateibereiche.
 * Die Funktion ist ein Platzhalter fuer kuenftige Erweiterungen.
 *
 * @param stdClass $course Kursobjekt.
 * @param stdClass $cm Kursmodulobjekt.
 * @param context $context Kontext der Datei.
 * @param string $filearea Dateibereich.
 * @param array $args Restliche Pfadbestandteile.
 * @param bool $forcedownload Ob der Download erzwungen wird.
 * @param array $options Zusaetzliche Optionen fuer die Auslieferung.
 * @return void
 */
function block_kursfilter_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
): void {
    send_file_not_found();
}
