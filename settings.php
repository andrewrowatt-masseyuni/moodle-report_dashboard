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
 * Plugin administration pages are defined here.
 *
 * @package     report_dashboard
 * @category    admin
 * @copyright   2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('report_dashboard_settings', new lang_string('pluginname', 'report_dashboard'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        $settings->add(
            new admin_setting_configtextarea(
                'report_dashboard/description',
                new lang_string('description', 'report_dashboard'),
                new lang_string('description_desc', 'report_dashboard'),
                '', PARAM_RAW, 80, 3));
        $settings->add(
            new admin_setting_configtextarea(
                'report_dashboard/instructions',
                new lang_string('instructions', 'report_dashboard'),
                new lang_string('instructions_desc', 'report_dashboard'),
                '', PARAM_RAW, 80, 10));

        $settings->add(
            new admin_setting_configtextarea(
                'report_dashboard/limitations',
                new lang_string('limitations', 'report_dashboard'),
                new lang_string('limitations_desc', 'report_dashboard'),
                'The dashboard cannot be easily tailored for a particular course', PARAM_RAW, 80, 4));

        $settings->add(
            new admin_setting_configtextarea(
                'report_dashboard/knownissues',
                new lang_string('knownissues', 'report_dashboard'),
                new lang_string('knownissues_desc', 'report_dashboard'),
                '', PARAM_RAW, 80, 4));

        $settings->add(
            new admin_setting_configtextarea(
                'report_dashboard/supportcontact',
                new lang_string('supportcontact', 'report_dashboard'),
                new lang_string('supportcontact_desc', 'report_dashboard'),
                '', PARAM_RAW, 80, 3));

        $settings->add(
            new admin_setting_configtextarea(
                'report_dashboard/mastersql',
                new lang_string('mastersql', 'report_dashboard'),
                new lang_string('mastersql_desc', 'report_dashboard'),
                'select 1 as one', PARAM_RAW, 80, 20));
    }
}
