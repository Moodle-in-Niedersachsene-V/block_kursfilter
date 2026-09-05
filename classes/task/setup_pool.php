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
 * Scheduled task: ensure pool users exist and are enroled in all courses.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @author    Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_kursfilter\task;

/**
 * Nightly pool setup task.
 *
 * Laeuft taeglich nach dem Backup-Task (03:00 Uhr).
 * Legt fehlende Pool-Nutzer an und schreibt sie in alle
 * sichtbaren Kurse als Trainer ohne Bearbeitungsrecht ein.
 */
class setup_pool extends \core\task\scheduled_task {
    /**
     * Return task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_setup_pool', 'block_kursfilter');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        $created  = \block_kursfilter\pool_manager::create_pool_users();
        $enrolled = \block_kursfilter\pool_manager::enrol_pool_into_all_courses();

        mtrace("Kursfilter-Pool: {$created} Nutzer angelegt, {$enrolled} Einschreibungen hinzugefuegt.");
    }
}
