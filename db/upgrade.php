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
 * Plugin upgrade steps are defined here.
 *
 * @package     report_dashboard
 * @category    upgrade
 * @copyright   2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute report_dashboard upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_report_dashboard_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026100300) {
        // Define table report_dashboard_cm to be created.
        $table = new xmldb_table('report_dashboard_cm');

        // Adding fields to table report_dashboard_cm.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('earlyengagement', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table report_dashboard_cm.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('cmid', XMLDB_KEY_FOREIGN_UNIQUE, ['cmid'], 'course_modules', ['id']);

        // Conditionally launch create table for report_dashboard_cm.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Dashboard savepoint reached.
        upgrade_plugin_savepoint(true, 2026100300, 'report', 'dashboard');
    }

    if ($oldversion < 2026100400) {
        $table = new xmldb_table('report_dashboard_cm');

        // Define field showondashboard to be added to report_dashboard_cm.
        $field = new xmldb_field('showondashboard', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'earlyengagement');

        // Conditionally launch add field showondashboard.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field titleoverride to be added to report_dashboard_cm.
        $field = new xmldb_field('titleoverride', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'showondashboard');

        // Conditionally launch add field titleoverride.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Dashboard savepoint reached.
        upgrade_plugin_savepoint(true, 2026100400, 'report', 'dashboard');
    }

    return true;
}
