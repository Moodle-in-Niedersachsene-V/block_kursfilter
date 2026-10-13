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

    if ($oldversion < 2026100801) {
        // Tabelle fuer ausstehende KI-Tag-Vorschlaege anlegen.
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

    if ($oldversion < 2026101001) {
        // Einstellungsschluessel umbenennen:
        // schulformen  → schooltypes
        // faecher      → subjects
        // niveaustufen → levels
        // backup_adminid => backup_userid.
        $renames = [
            'schulformen'  => 'schooltypes',
            'faecher'      => 'subjects',
            'niveaustufen' => 'levels',
            'backup_adminid' => 'backup_userid',
        ];
        foreach ($renames as $old => $new) {
            $value = get_config('block_kursfilter', $old);
            if ($value !== false) {
                set_config($new, $value, 'block_kursfilter');
                unset_config($old, 'block_kursfilter');
            }
        }

        upgrade_block_savepoint(true, 2026101001, 'kursfilter');
    }

    if ($oldversion < 2026101016) {
        // Assign the kursfilter_pooluser restriction role to all existing pool users
        // so they cannot edit their own profile or change their password.
        // New pool users created after this build get the role in pool_manager::create_pool_users().
        $prefix = \block_kursfilter\pool_manager::USERNAME_PREFIX;
        $roleid = \block_kursfilter\pool_manager::ensure_pool_role();
        $systemcontext = \context_system::instance();

        $poolusers = $DB->get_records_select(
            'user',
            $DB->sql_like('username', ':prefix') . ' AND deleted = 0',
            ['prefix' => $DB->sql_like_escape($prefix) . '%'],
            '',
            'id'
        );

        foreach ($poolusers as $pooluser) {
            // role_assign() is idempotent — safe to call even if already assigned.
            role_assign($roleid, $pooluser->id, $systemcontext->id);
        }

        upgrade_block_savepoint(true, 2026101016, 'kursfilter');
    }

    if ($oldversion < 2026101017) {
        // Extend the kursfilter_pooluser role with additional prohibitions:
        // messaging, own-files management and message-profile editing.
        // ensure_pool_role() is idempotent; the new capabilities are added
        // via assign_capability() which overwrites any existing permission value.
        $roleid        = \block_kursfilter\pool_manager::ensure_pool_role();
        $systemcontext = \context_system::instance();

        $newcaps = [
            'moodle/user:editownmessageprofile',
            'moodle/user:manageownfiles',
            'moodle/site:sendmessage',
        ];
        foreach ($newcaps as $cap) {
            assign_capability($cap, CAP_PROHIBIT, $roleid, $systemcontext->id, true);
        }

        upgrade_block_savepoint(true, 2026101017, 'kursfilter');
    }

    if ($oldversion < 2026101018) {
        // No DB changes — block_kursfilter_after_config() in lib.php now handles
        // session-level messaging suppression and URL redirect for pool users.
        // This savepoint records the build that introduced that behaviour.
        upgrade_block_savepoint(true, 2026101018, 'kursfilter');
    }

    return true;
}
