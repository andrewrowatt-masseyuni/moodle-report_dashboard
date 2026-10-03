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

    $record = false;
    if ($cm = $formwrapper->get_coursemodule()) {
        $record = $DB->get_record('report_dashboard_cm', ['cmid' => $cm->id]);
    }

    // Only assessments are shown on the course dashboard report based on their close or due date.
    $isassessment = in_array($formwrapper->get_current()->modulename, \report_dashboard\dashboard::ASSESSMENT_MODULES);

    $mform->addElement(
        'select',
        'report_dashboard_showondashboard',
        \report_dashboard\dashboard::get_field_label(
            get_string('showondashboard', 'report_dashboard'),
            get_string('showondashboard_inlinehelp', 'report_dashboard')
        ),
        [
            \report_dashboard\dashboard::SHOW_AUTO => get_string('showondashboard_auto', 'report_dashboard'),
            \report_dashboard\dashboard::SHOW_ALWAYS => get_string('showondashboard_always', 'report_dashboard'),
            \report_dashboard\dashboard::SHOW_NEVER => get_string('showondashboard_never', 'report_dashboard'),
        ],
        $isassessment ? [] : ['disabled' => 'disabled']
    );
    $mform->setDefault(
        'report_dashboard_showondashboard',
        $record ? (int) $record->showondashboard : \report_dashboard\dashboard::SHOW_AUTO
    );

    // The activity completion elements are only in the form when completion tracking is enabled for the course.
    $completionenabled = $mform->elementExists('completion');

    $mform->addElement(
        'selectyesno',
        'report_dashboard_earlyengagement',
        \report_dashboard\dashboard::get_field_label(
            get_string('earlyengagement', 'report_dashboard'),
            get_string('earlyengagement_inlinehelp', 'report_dashboard')
        ),
        $completionenabled ? [] : ['disabled' => 'disabled']
    );
    if ($completionenabled) {
        $mform->disabledIf('report_dashboard_earlyengagement', 'completion', 'eq', COMPLETION_TRACKING_NONE);
    }
    $mform->setDefault('report_dashboard_earlyengagement', $record ? (int) $record->earlyengagement : 0);

    $mform->addElement(
        'text',
        'report_dashboard_titleoverride',
        \report_dashboard\dashboard::get_field_label(
            get_string('titleoverride', 'report_dashboard'),
            get_string('titleoverride_inlinehelp', 'report_dashboard')
        ),
        ['size' => 40]
    );
    $mform->setType('report_dashboard_titleoverride', PARAM_TEXT);
    $mform->addRule('report_dashboard_titleoverride', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
    $mform->setDefault('report_dashboard_titleoverride', $record ? (string) $record->titleoverride : '');
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

    // Settings are not submitted while disabled, or for activities they do not apply to, in which case the saved
    // values are kept.
    $fields = [];
    if (isset($data->report_dashboard_showondashboard)) {
        $fields['showondashboard'] = (int) $data->report_dashboard_showondashboard;
    }
    if (isset($data->report_dashboard_earlyengagement)) {
        $fields['earlyengagement'] = empty($data->report_dashboard_earlyengagement) ? 0 : 1;
    }
    if (isset($data->report_dashboard_titleoverride)) {
        $titleoverride = trim($data->report_dashboard_titleoverride);
        $fields['titleoverride'] = $titleoverride === '' ? null : $titleoverride;
    }

    if ($record = $DB->get_record('report_dashboard_cm', ['cmid' => $data->coursemodule])) {
        $changed = false;
        foreach ($fields as $name => $value) {
            if ($record->$name != $value) {
                $record->$name = $value;
                $changed = true;
            }
        }
        if ($changed) {
            $DB->update_record('report_dashboard_cm', $record);
        }
    } else if (array_filter($fields, fn($value) => $value !== 0 && $value !== null)) {
        // ... Only activities with a non-default setting need a record.
        $DB->insert_record('report_dashboard_cm', ['cmid' => $data->coursemodule] + $fields);
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
