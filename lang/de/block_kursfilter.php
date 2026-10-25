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
$string['error_ai_answer_invalid']     = 'Die Antwort der KI ist nicht das erwartete JSON: {$a}';
$string['error_ai_personal_data'] = 'Die KI hat mögliche personenbezogene Daten gemeldet. Entfernen Sie personenbezogene Daten aus den Kurstexten, bevor Sie die KI-Verschlagwortung erneut starten.';
$string['error_ai_request_failed']     = 'Die KI-Anfrage ist fehlgeschlagen: {$a}';
$string['error_ai_tagging_failed']     = 'Die KI-Verschlagwortung ist für {$a->failed} von {$a->total} Kursen fehlgeschlagen; die Ursachen stehen im Task-Protokoll.';
$string['error_ai_unavailable']        = 'Die KI-Verschlagwortung ist aktiviert, aber kein Backend ist nutzbar: Entweder bietet kein KI-Anbieter die Aktion „Text generieren“ an (Website-Administration > Allgemein > KI) oder der Claude-API-Schlüssel fehlt.';
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
$string['preview_in_use']             = 'Für diese IP-Adresse läuft bereits eine Vorschau. Bitte zuerst dort abmelden.';
$string['preview_title']               = 'Kurs wie eine Lehrkraft ohne Bearbeitungsrecht ansehen';
$string['privacy:metadata'] = 'Der Kursfilter speichert pseudonyme Bewertungen und ein Browser-Cookie. Bewertungsereignisse können Bewertungen im Moodle-Protokoll mit Nutzerkonto und IP-Adresse verknüpfen. Die KI-Verschlagwortung sendet gefilterte Kursinhalte über core_ai an den eingerichteten Anbieter; der Filter garantiert keine Anonymisierung.';
$string['privacy:metadata:ai'] = 'Moodle core_ai verarbeitet und protokolliert Anfragen und sendet den Prompt an den eingerichteten, gegebenenfalls externen KI-Anbieter.';
$string['privacy:metadata:ai:contextid'] = 'Der Kontext des zu verschlagwortenden Kurses.';
$string['privacy:metadata:ai:prompt'] = 'Kursname, Kurzname, Kursbereich und gekürzte Beschreibung ohne E-Mail-Adressen und erkennbare Namensmuster sowie erlaubte Filterwerte. Andere personenbezogene Inhalte können verbleiben.';
$string['privacy:metadata:ai:userid'] = 'Das Administratorkonto, unter dem core_ai die geplante KI-Anfrage protokolliert.';
$string['privacy:metadata:claude'] = 'Optionales direktes Claude-Backend: Der Prompt wird an die Anthropic-API (api.anthropic.com) gesendet, außerhalb des KI-Subsystems von Moodle und dessen Protokollierung.';
$string['privacy:metadata:claude:prompt'] = 'Kursname, Kurzname, Kursbereich und gekürzte Beschreibung ohne E-Mail-Adressen und erkennbare Namensmuster sowie erlaubte Filterwerte. Andere personenbezogene Inhalte können verbleiben.';
$string['privacy:metadata:cookie'] = 'Das Cookie kf_rater_id wird ein Jahr im Browser des Besuchers gespeichert und bei Wiederkehr verlängert, um vorhandene Bewertungen zu erkennen.';
$string['privacy:metadata:logs'] = 'Moodle-Logstores speichern Bewertungsereignisse mit Nutzer-ID, IP-Adresse und Verweis auf die Bewertungszeile.';
$string['privacy:metadata:logs:ip'] = 'Die vom Logstore erfasste IP-Adresse.';
$string['privacy:metadata:logs:objectid'] = 'Die ID der Bewertungszeile, die das Ereignis mit der pseudonymen Bewertung verknüpft.';
$string['privacy:metadata:logs:other'] = 'Die abgegebene Sternzahl.';
$string['privacy:metadata:logs:userid'] = 'Das bewertende Konto oder das Pool-Konto einer Vorschau.';
$string['privacy:metadata:ratings'] = 'Bewertungen mit pseudonymer Browserkennung; Bewertungsereignisse können sie einem Nutzerkonto zuordnen.';
$string['privacy:metadata:ratings:cookiehash'] = 'Zufällige SHA-256-Browserkennung, die im Cookie und in den Bewertungen steht.';
$string['privacy:metadata:ratings:courseid'] = 'Der bewertete Kurs.';
$string['privacy:metadata:ratings:stars'] = 'Die Bewertung mit einem bis fünf Sternen.';
$string['privacy:metadata:ratings:timecreated'] = 'Der Zeitpunkt der Bewertung.';
$string['ratelimitexceeded']           = 'Zu viele Suchanfragen. Bitte {$a->seconds} Sekunden warten.';
$string['rating_done']                 = 'Bewertet';
$string['rating_star']                 = 'Mit {$a} von 5 Sternen bewerten';
$string['results_count']               = 'Gefundene Kurse: {$a}';
$string['results_none']                = 'Keine Kurse gefunden.';
$string['role_pool_description']       = 'Rolle der Pool-Konten: wie Trainer/in ohne Bearbeitungsrecht, aber ohne Zugriff auf Teilnehmerliste, Nutzeridentität und Noten.';
$string['role_pool_name']              = 'Kursfilter-Pool-Rolle';
$string['settings_ai_autoapply']       = 'Tags automatisch übernehmen';
$string['settings_ai_autoapply_help']  = 'Wenn aktiviert, werden passende Werte sofort zu Kurs-Tags. Wenn deaktiviert, bleiben sie als Tag-Vorschläge zur Prüfung stehen.';
$string['settings_ai_backend'] = 'KI-Backend';
$string['settings_ai_backend_claude'] = 'Claude-API (direkt)';
$string['settings_ai_backend_coreai'] = 'KI-Subsystem von Moodle (core_ai)';
$string['settings_ai_backend_help'] = 'KI-Subsystem von Moodle: nutzt den unter Website-Administration > Allgemein > KI eingerichteten Anbieter. Claude-API: sendet den Prompt mit dem folgenden Schlüssel direkt an die Anthropic-API, bis ein KI-Anbieter in Moodle zur Verfügung steht.';
$string['settings_ai_batch_size']      = 'Kurse pro Lauf';
$string['settings_ai_batch_size_help'] = 'Anzahl der Kurse, die pro Lauf der geplanten Aufgabe verschlagwortet werden (Standard: 20).';
$string['settings_ai_claude_apikey'] = 'Claude-API-Schlüssel';
$string['settings_ai_claude_apikey_help'] = 'API-Schlüssel des Anthropic-Kontos des Vereins. Wird nur mit dem Backend „Claude-API (direkt)“ verwendet.';
$string['settings_ai_claude_model'] = 'Claude-Modell';
$string['settings_ai_claude_model_help'] = 'Modellname für die Claude-API (Standard: claude-haiku-4-5).';
$string['settings_ai_desc']            = 'Die geplante Aufgabe fragt den in Moodle eingerichteten KI-Anbieter (Aktion „Text generieren“) einmal je öffentlichem Kurs, welche Filterwerte passen. Übermittelt werden nur Kursname, Kurzname, Kursbereich und der Anfang der Kursbeschreibung. Übernommen werden nur Werte aus den Listen der Filterwerte.';
$string['settings_ai_enabled']         = 'KI-Verschlagwortung aktivieren';
$string['settings_ai_enabled_help']    = 'Erlaubt der geplanten Aufgabe „KI-Verschlagwortung der Kurse“ zu laufen; die Aufgabe muss zusätzlich in den geplanten Aufgaben aktiviert sein.';
$string['settings_ai_heading']         = 'KI-Verschlagwortung';
$string['settings_backup_desc']        = 'Die geplante Aufgabe erzeugt täglich um 02:00 Uhr je öffentlichem Kurs eine Kurssicherung (.mbz) ohne Nutzerdaten. Besucher können sie in den Suchergebnissen herunterladen. Es bleibt nur die neueste Kurssicherung je Kurs erhalten.';
$string['settings_backup_heading']     = 'Kurssicherungen';
$string['settings_backup_userid']      = 'Nutzer-ID des Sicherungskontos';
$string['settings_backup_userid_help'] = 'ID des Kontos, unter dem die nächtlichen Kurssicherungen laufen; es braucht das Recht, Kurse zu sichern. Leer lassen, um den ersten Administrator zu verwenden.';
$string['settings_filters_desc']       = 'Diese Werte erscheinen als Chips im Block. Ein Kurs passt zu einem Wert, wenn er einen Kurs-Tag mit genau diesem Wert trägt; Groß- und Kleinschreibung spielt keine Rolle.';
$string['settings_rootcategory']       = 'Wurzel-Kursbereich';
$string['settings_rootcategory_help']  = 'Name oder ID des Kursbereichs, auf den die automatische Verschlagwortung und die automatischen Kurssicherungen beschränkt werden sollen (einschließlich aller Unterkursbereiche). Leer lassen, um die gesamte Moodle-Instanz zu durchsuchen.';
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

$string['task_tag_courses']            = 'KI-Verschlagwortung der Kurse für den Kursfilter';
