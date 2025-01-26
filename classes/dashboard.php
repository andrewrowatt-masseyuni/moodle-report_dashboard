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
     * Simple helper function to get an item from a dataset by id. Assumes id is unique within the dataset.
     *
     * @param array $dataset
     * @param int $id
     * @return array
     */
    public static function get_item_by_id(array $dataset, int|string $id): array {
        $retval = [];

        foreach ($dataset as $item) {
            if ($item['id'] == $id) {
                $retval = $item;
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
        return [
            ['id' => 1,'groupname' => 'Group 1', 'groupdescription' => 'Group 1 description', 'membercount' => 10],
            ['id' => 2,'groupname' => 'Group 2 - Keith\'s tutorial group', 'groupdescription' => 'Group 2 description', 'membercount' => 5],
            ['id' => 6,'groupname' => 'Group 3', 'groupdescription' => 'Group 3 description', 'membercount' => 0],
        ];

        /*
        global $DB;

        // ... Get course groups with count of members. Exclude cohort groups. groupid, groupname, groupdescription, membercount

        $sql = "SELECT g.id, g.name
                FROM {groups} g
                JOIN {groups_members} gm ON g.id = gm.groupid
                WHERE gm.courseid = :courseid
                GROUP BY g.id, g.name";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
        */
    }

    public static function get_cohort_groups(int $courseid): array{
        return [
            ['id' => 4, 'groupname' => 'Auckland', 'groupdescription' => 'Auckland description', 'membercount' => 8],
            ['id' => 5, 'groupname' => 'Distance', 'groupdescription' => 'Distance description', 'membercount' => 7],
        ];

        /*
        global $DB;

        // ... Get cohort groups (i.e., a group linked to a meta-link enrolment method) with count of members. groupid, groupname, groupdescription, membercount

        $sql = "SELECT c.id, c.name
                FROM {cohort} c
                JOIN {cohort_members} cm ON c.id = cm.cohortid
                WHERE cm.courseid = :courseid
                GROUP BY c.id, c.name";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
        */
    }

    public static function get_assessments(int $courseid): array {
        return [
            ['id' => 1, 'name' => 'Assignment 1'],
            ['id' => 2, 'name' => 'Assignment 2'],
            ['id' => 3, 'name' => 'Exam'],
        ];

        /*

        global $DB;

        // ... Get course assessments

        $sql = "SELECT a.id, a.name
                FROM {assign} a
                WHERE a.course = :courseid";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
        */
    }

    public static function get_assessment_status_string(string $status): string {
        return get_string("assessmentstatus_$status", 'report_dashboard');
    }

    public static function get_assessment_statuses(): array {
        return [
            'notdue' => self::get_assessment_status_string('notdue'),
            'submitted' => self::get_assessment_status_string('submitted'),
            'overdue' => self::get_assessment_status_string('overdue'),
            'passed' => self::get_assessment_status_string('passed'),
            'failed' => self::get_assessment_status_string('failed'),
            'extension' => self::get_assessment_status_string('extension'),
        ];
    }

    public static function get_dataset(int $courseid): array {
        $data = [
            [
                'userid' => 1,
                'username' => '98186700',
                'firstname' => 'Andrew',
                'lastname' => 'Rowatt',
                'email' => 'andrewrowatt@gmail.com',
                'lastaccessed_timestamp' =>  1737847764 - 86400 * 8,
                'groups' => [1, 2],
                'cohort_groups' => [4],
                'international' => 0,
                'maori' => 0,
                'pacific' => 0,
                'new' => 0,
                'lateassessments' => 1,

                'assessment0_grade' => 0,
                'assessment0_status' => 'overdue',
                'assessment0_extension_date' => 1737867307,

                'assessment1_grade' => 0,
                'assessment1_status' => 'notdue',
                'assessment1_extension_date' => 1737867307,

                'assessment2_grade' => 0,
                'assessment2_status' => 'notdue',
                'assessment2_extension_date' => 1737867307,

                'course_total_grade' => 0,
            ],
            [
                'userid' => 2,
                'username' => '94088951',
                'firstname' => 'Patrick',
                'lastname' => 'Rynhart',
                'email' => 'patrick@gmail.com',
                'lastaccessed_timestamp' => -1,
                'lastaccessed_status' => 'Never',
                'groups' => [1,6],
                'cohort_groups' => [5],
                'international' => 0,
                'maori' => 0,
                'pacific' => 0,
                'new' => 0,
                'lateassessments' => 0,

                'assessment0_grade' => 0,
                'assessment0_status' => 'submitted',
                'assessment0_extension_date' => 0,
                'assessment1_grade' => 0,
                'assessment1_status' => 'notdue',
                'assessment1_extension_date' => 0,
                'assessment2_grade' => 0,
                'assessment2_status' => 'notdue',
                'assessment2_extension_date' => 0,

                'course_total_grade' => 0,
            ],
            [
                'userid' => 1,
                'username' => '23006700',
                'firstname' => 'McKenzie',
                'lastname' => 'Rowatt',
                'email' => 'andrewrowatt@gmail.com',
                'lastaccessed_timestamp' =>  1737847764 - 86400 * 7,
                'groups' => [],
                'cohort_groups' => [4],
                'international' => 0,
                'maori' => 0,
                'pacific' => 0,
                'new' => 1,
                'lateassessments' => 0,

                'assessment0_grade' => 0,
                'assessment0_status' => 'notdue',
                'assessment0_extension_date' => 0,
                'assessment1_grade' => 0,
                'assessment1_status' => 'notdue',
                'assessment1_extension_date' => 0,
                'assessment2_grade' => 0,
                'assessment2_status' => 'notdue',
                'assessment2_extension_date' => 0,

                'course_total_grade' => 0,
            ],
        ];
        
        return $data;
    }
}
