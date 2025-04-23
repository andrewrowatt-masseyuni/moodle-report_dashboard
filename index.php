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

/*
 * Setup page, check permissions
 */

// ... Get course details
$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('report/dashboard:view', $context);

$url = new moodle_url('/report/dashboard/index.php', ['id' => $course->id]);
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_title(
    format_string($course->fullname) . ' : ' .
    get_string('pluginname', 'report_dashboard')
);

$savedhiddenassessmentcmids = unserialize(get_user_preferences('report_dashboard_hidden_assessments',  serialize([])));

$action = optional_param('action', '', PARAM_ALPHA);
switch ($action) {
    case 'hideassessment':
        $savedhiddenassessmentcmids[] = required_param('assessmentid', PARAM_INT);
        break;
    case 'showassessment':
        $assessmentcmid = required_param('assessmentid', PARAM_INT);
        $savedhiddenassessmentcmids = array_diff($savedhiddenassessmentcmids, [$assessmentcmid]);
        break;
    default:
        // ... Display the report
}

// ... DataTables requirements
$PAGE->requires->css('/report/dashboard/datatables/datatables.min.css');
$PAGE->requires->js_call_amd('report_dashboard/dashboard', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_dashboard'));

echo get_config('report_dashboard', 'instructions');

$data = [];

$coursegroups = \report_dashboard\dashboard::get_groups($courseid);
$coursegroupsarray = [];
foreach($coursegroups as $coursegroup) {
    $coursegroupsarray[] = (array)$coursegroup;
}

$coursecohortgroups = \report_dashboard\dashboard::get_cohort_groups($courseid);
$coursecohortgroupsarray = [];
foreach($coursecohortgroups as $coursecohortgroup) {
    $coursecohortgroupsarray[] = (array)$coursecohortgroup;
}


$assessments = \report_dashboard\dashboard::get_assessments($courseid);



$actualhiddenassessmentcmids = []; // ... savehiddenassessmentcmids may contain cmids that no longer exist

$visibleassessments = [];
$hiddenassessments = [];

foreach($assessments as $assessmentobject) {
    $assessment = (array)$assessmentobject;
    if(in_array($assessment['assessmentid'], $savedhiddenassessmentcmids)) {
        $hiddenassessments[] = $assessment;
        $actualhiddenassessmentcmids[] = $assessment['assessmentid'];
    } else {
        $visibleassessments[] = $assessment;
    }
}

set_user_preference('report_dashboard_hidden_assessments', serialize($actualhiddenassessmentcmids));

$assessmentstatuses = \report_dashboard\dashboard::get_assessment_statuses();

$userassessments = \report_dashboard\dashboard::get_user_assessments($courseid, join(' ',$actualhiddenassessmentcmids));

$userdataset = \report_dashboard\dashboard::get_user_dataset($courseid);

echo $OUTPUT->render_from_template('report_dashboard/header_headings', ['assessments' => $visibleassessments, 'hiddenassessments' => $hiddenassessments, 'courseid' => $courseid]);
echo $OUTPUT->render_from_template('report_dashboard/header_filter_name', $data);
echo $OUTPUT->render_from_template('report_dashboard/header_filter_groups', ['cohort_groups' => $coursecohortgroupsarray, 'groups' => $coursegroupsarray]);
echo $OUTPUT->render_from_template('report_dashboard/header_filter_assessments', ['assessments' => $visibleassessments, 'courseid' => $courseid]); // ... include Late Assessments here? Yes as it is Yes or No only.

echo $OUTPUT->render_from_template('report_dashboard/header_end', []);



$usercount = count($userdataset);
$assessmentcount = count($visibleassessments);
$userassessmentscount = count($userassessments);

$userindex = 0;
$userassessmentindex = 0;

// ... Because we are using carefully sorted but separate arrays we need to do additional checking
if (($usercount * $assessmentcount) != $userassessmentscount) {
    throw new moodle_exception('User assessments count does not match user count * assessment count');
}

foreach($userdataset as $userobject) {
    $row = (array)$userobject;
    $currentuserid = $row['id'];

    /*
        Last accessed timestamp is 10000000000 if never accessed. This was done so when sorted by last accessed, the nevers are at the end. This is hardcoded in the SQL query as well.
    */

    if($row['lastaccessed_timestamp'] == -1) {
        $row['lastaccessed_filter_category'] = 'never';
        $row['lastaccessed_label'] = get_string('never', 'report_dashboard');

        // $row['lastaccessed_timestamp'] = -1;

    } else {
        $deltadays = floor((time() - $row['lastaccessed_timestamp']) / 86400);

        switch ($deltadays) {
            case 0:
                $row['lastaccessed_filter_category'] = 'today';
                $row['lastaccessed_label'] = 'Last 24 hrs'; // get_string('over_5_days', 'report_dashboard');
                break;
            case 1:
                $row['lastaccessed_filter_category'] = 'yesterday';
                $row['lastaccessed_label'] = '~ 1 day ago'; // get_string('over_1_days', 'report_dashboard');
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

    if($row['groups']) {
        foreach(explode(', ', $row['groups']) as $groupid) {
            $groupdetails = \report_dashboard\dashboard::get_item_by_id($coursegroups, $groupid);
            $groups[] = $groupdetails + ['class' => 'tag-course-group'];
        }
    }

    $cohortgroups = [];

    if($row['cohortgroups']) {
        foreach(explode(', ', $row['cohortgroups']) as $groupid) {
            $groupdetails = \report_dashboard\dashboard::get_item_by_id($coursecohortgroups, $groupid);
            $cohortgroups[] = $groupdetails + ['class' => 'tag-cohort-group'];
        }
    }

    $assessments = [];
    $lateassessments = false;

    for($assessmentindex = 0; $assessmentindex < $assessmentcount; $assessmentindex++) {
        $assessment = (array)$userassessments[$userassessmentindex + 1];

        // ... Because we are using carefully sorted but separate arrays we need to do additional checking
        if ($assessment['userid'] != $currentuserid) {
            throw new moodle_exception("Assessment user id: $assessment[userid] does not match current user id: $currentuserid where user assessment index = $userassessmentindex");
        }

        $label = $assessmentstatuses[$assessment['status']];

        if (in_array($assessment['status'], ['passed', 'failed'])) {
            $label = $assessment['grade'];
        }

        $assessments[] = $assessment + ['label' => $label];

        if($assessment['status'] == 'overdue') {
            $lateassessments = true;
        }

        $userassessmentindex += 1;
    }

    echo $OUTPUT->render_from_template('report_dashboard/row',
        ['row' => $row, 'groups' => $groups, 'cohort_groups' => $cohortgroups, 'assessments' => $assessments, 'lateassessments' => $lateassessments]);
}

// iterate over $dataset using render_from_template OR html_writer??

echo $OUTPUT->render_from_template('report_dashboard/footer', []);

echo $OUTPUT->footer();
