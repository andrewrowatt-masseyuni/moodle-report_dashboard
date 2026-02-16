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
 * Display Course Dashboard report
 *
 * @package    report_dashboard
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
use report_dashboard\modinfohelper;

/*
 * Setup page, check permissions
 */

// ... Get course details
$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
if (!has_capability('report/dashboard:view', $context)) {
    redirect(
        new moodle_url('/my'),
        get_string('nopermissions', 'error', get_string('dashboard:view', 'report_dashboard')),
        \core\output\notification::NOTIFY_ERROR
    );
}


$url = new moodle_url('/report/dashboard/index.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(
    format_string($course->fullname) . ' : ' .
    get_string('pluginname', 'report_dashboard')
);

$savedhiddencmids = unserialize(get_user_preferences('report_dashboard_hidden_cmids', serialize([])));
$fontsize = (int) get_user_preferences('report_dashboard_fontsize', 14);
$fontsizes = [];
foreach ([11, 12, 13, 14, 16] as $fs) {
    $fontsizes[] = [
        'value' => $fs,
        'label' => get_string('fontsize_' . $fs, 'report_dashboard'),
        'selected' => ($fs === $fontsize),
    ];
}

$action = optional_param('action', '', PARAM_ALPHA);
if ($action) {
    require_sesskey();

    switch ($action) {
        case 'hideitem':
            $savedhiddencmids[] = required_param('cmid', PARAM_INT);
            break;
        case 'showitem':
            $assessmentcmid = required_param('cmid', PARAM_INT);
            $savedhiddencmids = array_diff($savedhiddencmids, [$assessmentcmid]);
            break;
        default:
            // ... Display the report
    }

    set_user_preference('report_dashboard_hidden_cmids', serialize($savedhiddencmids));

    redirect($url);
}

$modinfohelper = new modinfohelper(get_fast_modinfo($course));

// ... DataTables requirements
$PAGE->requires->css('/report/dashboard/datatables/datatables.min.css');
$PAGE->requires->js_call_amd('report_dashboard/dashboard', 'init', [$courseid]);

// ... JSZip requirement for Excel export. Cannot be loaded via Import statement in AMD module.
$PAGE->requires->js('/report/dashboard/amd/build/jszip.min.js', true);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_dashboard'));

$description = markdown_to_html(trim(get_config('report_dashboard', 'description')));
$instructions = markdown_to_html(trim(get_config('report_dashboard', 'instructions')));
$limitations = markdown_to_html(trim(get_config('report_dashboard', 'limitations')));
// If blank, the template will hide or otherwise handle this condition.

$knownissues = markdown_to_html(trim(get_config('report_dashboard', 'knownissues')));
// If blank, the template will hide or otherwise handle this condition.

$supportcontact = markdown_to_html(trim(get_config('report_dashboard', 'supportcontact')));
// If blank, the template will hide or otherwise handle this condition.

// Load lightweight assessment/early engagement data for hide/show buttons.
$earlyengagements = \report_dashboard\dashboard::get_early_engagements($courseid);
$assessments = \report_dashboard\dashboard::get_assessments($courseid);

$hiddenassessments = [];
$hiddenearlyengagements = [];

foreach ($assessments as $assessmentobject) {
    $assessment = (array)$assessmentobject;
    $assessment += ['name' => $modinfohelper->get_cm_name($assessment['cmid'])];
    if (in_array($assessment['cmid'], $savedhiddencmids)) {
        $hiddenassessments[] = $assessment;
    }
}

foreach ($earlyengagements as $earlyengagementobject) {
    $earlyengagement = (array)$earlyengagementobject;
    $earlyengagement += ['name' => $modinfohelper->get_cm_name($earlyengagement['cmid'])];
    if (in_array($earlyengagement['cmid'], $savedhiddencmids)) {
        $hiddenearlyengagements[] = $earlyengagement;
    }
}

// Generate 50 skeleton placeholder rows for the loading animation.
$skeletonrows = array_fill(0, 50, ['skeleton' => true]);

// Render the page shell using the index template.
echo $OUTPUT->render_from_template('report_dashboard/index', [
    'description' => $description,
    'instructions' => $instructions,
    'limitations' => $limitations,
    'knownissues' => $knownissues,
    'supportcontact' => $supportcontact,
    'hiddenearlyengagements' => $hiddenearlyengagements,
    'hiddenassessments' => $hiddenassessments,
    'courseid' => $courseid,
    'sesskey' => sesskey(),
    'rows' => $skeletonrows,
    'fontsize' => $fontsize,
    'fontsizes' => $fontsizes,
]);

echo $OUTPUT->footer();
