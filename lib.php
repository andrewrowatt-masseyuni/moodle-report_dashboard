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

/**
 * Add the course dashboard preferences to the course module settings form.
 *
 * @param moodleform_mod $formwrapper The course module settings form wrapper.
 * @param MoodleQuickForm $mform The course module settings form.
 */
function report_dashboard_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB;

    $mform->addElement('header', 'report_dashboard_header', get_string('coursedashboardpreferences', 'report_dashboard'));

    // The activity completion elements are only in the form when completion tracking is enabled for the course.
    $completionenabled = $mform->elementExists('completion');

    $mform->addElement(
        'selectyesno',
        'report_dashboard_earlyengagement',
        get_string('earlyengagement', 'report_dashboard'),
        $completionenabled ? [] : ['disabled' => 'disabled']
    );
    $mform->addHelpButton('report_dashboard_earlyengagement', 'earlyengagement', 'report_dashboard');
    if ($completionenabled) {
        $mform->disabledIf('report_dashboard_earlyengagement', 'completion', 'eq', COMPLETION_TRACKING_NONE);
    }

    $earlyengagement = 0;
    if ($cm = $formwrapper->get_coursemodule()) {
        $earlyengagement = (int) $DB->get_field('report_dashboard_cm', 'earlyengagement', ['cmid' => $cm->id]);
    }
    $mform->setDefault('report_dashboard_earlyengagement', $earlyengagement);
}

/**
 * Save the course dashboard preferences when a course module is created or updated.
 *
 * @param stdClass $data Data from the course module settings form.
 * @param stdClass $course The course.
 * @return stdClass
 */
function report_dashboard_coursemodule_edit_post_actions($data, $course) {
    global $DB;

    // The setting is not submitted while it is disabled, in which case the saved value is kept.
    if (!isset($data->report_dashboard_earlyengagement)) {
        return $data;
    }

    $earlyengagement = empty($data->report_dashboard_earlyengagement) ? 0 : 1;
    if ($record = $DB->get_record('report_dashboard_cm', ['cmid' => $data->coursemodule])) {
        if ($record->earlyengagement != $earlyengagement) {
            $record->earlyengagement = $earlyengagement;
            $DB->update_record('report_dashboard_cm', $record);
        }
    } else if ($earlyengagement) {
        $DB->insert_record('report_dashboard_cm', ['cmid' => $data->coursemodule, 'earlyengagement' => $earlyengagement]);
    }

    return $data;
}

/**
 * Delete the course dashboard preferences for a course module that is being deleted.
 *
 * @param stdClass $cm The course module.
 */
function report_dashboard_pre_course_module_delete($cm) {
    global $DB;

    $DB->delete_records('report_dashboard_cm', ['cmid' => $cm->id]);
}
