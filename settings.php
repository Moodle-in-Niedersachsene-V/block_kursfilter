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
 * Admin settings for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'block_kursfilter/filters_heading',
        get_string('settings_filters_heading', 'block_kursfilter'),
        get_string('settings_filters_desc', 'block_kursfilter')
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_kursfilter/schooltypes',
        get_string('settings_schooltypes', 'block_kursfilter'),
        get_string('settings_schooltypes_help', 'block_kursfilter'),
        "Grundschule\nHauptschule\nRealschule\nGymnasium\nGesamtschule\nBerufsschule"
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_kursfilter/subjects',
        get_string('settings_subjects', 'block_kursfilter'),
        get_string('settings_subjects_help', 'block_kursfilter'),
        "Mathematik\nDeutsch\nEnglisch\nNaturwissenschaften\nGeschichte\nKunst\nMusik\nSport"
    ));
    $settings->add(new admin_setting_configtextarea(
        'block_kursfilter/levels',
        get_string('settings_levels', 'block_kursfilter'),
        get_string('settings_levels_help', 'block_kursfilter'),
        "Klasse 1-4\nKlasse 5-6\nKlasse 7-9\nKlasse 10\nOberstufe"
    ));
    $settings->add(new admin_setting_configtext(
        'block_kursfilter/resultlimit',
        get_string('settings_resultlimit', 'block_kursfilter'),
        get_string('settings_resultlimit_help', 'block_kursfilter'),
        '100',
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'block_kursfilter/pool_heading',
        get_string('settings_pool_heading', 'block_kursfilter'),
        get_string('settings_pool_desc', 'block_kursfilter')
    ));
    $settings->add(new admin_setting_configtext(
        'block_kursfilter/poolsize',
        get_string('settings_poolsize', 'block_kursfilter'),
        get_string('settings_poolsize_help', 'block_kursfilter'),
        '10',
        PARAM_INT
    ));

    $settings->add(new admin_setting_heading(
        'block_kursfilter/backup_heading',
        get_string('settings_backup_heading', 'block_kursfilter'),
        get_string('settings_backup_desc', 'block_kursfilter')
    ));
    $settings->add(new admin_setting_configtext(
        'block_kursfilter/backup_userid',
        get_string('settings_backup_userid', 'block_kursfilter'),
        get_string('settings_backup_userid_help', 'block_kursfilter'),
        '',
        PARAM_INT
    ));
}
