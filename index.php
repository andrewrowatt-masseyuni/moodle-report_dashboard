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
// Clean up report_dashboard_hidden_cmids as it may contain cmids that no longer exist.

// ... DataTables requirements
$PAGE->requires->css('/report/dashboard/datatables/datatables.min.css');
$PAGE->requires->js_call_amd('report_dashboard/dashboard', 'init');

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

$coursegroups = \report_dashboard\dashboard::get_groups($courseid);
$coursegroupsarray = [];
foreach ($coursegroups as $coursegroup) {
    $coursegroupsarray[] = (array)$coursegroup;
}

$coursecohortgroups = \report_dashboard\dashboard::get_cohort_groups($courseid);
$coursecohortgroupsarray = [];
foreach ($coursecohortgroups as $coursecohortgroup) {
    $coursecohortgroupsarray[] = (array)$coursecohortgroup;
}

$earlyengagements = \report_dashboard\dashboard::get_early_engagements($courseid);
$assessments = \report_dashboard\dashboard::get_assessments($courseid);

$visibleassessments = [];
$hiddenassessments = [];

$visibleearlyengagements = [];
$hiddenearlyengagements = [];

foreach ($assessments as $assessmentobject) {
    $assessment = (array)$assessmentobject;
    $assessment += ['name' => $modinfohelper->get_cm_name($assessment['cmid'])];
    if (in_array($assessment['cmid'], $savedhiddencmids)) {
        $hiddenassessments[] = $assessment;
    } else {
        $visibleassessments[] = $assessment;
    }
}

foreach ($earlyengagements as $earlyengagementobject) {
    $earlyengagement = (array)$earlyengagementobject;
    $earlyengagement += ['name' => $modinfohelper->get_cm_name($earlyengagement['cmid'])];
    if (in_array($earlyengagement['cmid'], $savedhiddencmids)) {
        $hiddenearlyengagements[] = $earlyengagement;
    } else {
        $visibleearlyengagements[] = $earlyengagement;
    }
}

$earlyengagementstatuses = \report_dashboard\dashboard::get_earlyengagement_statuses();
$userearlyengagements = \report_dashboard\dashboard::get_user_early_engagements($courseid, join(' ', $savedhiddencmids));

$assessmentstatuses = \report_dashboard\dashboard::get_assessment_statuses();
$userassessments = \report_dashboard\dashboard::get_user_assessments($courseid, join(' ', $savedhiddencmids));

$userdataset = \report_dashboard\dashboard::get_user_dataset($courseid);

// Collect all row data first.
$rows = [];



$usercount = count($userdataset);
$assessmentcount = count($visibleassessments);
$earlyengagementcount = count($visibleearlyengagements);

$userassessmentscount = count($userassessments);
$userearlyengagementscount = count($userearlyengagements);

$userindex = 0;
$userearlyengagementindex = 0;
$userassessmentindex = 0;

// ... Because we are using carefully sorted but separate arrays we need to do additional checking
if (($usercount * $assessmentcount) != $userassessmentscount) {
    throw new moodle_exception('User assessments count does not match user count * assessment count');
}

