<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // --- Kursbereiche / Kategorien: werden dynamisch aus der DB geladen, keine Einstellung nötig ---

    // --- Schulformen ---
    $settings->add(new admin_setting_heading(
        'block_kursfilter/schulformen_heading',
        get_string('settings_schulformen_heading', 'block_kursfilter'),
        get_string('settings_schulformen_desc', 'block_kursfilter')
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_kursfilter/schulformen',
        get_string('settings_schulformen', 'block_kursfilter'),
        get_string('settings_schulformen_help', 'block_kursfilter'),
        "Grundschule\nHauptschule\nRealschule\nGymnasium\nGesamtschule\nBerufsschule"
    ));

    // --- Fächer ---
    $settings->add(new admin_setting_heading(
        'block_kursfilter/faecher_heading',
        get_string('settings_faecher_heading', 'block_kursfilter'),
        ''
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_kursfilter/faecher',
        get_string('settings_faecher', 'block_kursfilter'),
        get_string('settings_faecher_help', 'block_kursfilter'),
        "Mathematik\nDeutsch\nEnglisch\nNaturwissenschaften\nGeschichte\nKunst\nMusik\nSport"
    ));

    // --- Niveaustufen ---
    $settings->add(new admin_setting_heading(
        'block_kursfilter/niveaustufen_heading',
        get_string('settings_niveaustufen_heading', 'block_kursfilter'),
        get_string('settings_niveaustufen_desc', 'block_kursfilter')
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_kursfilter/niveaustufen',
        get_string('settings_niveaustufen', 'block_kursfilter'),
        get_string('settings_niveaustufen_help', 'block_kursfilter'),
        "Klasse 1-4\nKlasse 5-6\nKlasse 7-9\nKlasse 10\nOberstufe"
    ));

    // --- Ergebnislimit ---
    $settings->add(new admin_setting_configtext(
        'block_kursfilter/resultlimit',
        get_string('settings_resultlimit', 'block_kursfilter'),
        get_string('settings_resultlimit_help', 'block_kursfilter'),
        '100',
        PARAM_INT
    ));
}
