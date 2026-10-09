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

$string['back_to_search']                 = 'Zurück zur Suche';
$string['backup_not_available']           = 'Für diesen Kurs ist noch keine Sicherung vorhanden. Bitte später erneut versuchen.';
$string['btn_export']                     = 'Herunterladen';
$string['btn_opencourse']                 = 'Zum Kurs';
$string['btn_reset']                      = 'Zurücksetzen';
$string['course_not_found']               = 'Dieser Kurs wurde nicht gefunden oder ist nicht öffentlich zugänglich.';
$string['error_backup_not_created']       = 'Die Sicherung von Kurs {$a} hat keine Datei erzeugt.';
$string['error_backups_failed']           = '{$a->failed} von {$a->total} Kurssicherungen sind fehlgeschlagen; die Ursachen stehen im Task-Protokoll.';
$string['error_preview_needs_post']       = 'Eine Vorschau lässt sich nur über die Vorschau-Schaltfläche des Kursfilters starten.';
$string['event_course_rated']             = 'Kurs bewertet';
$string['filter_all']                     = '– Alle –';
$string['hint_setfilter']                 = 'Filter setzen, um Kurse zu suchen.';
$string['kursfilter:addinstance']          = 'Block „Kursfilter" hinzufügen';
$string['kursfilter:myaddinstance']        = 'Block „Kursfilter" zum Dashboard hinzufügen';
$string['kursfilter:search']              = 'Kurse mit dem Kursfilter suchen';
$string['label_fach']                     = 'Fach';
$string['label_kursbereich']              = 'Kursbereich';
$string['label_kursname']                 = 'Suchbegriff';
$string['label_niveaustufe']              = 'Niveaustufe';
$string['label_schulform']                = 'Schulform';
$string['placeholder_kursname']           = 'Kursname oder Beschreibung suchen …';
$string['pluginname']                     = 'Kursfilter';
$string['pool_account_description']       = 'Pool-Konto des Kursfilter-Blocks für Kursvorschauen.';
$string['pool_account_firstname']         = 'Pool-Konto';
$string['pool_full']                      = 'Aktuell sind alle Pool-Konten belegt. Bitte versuchen Sie es in einigen Minuten erneut.';
$string['ratelimitexceeded']              = 'Zu viele Suchanfragen. Bitte {$a->seconds} Sekunden warten.';
$string['rating_already_done']            = 'Du hast diesen Kurs bereits bewertet.';
$string['rating_saved']                   = 'Bewertung gespeichert.';
$string['role_pool_description']          = 'Rolle der Pool-Konten: wie Trainer/in ohne Bearbeitungsrecht, aber ohne Zugriff auf Teilnehmerliste, Nutzeridentität und Noten.';
$string['role_pool_name']                 = 'Kursfilter-Pool-Rolle';
$string['settings_backup_adminid']        = 'Nutzer-ID für Kurssicherungen';
$string['settings_backup_adminid_help']   = 'Nutzer-ID eines Administrators, der für die nächtlichen Kurssicherungen verwendet wird. Leer lassen, um den ersten Site-Administrator zu verwenden.';
$string['settings_backup_desc']           = 'Der Scheduled Task erzeugt täglich um 02:00 Uhr eine Kurssicherung (.mbz) pro Kurs. Gäste können diese Datei über den Download-Button herunterladen. Es wird immer nur eine Datei pro Kurs gespeichert.';
$string['settings_backup_heading']        = 'Kurssicherungen (Gäste-Download)';
$string['settings_faecher']               = 'Fächer';
$string['settings_faecher_heading']       = 'Fächer';
$string['settings_faecher_help']          = 'Ein Fach pro Zeile. Am Kurs muss der Tag „fach:Mathematik" o. ä. gesetzt sein.';
$string['settings_niveaustufen']          = 'Niveaustufen';
$string['settings_niveaustufen_desc']     = 'Eine Stufe pro Zeile. Am Kurs muss der Tag „niveaustufe:Klasse 10" o. ä. gesetzt sein.';
$string['settings_niveaustufen_heading']  = 'Niveaustufen';
$string['settings_niveaustufen_help']     = 'Eine Stufe pro Zeile. Beliebig erweiterbar.';
$string['settings_pool_desc']             = 'Besucher sehen öffentliche Kurse in der Vorschau über ein Pool-Konto an. Fehlende Pool-Konten legt die geplante Aufgabe an (täglich um 03:00 Uhr).';
$string['settings_pool_heading']          = 'Pool-Konten';
$string['settings_poolsize']              = 'Anzahl der Pool-Konten';
$string['settings_poolsize_help']         = 'Anzahl der Pool-Konten und damit gleichzeitiger Vorschauen (Standard: 10, max. 50).';
$string['settings_resultlimit']           = 'Max. Ergebnisse';
$string['settings_resultlimit_help']      = 'Maximale Anzahl Kurse pro Suchanfrage (Standard: 100, max. 200).';
$string['settings_schulformen']           = 'Schulformen';
$string['settings_schulformen_desc']      = 'Diese Werte erscheinen als Chips im Block. Jede Schulform wird als Tag „schulform:Wert" an Kursen erwartet.';
$string['settings_schulformen_heading']   = 'Schulformen';
$string['settings_schulformen_help']      = 'Eine Schulform pro Zeile, z. B. Gymnasium. Am Kurs muss dann der Tag „schulform:Gymnasium" gesetzt sein.';
$string['task_backup_courses']            = 'Kurssicherungen für Kursfilter-Block erzeugen';
$string['task_setup_pool']                = 'Kursfilter-Pool-Konten anlegen und in öffentliche Kurse einschreiben';
