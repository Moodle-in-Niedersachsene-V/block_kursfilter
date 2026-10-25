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
 * Move the settings that got English names to the new names (idempotent).
 */
function xmldb_block_kursfilter_rename_settings(): void {
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
}

/**
 * Remove the settings of the former own AI backend (idempotent).
 */
function xmldb_block_kursfilter_remove_ai_settings(): void {
    foreach (['ai_backend', 'ai_claude_apikey', 'ai_claude_model', 'ai_ollama_url', 'ai_ollama_model'] as $name) {
        unset_config($name, 'block_kursfilter');
    }
}

/**
 * Rename the pool accounts from the former prefix to the glossary name (idempotent, keeps user IDs and assignments).
 */
function xmldb_block_kursfilter_rename_pool_accounts(): void {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/user/lib.php');

    $old = 'kursfilter_guest';
    $new = \block_kursfilter\pool_manager::USERNAME_PREFIX;
    for ($i = 1; $i <= \block_kursfilter\pool_manager::POOL_SIZE_MAX; $i++) {
        $suffix = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        $user = $DB->get_record('user', ['username' => $old . $suffix, 'mnethostid' => $CFG->mnet_localhost_id]);
        if (!$user || $DB->record_exists('user', ['username' => $new . $suffix, 'mnethostid' => $CFG->mnet_localhost_id])) {
            continue;
        }
        $user->username = $new . $suffix;
        $user->email = $new . $suffix . '@kursfilter.invalid';
        user_update_user($user, false, false);
    }
}

/**
 * Rename the tag suggestion table and its status 'pending' to 'suggested' (idempotent, keeps all rows).
 */
function xmldb_block_kursfilter_rename_tag_suggestions(): void {
    global $DB;

    $dbman = $DB->get_manager();
    $old = new \xmldb_table('block_kursfilter_tag_pending');
    $new = new \xmldb_table('block_kursfilter_tag_suggest');
    if ($dbman->table_exists($old) && !$dbman->table_exists($new)) {
        $dbman->rename_table($old, 'block_kursfilter_tag_suggest');
    }
    if (!$dbman->table_exists($new)) {
        return;
    }
    $DB->set_field('block_kursfilter_tag_suggest', 'status', 'suggested', ['status' => 'pending']);
    $status = new \xmldb_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'suggested', 'tags');
    // Moodle refuses to change a column that an index depends on.
    $index = new \xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
    if ($dbman->index_exists($new, $index)) {
        $dbman->drop_index($new, $index);
    }
    $dbman->change_field_default($new, $status);
    $dbman->add_index($new, $index);
}

/**
 * Upgrade the block_kursfilter plugin.
 *
 * @param int $oldversion Previous plugin version.
 * @return bool
 */
function xmldb_block_kursfilter_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    // Glossary names (N1): the pool accounts must be renamed before the older steps look them up by name.
    // Not a savepoint step: the rename is idempotent and the savepoint 2026101501 below covers it.
    $oldversion < 2026101501 && xmldb_block_kursfilter_rename_pool_accounts();

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
        xmldb_block_kursfilter_rename_settings();
        upgrade_block_savepoint(true, 2026100901, 'kursfilter');
    }

    if ($oldversion < 2026101000) {
        // AI tagging uses Moodle's AI subsystem; the own backend settings, including the API key, go away.
        xmldb_block_kursfilter_remove_ai_settings();
        upgrade_block_savepoint(true, 2026101000, 'kursfilter');
    }

    if ($oldversion < 2026101001) {
        // Installations from the 1.4.0 line (2026100811) skipped the step 2026100600: repeat the idempotent migration.
        \block_kursfilter\pool_manager::migrate_to_pool_role();
        upgrade_block_savepoint(true, 2026101001, 'kursfilter');
    }

    if ($oldversion < 2026101023) {
        // The 1.5.0 line on main (up to 2026101006) passed 2026101000/2026101001 with other steps under the same numbers.
        // All three migrations are idempotent and run again.
        xmldb_block_kursfilter_rename_settings();
        xmldb_block_kursfilter_remove_ai_settings();
        \block_kursfilter\pool_manager::migrate_to_pool_role();
        upgrade_block_savepoint(true, 2026101023, 'kursfilter');
    }

    if ($oldversion < 2026101027) {
        // Bump savepoint to match plugin version.
        upgrade_block_savepoint(true, 2026101027, 'kursfilter');
    }

    if ($oldversion < 2026101028) {
        // Re-apply prohibited capabilities to the pool role to include profile access restrictions.
        $roleid = $DB->get_field('role', 'id', ['shortname' => \block_kursfilter\pool_manager::ROLE_SHORTNAME]);
        if ($roleid) {
            $ctx = \context_system::instance();
            foreach (\block_kursfilter\pool_manager::PROHIBITED_CAPABILITIES as $cap) {
                assign_capability($cap, CAP_PROHIBIT, $roleid, $ctx->id, true);
            }
        }
        upgrade_block_savepoint(true, 2026101028, 'kursfilter');
    }

    if ($oldversion < 2026101029) {
        // Profile, profile editing and password change are now also prohibited for the pool role.
        $roleid = \block_kursfilter\pool_manager::ensure_role();
        foreach (\block_kursfilter\pool_manager::PROHIBITED_CAPABILITIES as $capability) {
            assign_capability($capability, CAP_PROHIBIT, $roleid, \context_system::instance()->id, true);
        }
        upgrade_block_savepoint(true, 2026101029, 'kursfilter');
    }

    if ($oldversion < 2026101500) {
        // Rebuild observer and cache definitions after removing the separate occupancy cache.
        upgrade_block_savepoint(true, 2026101500, 'kursfilter');
    }

    if ($oldversion < 2026101501) {
        // Glossary names (N1): tag suggestion table and status; the pool accounts were renamed above.
        xmldb_block_kursfilter_rename_tag_suggestions();
        upgrade_block_savepoint(true, 2026101501, 'kursfilter');
    }

    if ($oldversion < 2026102201) {
        // New setting rootcategory: restricts automatic tagging and backups to a category subtree.
        // No database schema change; the setting is a plain config value. Just bump the savepoint.
        upgrade_block_savepoint(true, 2026102201, 'kursfilter');
    }

    if ($oldversion < 2026102501) {
        // AI personal-data reports are now recorded as status='skipped' instead of failing the task.
        // No schema change needed; the status column is a free-form CHAR(20).
        upgrade_block_savepoint(true, 2026102501, 'kursfilter');
    }

    return true;
}
