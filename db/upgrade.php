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
 * Upgrade steps for block_kursfilter.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the block_kursfilter plugin.
 *
 * @param int $oldversion Previous plugin version.
 * @return bool
 */
function xmldb_block_kursfilter_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026100600) {
        // Pool accounts get a role without access to participants, user identity and grades.
        \block_kursfilter\pool_manager::migrate_to_pool_role();
        upgrade_block_savepoint(true, 2026100600, 'kursfilter');
    }

    if ($oldversion < 2026100801) {
        // Table for pending AI tag suggestions.
        $table = new \xmldb_table('block_kursfilter_tag_pending');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('courseid', XMLDB_INDEX_UNIQUE, ['courseid']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_block_savepoint(true, 2026100801, 'kursfilter');
    }

    if ($oldversion < 2026100901) {
        // Settings got English names (coding standard N3); values move to the new names.
        $renamed = [
            'schulformen' => 'schooltypes',
            'faecher' => 'subjects',
            'niveaustufen' => 'levels',
            'backup_adminid' => 'backup_userid',
        ];
        foreach ($renamed as $old => $new) {
            $value = get_config('block_kursfilter', $old);
            if ($value !== false) {
                set_config($new, $value, 'block_kursfilter');
                unset_config($old, 'block_kursfilter');
            }
        }
        upgrade_block_savepoint(true, 2026100901, 'kursfilter');
    }

    if ($oldversion < 2026101000) {
        // AI tagging uses Moodle's AI subsystem; the own backend settings, including the API key, go away.
        foreach (['ai_backend', 'ai_claude_apikey', 'ai_claude_model', 'ai_ollama_url', 'ai_ollama_model'] as $name) {
            unset_config($name, 'block_kursfilter');
        }
        upgrade_block_savepoint(true, 2026101000, 'kursfilter');
    }

    if ($oldversion < 2026101001) {
        // Installations from the 1.4.0 line (2026100811) skipped the step 2026100600: repeat the idempotent migration.
        \block_kursfilter\pool_manager::migrate_to_pool_role();
        upgrade_block_savepoint(true, 2026101001, 'kursfilter');
    }

    return true;
}
