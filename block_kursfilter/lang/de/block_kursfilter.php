<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname']                    = 'Kursfilter';
$string['block_kursfilter:addinstance']  = 'Block „Kursfilter" hinzufügen';
$string['block_kursfilter:myaddinstance']= 'Block „Kursfilter" zum Dashboard hinzufügen';

// Filter-Labels.
$string['label_kursbereich']  = 'Kursbereich';
$string['label_schulform']    = 'Schulform';
$string['label_fach']         = 'Fach';
$string['label_niveaustufe']  = 'Niveaustufe';
$string['label_kursname']     = 'Kursname';

// Platzhalter.
$string['placeholder_kursname'] = 'Kursname suchen …';
$string['filter_all']           = '– Alle –';
$string['hint_setfilter']       = 'Filter setzen, um Kurse zu suchen.';

// Buttons.
$string['btn_reset']      = 'Zurücksetzen';
$string['btn_opencourse'] = 'Zum Kurs';
$string['btn_export']     = 'Exportieren';

// Admin-Einstellungen.
$string['settings_schulformen_heading'] = 'Schulformen';
$string['settings_schulformen_desc']    = 'Diese Werte erscheinen als Chips im Block. Jede Schulform wird als Tag „schulform:Wert" an Kursen erwartet.';
$string['settings_schulformen']         = 'Schulformen';
$string['settings_schulformen_help']    = 'Eine Schulform pro Zeile, z. B. Gymnasium. Am Kurs muss dann der Tag „schulform:Gymnasium" gesetzt sein.';

$string['settings_faecher_heading']     = 'Fächer';
$string['settings_faecher']             = 'Fächer';
$string['settings_faecher_help']        = 'Ein Fach pro Zeile. Am Kurs muss dann der Tag „fach:Mathematik" o. ä. gesetzt sein.';

$string['settings_niveaustufen_heading']= 'Niveaustufen';
$string['settings_niveaustufen_desc']   = 'Eine Stufe pro Zeile. Am Kurs muss der Tag „niveaustufe:Klasse 10" o. ä. gesetzt sein.';
$string['settings_niveaustufen']        = 'Niveaustufen';
$string['settings_niveaustufen_help']   = 'Eine Stufe pro Zeile. Beliebig erweiterbar.';

$string['settings_resultlimit']         = 'Max. Ergebnisse';
$string['settings_resultlimit_help']    = 'Maximale Anzahl Kurse pro Suchanfrage (Standard: 100).';
