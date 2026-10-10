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
$string['block_kursfilter:addinstance']   = 'Block „Kursfilter" hinzufügen';
$string['block_kursfilter:myaddinstance'] = 'Block „Kursfilter" zum Dashboard hinzufügen';
$string['btn_export']                     = 'Herunterladen';
$string['btn_opencourse']                 = 'Zum Kurs';
$string['btn_reset']                      = 'Zurücksetzen';
$string['course_not_found']               = 'Dieser Kurs wurde nicht gefunden oder ist nicht öffentlich zugänglich.';
$string['filter_all']                     = '– Alle –';
$string['hint_setfilter']                 = 'Filter setzen, um Kurse zu suchen.';
$string['label_fach']                     = 'Fach';
$string['label_kursbereich']              = 'Kursbereich';
$string['label_kursname']                 = 'Suchbegriff';
$string['label_niveaustufe']              = 'Niveaustufe';
$string['label_schulform']                = 'Schulform';
$string['placeholder_kursname']           = 'Kursname oder Beschreibung suchen …';
$string['pluginname']                     = 'Kursfilter';
$string['pool_full']                      = 'Aktuell sind alle Kursbesucher-Zugänge belegt. Bitte versuchen Sie es in einigen Minuten erneut.';
$string['privacy:metadata']               = 'Dieses Plugin speichert keine personenbezogenen Daten. Kursbewertungen werden ausschließlich über einen anonymen Cookie-Hash gespeichert, der keiner Person zugeordnet werden kann.';
$string['ratelimitexceeded']              = 'Zu viele Suchanfragen. Bitte {$a->seconds} Sekunden warten.';
$string['rating_already_done']            = 'Du hast diesen Kurs bereits bewertet.';
$string['rating_saved']                   = 'Bewertung gespeichert.';
$string['settings_ai_autoapply']          = 'Tags automatisch anwenden';
$string['settings_ai_autoapply_help']     = 'Wenn aktiviert, werden KI-Vorschläge direkt als Tags gespeichert. Wenn deaktiviert, werden die Vorschläge zur manuellen Überprüfung gespeichert.';
$string['settings_ai_backend']            = 'KI-Backend';
$string['settings_ai_backend_claude']     = 'Anthropic Claude API';
$string['settings_ai_backend_help']       = 'Wählen Sie das KI-Backend für die automatische Kursverschlagwortung.';
$string['settings_ai_backend_ollama']     = 'Ollama (lokal)';
$string['settings_ai_batch_size']         = 'Batch-Größe';
$string['settings_ai_batch_size_help']    = 'Anzahl der Kurse, die pro Cron-Lauf verschlagwortet werden (Standard: 20).';
$string['settings_ai_claude_apikey']      = 'Claude API-Schlüssel';
$string['settings_ai_claude_apikey_help'] = 'API-Schlüssel für die Anthropic Claude API. Wird sicher in der Datenbank gespeichert.';
$string['settings_ai_claude_desc']        = 'Einstellungen für die Anthropic Claude API. Es werden keine Nutzerdaten übermittelt – nur Kursname, Kurzname, Kategorie und ein gekürzter Beschreibungstext.';
$string['settings_ai_claude_heading']     = 'Claude API';
$string['settings_ai_claude_model']       = 'Claude Modell';
$string['settings_ai_claude_model_help']  = 'Modellbezeichnung, z. B. claude-haiku-4-5-20251001.';
$string['settings_ai_desc']               = 'Das Plugin kann Kurse automatisch verschlagworten. Es werden ausschließlich Kursname, Kurzname, Kategorie und ein gekürzter Beschreibungstext übermittelt. Keine Nutzerdaten, keine Lehrkräftenamen, keine Einschreibedaten.';
$string['settings_ai_enabled']            = 'KI-Verschlagwortung aktivieren';
$string['settings_ai_enabled_help']       = 'Aktiviert den Scheduled Task zur automatischen KI-Verschlagwortung von Kursen.';
$string['settings_ai_heading']            = 'KI-Verschlagwortung';
$string['settings_ai_ollama_heading']     = 'Ollama (lokal)';
$string['settings_ai_ollama_model']       = 'Ollama Modell';
$string['settings_ai_ollama_model_help']  = 'Modellbezeichnung, z. B. gemma3:4b.';
$string['settings_ai_ollama_url']         = 'Ollama URL';
$string['settings_ai_ollama_url_help']    = 'Basis-URL des lokalen Ollama-Servers (Standard: http://localhost:11434).';
$string['settings_backup_desc']           = 'Der Scheduled Task erzeugt täglich um 02:00 Uhr eine Kurssicherung (.mbz) pro Kurs. Gäste können diese Datei über den Download-Button herunterladen. Es wird immer nur eine Datei pro Kurs gespeichert.';
$string['settings_backup_heading']        = 'Kurssicherungen (Gäste-Download)';
$string['settings_backup_userid']         = 'Nutzer-ID für Kurssicherungen';
$string['settings_backup_userid_help']    = 'Nutzer-ID eines Administrators, der für die nächtlichen Kurssicherungen verwendet wird. Leer lassen, um den ersten Site-Administrator zu verwenden.';
$string['settings_levels']                = 'Niveaustufen';
$string['settings_levels_desc']           = 'Eine Stufe pro Zeile. Am Kurs muss der Tag „niveaustufe:Klasse 10" o. ä. gesetzt sein.';
$string['settings_levels_heading']        = 'Niveaustufen';
$string['settings_levels_help']           = 'Eine Stufe pro Zeile. Beliebig erweiterbar.';
$string['settings_pool_desc']             = 'Der Pool stellt anonyme Kursbesucher-Accounts bereit. Neue Nutzer werden beim nächsten Lauf des Scheduled Tasks (täglich 03:00 Uhr) automatisch angelegt und in alle Kurse eingeschrieben. Maximal 50 Nutzer.';
$string['settings_pool_heading']          = 'Gast-Pool-Nutzer';
$string['settings_poolsize']              = 'Anzahl Pool-Nutzer';
$string['settings_poolsize_help']         = 'Anzahl der Kursbesucher-Accounts (Standard: 10, max. 50). Nach dem Speichern werden fehlende Nutzer beim nächsten Cronjob-Lauf automatisch angelegt.';
$string['settings_resultlimit']           = 'Max. Ergebnisse';
$string['settings_resultlimit_help']      = 'Maximale Anzahl Kurse pro Suchanfrage (Standard: 100, max. 200).';
$string['settings_schooltypes']           = 'Schulformen';
$string['settings_schooltypes_desc']      = 'Diese Werte erscheinen als Chips im Block. Jede Schulform wird als Tag „schulform:Wert" an Kursen erwartet.';
$string['settings_schooltypes_heading']   = 'Schulformen';
$string['settings_schooltypes_help']      = 'Eine Schulform pro Zeile, z. B. Gymnasium. Am Kurs muss dann der Tag „schulform:Gymnasium" gesetzt sein.';
$string['settings_subjects']              = 'Fächer';
$string['settings_subjects_heading']      = 'Fächer';
$string['settings_subjects_help']         = 'Ein Fach pro Zeile. Am Kurs muss der Tag „fach:Mathematik" o. ä. gesetzt sein.';
$string['task_backup_courses']            = 'Kurssicherungen für Kursfilter-Block erzeugen';
$string['task_setup_pool']                = 'Kursfilter-Pool-Nutzer einrichten und in neue Kurse einschreiben';
$string['task_tag_courses']               = 'Kurse automatisch per KI verschlagworten';
