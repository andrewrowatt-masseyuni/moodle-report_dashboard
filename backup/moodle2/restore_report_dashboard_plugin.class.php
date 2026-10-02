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

/**
 * Restore of the course dashboard preferences for course modules.
 *
 * @package     report_dashboard
 * @category    backup
 * @copyright   2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_report_dashboard_plugin extends restore_report_plugin {
    /**
     * Returns the paths to be handled by the plugin at module level.
     *
     * @return restore_path_element[]
     */
    protected function define_module_plugin_structure() {
        return [new restore_path_element('report_dashboard_cm', $this->get_pathfor('/dashboard_cm'))];
    }

    /**
     * Process the course dashboard preferences for a restored course module.
     *
     * @param array $data
     */
    public function process_report_dashboard_cm($data) {
        global $DB;

        $data = (object) $data;
        $data->cmid = $this->task->get_moduleid();

        $DB->insert_record('report_dashboard_cm', $data);
    }
}