foreach ($userdataset as $userobject) {
    $row = (array)$userobject;
    $currentuserid = $row['id'];

    if ($row['lastaccessed_timestamp'] == -1) {
        $row['lastaccessed_filter_category'] = 'never';
        $row['lastaccessed_label'] = get_string('never', 'report_dashboard');
    } else {
        $deltadays = floor((time() - $row['lastaccessed_timestamp']) / 86400);

        switch ($deltadays) {
            case 0:
                $row['lastaccessed_filter_category'] = 'today';
                $row['lastaccessed_label'] = get_string('lastday', 'report_dashboard');
                break;
            case 1:
                $row['lastaccessed_filter_category'] = 'yesterday';
                $row['lastaccessed_label'] = get_string('over_1_days', 'report_dashboard');
                break;
            case $deltadays < 7:
                $row['lastaccessed_filter_category'] = '1week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_n_days', 'report_dashboard', $deltadays);
                break;
            case $deltadays < 14:
                $row['lastaccessed_filter_category'] = 'over1week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_1_week', 'report_dashboard');
                break;
            case $deltadays < 21:
                $row['lastaccessed_filter_category'] = 'over2week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_2_week', 'report_dashboard');
                break;
            case $deltadays < 28:
                $row['lastaccessed_filter_category'] = 'over3week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_3_week', 'report_dashboard');
                break;
            default:
                $row['lastaccessed_filter_category'] = 'over4week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_28_days', 'report_dashboard');
        }
    }

    $groups = [];

    if ($row['groups']) {
        foreach (explode(', ', $row['groups']) as $groupid) {
            $groupdetails = \report_dashboard\dashboard::get_item_by_id($coursegroups, $groupid);
            $groups[] = $groupdetails + ['class' => 'rdbtag-course-group'];
        }
    }

    $cohortgroups = [];

    if ($row['cohortgroups']) {
        foreach (explode(', ', $row['cohortgroups']) as $groupid) {
            $groupdetails = \report_dashboard\dashboard::get_item_by_id($coursecohortgroups, $groupid);
            $cohortgroups[] = $groupdetails + ['class' => 'tag-cohort-group'];
        }
    }

    $earlyengagements = [];

    for ($earlyengagementindex = 0; $earlyengagementindex < $earlyengagementcount; $earlyengagementindex++) {
        $earlyengagement = (array)$userearlyengagements[$userearlyengagementindex + 1];

        // ... Because we are using carefully sorted but separate arrays we need to do additional checking
        if ($earlyengagement['userid'] != $currentuserid) {
            throw new moodle_exception("Early engagement user id: $earlyengagement[userid] " .
                "does not match current user id: $currentuserid where user earlyengagement index = $userearlyengagementindex");
        }

        $label = $earlyengagementstatuses[$earlyengagement['status']];
        $earlyengagements[] = $earlyengagement + ['label' => $label];
        $userearlyengagementindex += 1;
    }

    $assessments = [];
    $lateassessments = false;

    for ($assessmentindex = 0; $assessmentindex < $assessmentcount; $assessmentindex++) {
        $assessment = (array)$userassessments[$userassessmentindex + 1];

        // ... Because we are using carefully sorted but separate arrays we need to do additional checking
        if ($assessment['userid'] != $currentuserid) {
            throw new moodle_exception("Assessment user id: $assessment[userid] " .
                " does not match current user id: $currentuserid where user assessment index = $userassessmentindex");
        }

        $label = $assessmentstatuses[$assessment['status']];

        if (in_array($assessment['status'], ['passed', 'failed'])) {
            $label = $assessment['grade'];
        }

        $assessments[] = $assessment + ['label' => $label];

        if ($assessment['status'] == 'overdue') {
            $lateassessments = true;
        }

        $userassessmentindex += 1;
    }

    // Collect row data instead of rendering immediately.
    $rows[] = [
        'row' => $row,
        'groups' => $groups,
        'cohort_groups' => $cohortgroups,
        'earlyengagements' => $earlyengagements,
        'assessments' => $assessments,
        'lateassessments' => $lateassessments,
    ];
}

// Render the complete dashboard using the master template.
echo $OUTPUT->render_from_template('report_dashboard/dashboard', [
    'description' => $description,
    'instructions' => $instructions,
    'limitations' => $limitations,
    'knownissues' => $knownissues,
    'supportcontact' => $supportcontact,
    'earlyengagements' => $visibleearlyengagements,
    'hiddenearlyengagements' => $hiddenearlyengagements,
    'assessments' => $visibleassessments,
    'hiddenassessments' => $hiddenassessments,
    'courseid' => $courseid,
    'sesskey' => sesskey(),
    'cohort_groups' => $coursecohortgroupsarray,
    'groups' => $coursegroupsarray,
    'rows' => $rows,
]);

echo $OUTPUT->footer();
