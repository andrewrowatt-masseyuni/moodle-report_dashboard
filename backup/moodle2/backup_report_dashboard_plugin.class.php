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
 * Backup of the course dashboard preferences for course modules.
 *
 * @package     report_dashboard
 * @category    backup
 * @copyright   2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_report_dashboard_plugin extends backup_report_plugin {
    /**
     * Returns the course dashboard preferences to attach to the module element.
     *
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element();

        $pluginwrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginwrapper);

        $cm = new backup_nested_element('dashboard_cm', ['id'], ['earlyengagement', 'showondashboard', 'titleoverride']);
        $pluginwrapper->add_child($cm);

        $cm->set_source_table('report_dashboard_cm', ['cmid' => backup::VAR_MODID]);

        return $plugin;
    }
}
