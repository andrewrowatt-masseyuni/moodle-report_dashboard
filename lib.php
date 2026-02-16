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
 * Callback implementations for Course Dashboard
 *
 * @package    report_dashboard
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define user preferences for this plugin.
 *
 * @return array[]
 */
function report_dashboard_user_preferences() {
    $preferences = [];
    $preferences['report_dashboard_fontsize'] = [
        'type' => PARAM_INT,
        'null' => NULL_NOT_ALLOWED,
        'default' => 14,
        'choices' => [11, 12, 13, 14, 16],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];
    $preferences['report_dashboard_hidden_cmids'] = [
        'type' => PARAM_RAW,
        'null' => NULL_NOT_ALLOWED,
        'default' => '[]',
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];
    return $preferences;
}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param stdClass $context The context of the course
 */
function report_dashboard_extend_navigation_course($navigation, $course, $context) {
    global $CFG;

    if (has_capability('report/dashboard:view', $context)) {
        $url = new moodle_url('/report/dashboard/index.php', ['id' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'report_dashboard'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        );
    }
}
