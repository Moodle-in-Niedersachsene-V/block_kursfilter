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

namespace block_kursfilter;

/**
 * Thrown by tag_suggester when the AI model flags personal data in a course text.
 *
 * Catching this exception separately from a generic \Throwable lets the caller
 * record the course as 'skipped' instead of treating it as a hard failure.
 *
 * @package   block_kursfilter
 * @copyright 2026 Moodle in Niedersachsen e. V.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class personal_data_exception extends \moodle_exception {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct('error_ai_personal_data', 'block_kursfilter');
    }
}
