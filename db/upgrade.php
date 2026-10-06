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
    if ($oldversion < 2026100600) {
        // Pool accounts get a role without access to participants, user identity and grades.
        \block_kursfilter\pool_manager::migrate_to_pool_role();
        upgrade_block_savepoint(true, 2026100600, 'kursfilter');
    }

    return true;
}
