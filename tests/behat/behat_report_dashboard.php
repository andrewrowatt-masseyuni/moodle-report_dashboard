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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps in plugin report_dashboard
 *
 * @package    report_dashboard
 * @category   test
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_report_dashboard extends behat_base {

    /**
     * Get the URL for the dashboard page.
     *
     * @param string $courseshortname The course short name
     * @return moodle_url
     */
    protected function get_dashboard_url($courseshortname) {
        global $DB;

        $course = $DB->get_record('course', ['shortname' => $courseshortname], '*', MUST_EXIST);
        return new moodle_url('/report/dashboard/index.php', ['id' => $course->id]);
    }


    // https://github.com/moodle/moodle/blob/a0fc902eb184cd4097c8ab453ddc57964cd2dbd4/lib/behat/behat_base.php#L1093

    // protected function resolve_page_url

    // protected function resolve_page_instance_url
}
