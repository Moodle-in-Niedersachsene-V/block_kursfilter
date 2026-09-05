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
 * Post-install hook for block_kursfilter.
 *
 * Legt die Pool-Nutzer bei der ersten Installation an
 * und schreibt sie in alle vorhandenen Kurse ein.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Post-install tasks for block_kursfilter.
 */
function xmldb_block_kursfilter_install(): void {
    // Pool-Nutzer anlegen.
    \block_kursfilter\pool_manager::create_pool_users();

    // In alle vorhandenen sichtbaren Kurse einschreiben.
    \block_kursfilter\pool_manager::enrol_pool_into_all_courses();
}
