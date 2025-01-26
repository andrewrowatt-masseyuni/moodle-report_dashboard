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

// ... DataTables requirements
$PAGE->requires->css('/report/dashboard/libs/datatables.min.css');

// ...Just testing passing parameters to JavaScript
$data = [];
$data['assessments'] = 2;
$data['course'] = $course->shortname;

$PAGE->requires->js_call_amd('report_dashboard/dashboard', 'init', $data);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_dashboard'));

// ... Need to output global instructions - or - include that in the header template?
echo "<p>TODO</p>";

// echo $OUTPUT->render_from_template('report_dashboard/static_test', $data);

$data = [];

$coursegroups = \report_dashboard\dashboard::get_groups($courseid);
$coursecohortgroups = \report_dashboard\dashboard::get_cohort_groups($courseid);

$assessments = \report_dashboard\dashboard::get_assessments($courseid);
$assessmentstatuses = \report_dashboard\dashboard::get_assessment_statuses();

$dataset = \report_dashboard\dashboard::get_dataset($courseid);

echo $OUTPUT->render_from_template('report_dashboard/header_headings', ['assessments' => $assessments]);
echo $OUTPUT->render_from_template('report_dashboard/header_filter_name', $data);
echo $OUTPUT->render_from_template('report_dashboard/header_filter_groups', ['cohort_groups' => $coursecohortgroups,'groups' => $coursegroups]);
echo $OUTPUT->render_from_template('report_dashboard/header_filter_assessments', ['assessments' => $assessments]); // ... include Late Assessments here? Yes as it is Yes or No only.

echo $OUTPUT->render_from_template('report_dashboard/header_end', []);

foreach($dataset as $row) {
    if($row['lastaccessed_timestamp'] == -1) {
        $row['lastaccessed_filter_category'] = 'never';
        $row['lastaccessed_label'] = get_string('never', 'report_dashboard');

    } else {
        $delta_days = floor((time() - $row['lastaccessed_timestamp']) / 86400);
        switch ($delta_days) {
            case 0:
                $row['lastaccessed_filter_category'] = 'today';
                $row['lastaccessed_label'] = 'Last 24 hrs'; // get_string('over_5_days', 'report_dashboard');
                break;
            case 1:
                $row['lastaccessed_filter_category'] = 'yesterday';
                $row['lastaccessed_label'] = '> 1 day ago'; // get_string('over_1_days', 'report_dashboard');
                break;
            case $delta_days < 7:
                $row['lastaccessed_filter_category'] = '1week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_n_days', 'report_dashboard',$delta_days);
                break;
            case $delta_days < 14:
                $row['lastaccessed_filter_category'] = 'over1week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_1_week', 'report_dashboard');
                break;
            case $delta_days < 21:
                $row['lastaccessed_filter_category'] = 'over2week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_2_week', 'report_dashboard');
                break;
            case $delta_days < 28:
                $row['lastaccessed_filter_category'] = 'over3week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_3_week', 'report_dashboard');
                break;
            default:
                $row['lastaccessed_filter_category'] = 'over4week';
                $row['lastaccessed_label'] = get_string('lastaccessed_over_28_days', 'report_dashboard');
        }
    }

    $groups = [];

    foreach($row['groups'] as $group_id) {
        $group_details = \report_dashboard\dashboard::get_item_by_id($coursegroups, $group_id);
        $groups[] = $group_details + ['class' => 'tag-course-group'];
    }

    foreach($row['cohort_groups'] as $group_id) {
        $group_details = \report_dashboard\dashboard::get_item_by_id($coursecohortgroups, $group_id);
        $groups[] = $group_details + ['class' => 'tag-cohort-group'];
    }

    $assessments = [];
    $assessments[] = [
        'grade' => $row['assessment0_grade'], 
        'status' => $row['assessment0_status'],
        'extension_date' => $row['assessment0_extension_date'],
        'label' => $assessmentstatuses[$row['assessment0_status']]];

    $assessments[] = ['grade' => $row['assessment1_grade'], 'status' => $row['assessment1_status'], 'extension_date' => $row['assessment1_extension_date'],
        'label' => $assessmentstatuses[$row['assessment1_status']]];
    $assessments[] = ['grade' => $row['assessment2_grade'], 'status' => $row['assessment2_status'], 'extension_date' => $row['assessment2_extension_date'],
        'label' => $assessmentstatuses[$row['assessment2_status']]];





    echo $OUTPUT->render_from_template('report_dashboard/row', ['row' => $row, 'groups' => $groups, 'assessments' => $assessments]);
}

// iterate over $dataset using render_from_template OR html_writer??

echo $OUTPUT->render_from_template('report_dashboard/footer',[]);

// var_dump($dataset);

echo $OUTPUT->footer();
