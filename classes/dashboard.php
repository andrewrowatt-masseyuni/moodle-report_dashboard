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

namespace report_dashboard;

/**
 * Class dashboard
 *
 * @package    report_dashboard
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard {

    /**
     * Gets the Master SQL statement and appended the specific dataset required
     *
     * @param string $subquery
     * @return string
     */
    public static function get_master_sql(string $subquery): string {
        $mastersql = get_config('report_dashboard', 'mastersql');
        $mastersql .= "select * from $subquery";

        return $mastersql;
    }

    /**
     * Simple helper function to get an item from a dataset by id. Assumes id is unique within the dataset.
     *
     * @param array $dataset
     * @param int $id
     * @return array
     */
    public static function get_item_by_id(array $dataset, int $id): array {
        $retval = [];

        foreach ($dataset as $item) {
            if ($item->id == $id) {
                $retval = (array)$item;
            }
        }

        return $retval;
    }

    /**
     * Get course groups with count of members
     *
     * @param int $courseid
     * @return array
     */
    public static function get_groups(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(
        self::get_master_sql('get_groups'),
            ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    /**
     * Get course cohort groups with count of members
     *
     * @param int $courseid
     * @return array
     */
    public static function get_cohort_groups(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(
            self::get_master_sql('get_cohort_groups'),
            ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    /**
     * Gets all assessment status for all students in a course.
     *
     * @param int $courseid
     * @param string $hiddencmids
     * @return array
     */
    public static function get_user_assessments(int $courseid, string $hiddencmids): array {
        // ... Hidden assessments are excluded!

        global $DB, $USER;
        $data = $DB->get_records_sql(
            self::get_master_sql('get_user_assessments'),
            ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => $hiddencmids]);
        return $data;
    }

    /**
     * Gets all eligble assessments
     *
     * @param int $courseid
     * @return array
     */
    public static function get_assessments(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(
            self::get_master_sql('get_assessments'),
            ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    /**
     * Gets the status for all eligble early engagement activities for all users for a course.
     *
     * @param int $courseid
     * @param string $hiddencmids
     * @return array
     */
    public static function get_user_early_engagements(int $courseid, string $hiddencmids): array {
        // ... Hidden early engagement activites are excluded!

        global $DB, $USER;
        $data = $DB->get_records_sql(
            self::get_master_sql('get_user_early_engagements'),
            ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => $hiddencmids]);
        return $data;
    }

    /**
     * Gets all eligble early engagement activities for a course.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_early_engagements(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(
            self::get_master_sql('get_early_engagements'),
            ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    /**
     * Get the assessment statuses
     *
     * @return array
     */
    public static function get_assessment_statuses(): array {
        return [
            'notdue' => get_string('assessmentstatus_notdue', 'report_dashboard'),
            'submitted' => get_string('assessmentstatus_submitted', 'report_dashboard'),
            'overdue' => get_string('assessmentstatus_overdue', 'report_dashboard'),
            'passed' => get_string('assessmentstatus_passed', 'report_dashboard'),
            'failed' => get_string('assessmentstatus_failed', 'report_dashboard'),
            'extension' => get_string('assessmentstatus_extension', 'report_dashboard'),
            'graded' => get_string('assessmentstatus_graded', 'report_dashboard'),
        ];
    }

    /**
     * Get the earlyengagement statuses
     *
     * @return array
     */
    public static function get_earlyengagement_statuses(): array {
        return [
            'notdue' => get_string('earlyengagementstatus_notdue', 'report_dashboard'),
            'completed' => get_string('earlyengagementstatus_completed', 'report_dashboard'),
            'overdue' => get_string('earlyengagementstatus_overdue', 'report_dashboard'),
            'notcompleted' => get_string('earlyengagementstatus_notcompleted', 'report_dashboard'),
        ];
    }

    /**
     * Gets all users for a course.
     *
     * @param int $courseid
     * @return array
     */
    public static function get_user_dataset(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(
            self::get_master_sql('get_user_dataset'),
            ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }
}
