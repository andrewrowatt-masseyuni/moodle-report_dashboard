<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace report_dashboard;

use core_course\hook\before_course_deleted;

/**
 * Hook callbacks for Course Dashboard
 *
 * @package     report_dashboard
 * @copyright   2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Delete the course dashboard preferences for all course modules in a course that is being deleted.
     *
     * Course deletion does not call the pre_course_module_delete callback for each course module.
     *
     * @param before_course_deleted $hook
     */
    public static function before_course_deleted(before_course_deleted $hook): void {
        global $DB;

        $DB->delete_records_select(
            'report_dashboard_cm',
            'cmid IN (SELECT id FROM {course_modules} WHERE course = :courseid)',
            ['courseid' => $hook->course->id]
        );
    }
}
