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

$data = [];
$data['assessments'] = 2;
$data['course'] = $course->shortname;

$PAGE->requires->js_call_amd('report_dashboard/dashboard', 'init', $data);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_dashboard'));

echo "<p>TODO</p>";

$data = [];

echo $OUTPUT->render_from_template('report_dashboard/static_test', $data);

echo $OUTPUT->footer();
