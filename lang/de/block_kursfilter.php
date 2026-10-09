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
 * German language strings for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['back_to_search']              = 'Zurück zur Suche';
$string['backup_not_available']        = 'Für diesen Kurs ist noch keine Kurssicherung vorhanden. Bitte später erneut versuchen.';
$string['btn_download_backup']         = 'Kurssicherung herunterladen (.mbz)';
$string['btn_preview']                 = 'Kurs ansehen';
$string['btn_reset']                   = 'Zurücksetzen';
$string['course_not_found']            = 'Dieser Kurs wurde nicht gefunden oder ist nicht öffentlich zugänglich.';
$string['error_backup_not_created']    = 'Die Sicherung von Kurs {$a} hat keine Datei erzeugt.';
$string['error_backups_failed']        = '{$a->failed} von {$a->total} Kurssicherungen sind fehlgeschlagen; die Ursachen stehen im Task-Protokoll.';
$string['error_preview_needs_post']    = 'Eine Vorschau lässt sich nur über die Vorschau-Schaltfläche des Kursfilters starten.';
$string['error_rating_failed']         = 'Die Bewertung konnte nicht gespeichert werden.';
$string['error_search_failed']         = 'Die Suche ist fehlgeschlagen.';
$string['event_course_rated']          = 'Kurs bewertet';
$string['filter_all']                  = '– Alle –';
$string['hint_setfilter']              = 'Filter setzen, um Kurse zu suchen.';
$string['kursfilter:addinstance']      = 'Block „Kursfilter" hinzufügen';
$string['kursfilter:myaddinstance']    = 'Block „Kursfilter" zum Dashboard hinzufügen';
$string['kursfilter:search']           = 'Kurse mit dem Kursfilter suchen';
$string['label_category']              = 'Kursbereich';
$string['label_level']                 = 'Niveaustufe';
$string['label_schooltype']            = 'Schulform';
$string['label_searchterm']            = 'Suchbegriff';
$string['label_subject']               = 'Fach';
$string['placeholder_searchterm']      = 'Kursname oder Beschreibung suchen …';
$string['pluginname']                  = 'Kursfilter';
$string['pool_account_description']    = 'Pool-Konto des Kursfilter-Blocks für Kursvorschauen.';
$string['pool_account_firstname']      = 'Pool-Konto';
$string['pool_full']                   = 'Aktuell sind alle Pool-Konten belegt. Bitte versuchen Sie es in einigen Minuten erneut.';
$string['preview_title']               = 'Kurs wie eine Lehrkraft ohne Bearbeitungsrecht ansehen';
$string['privacy:metadata']            = 'Der Kursfilter speichert Bewertungen nur mit einem zufälligen Browser-Cookie, das mit keinem Nutzerkonto verknüpft ist; personenbezogene Daten von Nutzer/innen speichert er nicht.';
$string['ratelimitexceeded']           = 'Zu viele Suchanfragen. Bitte {$a->seconds} Sekunden warten.';
$string['rating_done']                 = 'Bewertet';
$string['rating_star']                 = 'Mit {$a} von 5 Sternen bewerten';
$string['results_count']               = 'Gefundene Kurse: {$a}';
$string['results_none']                = 'Keine Kurse gefunden.';
$string['role_pool_description']       = 'Rolle der Pool-Konten: wie Trainer/in ohne Bearbeitungsrecht, aber ohne Zugriff auf Teilnehmerliste, Nutzeridentität und Noten.';
$string['role_pool_name']              = 'Kursfilter-Pool-Rolle';
$string['settings_backup_desc']        = 'Die geplante Aufgabe erzeugt täglich um 02:00 Uhr je öffentlichem Kurs eine Kurssicherung (.mbz) ohne Nutzerdaten. Besucher können sie in den Suchergebnissen herunterladen. Es bleibt nur die neueste Kurssicherung je Kurs erhalten.';
$string['settings_backup_heading']     = 'Kurssicherungen';
$string['settings_backup_userid']      = 'Nutzer-ID des Sicherungskontos';
$string['settings_backup_userid_help'] = 'ID des Kontos, unter dem die nächtlichen Kurssicherungen laufen; es braucht das Recht, Kurse zu sichern. Leer lassen, um den ersten Administrator zu verwenden.';
$string['settings_filters_desc']       = 'Diese Werte erscheinen als Chips im Block. Ein Kurs passt zu einem Wert, wenn er einen Kurs-Tag mit genau diesem Wert trägt; Groß- und Kleinschreibung spielt keine Rolle.';
$string['settings_filters_heading']    = 'Filterwerte';
$string['settings_levels']             = 'Niveaustufen';
$string['settings_levels_help']        = 'Eine Niveaustufe pro Zeile, z. B. Klasse 5-6.';
$string['settings_pool_desc']          = 'Besucher sehen öffentliche Kurse in der Vorschau über ein Pool-Konto an. Fehlende Pool-Konten legt die geplante Aufgabe an (täglich um 03:00 Uhr).';
$string['settings_pool_heading']       = 'Pool-Konten';
$string['settings_poolsize']           = 'Anzahl der Pool-Konten';
$string['settings_poolsize_help']      = 'Anzahl der Pool-Konten und damit gleichzeitiger Vorschauen (Standard: 10, max. 50).';
$string['settings_resultlimit']        = 'Max. Ergebnisse';
$string['settings_resultlimit_help']   = 'Maximale Anzahl Kurse pro Suchanfrage (Standard: 100, max. 200).';
$string['settings_schooltypes']        = 'Schulformen';
$string['settings_schooltypes_help']   = 'Eine Schulform pro Zeile, z. B. Gymnasium.';
$string['settings_subjects']           = 'Fächer';
$string['settings_subjects_help']      = 'Ein Fach pro Zeile, z. B. Mathematik.';
$string['task_backup_courses']         = 'Kurssicherungen für den Kursfilter erzeugen';
$string['task_setup_pool']             = 'Kursfilter-Pool-Konten anlegen und in öffentliche Kurse einschreiben';
