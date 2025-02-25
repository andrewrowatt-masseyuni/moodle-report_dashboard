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
            ['id' => 1, 'groupname' => 'Albany', 'groupdescription' => '', 'membercount' => 18],
['id' => 2, 'groupname' => 'Distance', 'groupdescription' => '', 'membercount' => 4],
['id' => 3, 'groupname' => 'Manawatu', 'groupdescription' => '', 'membercount' => 16],

            

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

    public static function get_user_assessments(int $courseid, array $hiddenassessmentcmids): array {
        // ... Hidden assessments are excluded!

        $data = [
            ['userid' => 1, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 1, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 2, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 2, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 3, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 3, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 4, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 4, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 5, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 5, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 6, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 6, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 7, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 7, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 8, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 8, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 9, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 9, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 10, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 10, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 11, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 11, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 12, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 12, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 13, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 13, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 14, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 14, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 15, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 15, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 16, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 16, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 17, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 17, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 18, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 18, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 19, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 19, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 20, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 20, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 21, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 21, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 22, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 22, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 23, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 23, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 24, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 24, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 25, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 25, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 26, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 26, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 27, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 27, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 28, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 28, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 29, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 29, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 30, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 30, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 31, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 31, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 32, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 32, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 33, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 33, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 34, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 34, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 35, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 35, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 36, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 36, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 37, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 37, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 38, 'assessmentid' => 1, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            ['userid' => 38, 'assessmentid' => 2, 'status' => 'notdue', 'extension_date' => 0, 'grade' => -1],
            

        ];

        return $data;

        /*

        global $DB;

        // ... Get user assessments. Must be ordered by [last, first, username, userid]="user sortorder" then assessment sortorder (not id).

        $sql = "SELECT a.id, a.name
                FROM {assign} a
                WHERE a.course = :courseid";
        return $DB->get_records_sql($sql, ['courseid' => $courseid]);
        */
    }

    public static function get_assessments(int $courseid): array {
        /*
            Get course assessments. 
            Must be ordered by assessment sortorder. 
            Hidden assessments are included, but flagged as hidden. 
            This then becomes the "master list" of hidden assessments as if a assessment has been hiden previously but now deleted it will not appear in this list.
        */

        $data = [
            ['id' => 1, 'name' => 'Assignment 1: Academic essay', 'assessmentcmid' => 5357148],
            ['id' => 2, 'name' => 'Assignment 2: Academic Report', 'assessmentcmid' => 5357153],
        ];

        return $data;

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
            'graded' => self::get_assessment_status_string('graded'),
        ];
    }

    public static function get_user_dataset(int $courseid): array {
        // ...Must be ordered by [last, first, username, userid]="user sortorder"

        $data = [
            ['id' => 1, 'userid' => 170447, 'username' => '21001408', 'firstname' => 'Lan', 'lastname' => 'Ji','email' => '1791613044@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 2, 'userid' => 150336, 'username' => '21018829', 'firstname' => 'Jingya', 'lastname' => 'Hu','email' => '732119214@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 0, 'new' => 0],
            ['id' => 3, 'userid' => 164191, 'username' => '22005666', 'firstname' => 'Sachin', 'lastname' => 'Kaluarachchige Don','email' => 'sachinmomotharonaruto@gmail.com', 'lastaccessed_timestamp' => 1740202468, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 4, 'userid' => 170451, 'username' => '22006948', 'firstname' => 'Tianle', 'lastname' => 'Wang','email' => '1813652037@qq.com', 'lastaccessed_timestamp' => 1739166115, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 5, 'userid' => 159466, 'username' => '22009430', 'firstname' => 'Gao Xiang', 'lastname' => 'Gao','email' => '2062556132@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 6, 'userid' => 157290, 'username' => '22011982', 'firstname' => 'Zherui', 'lastname' => 'Fei','email' => '3150134671@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 7, 'userid' => 157293, 'username' => '22011985', 'firstname' => 'Wuchen', 'lastname' => 'Li','email' => '2939663064@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 8, 'userid' => 160618, 'username' => '22014333', 'firstname' => 'Junfan', 'lastname' => 'Kang','email' => '3185644030@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 9, 'userid' => 161845, 'username' => '22014937', 'firstname' => 'Jingyun', 'lastname' => 'Yang','email' => '1491094465@qq.com', 'lastaccessed_timestamp' => 1739947872, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 10, 'userid' => 161466, 'username' => '22015431', 'firstname' => 'Le', 'lastname' => 'Zhang','email' => '2335462150@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 11, 'userid' => 172097, 'username' => '23009591', 'firstname' => 'Fengren', 'lastname' => 'Zhang','email' => '1497862940@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 12, 'userid' => 172100, 'username' => '23009693', 'firstname' => 'Shuchang', 'lastname' => 'Xu','email' => 'xscdlg@163.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 13, 'userid' => 167036, 'username' => '23011675', 'firstname' => 'Kuluni', 'lastname' => 'Mannapperuma','email' => 'kulunivihansa2001@gmail.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 14, 'userid' => 178152, 'username' => '23013538', 'firstname' => 'Chenchao', 'lastname' => 'Tian','email' => 'tcc20050221@163.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 15, 'userid' => 169307, 'username' => '23015325', 'firstname' => 'Lily', 'lastname' => 'Cai','email' => '3270076406@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 16, 'userid' => 168742, 'username' => '23016590', 'firstname' => 'Amy', 'lastname' => 'Ding','email' => 'amydingjiayi@gmail.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 0, 'new' => 0],
            ['id' => 17, 'userid' => 170963, 'username' => '23017905', 'firstname' => 'Zhongkang', 'lastname' => 'Ji','email' => '1903765783@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 18, 'userid' => 172180, 'username' => '23018384', 'firstname' => 'Linchang', 'lastname' => 'Shi','email' => '3071392169@qq.com', 'lastaccessed_timestamp' => 1739247347, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 19, 'userid' => 171389, 'username' => '23018946', 'firstname' => 'Katrina', 'lastname' => 'Sun','email' => 'foxsun04@gmail.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 0, 'new' => 0],
            ['id' => 20, 'userid' => 170082, 'username' => '23019320', 'firstname' => 'Arshirin', 'lastname' => 'Phoenixia','email' => 'arshirin.phoenixia@gmail.com', 'lastaccessed_timestamp' => 1740015102, 'groups' => [], 'cohortgroups' => [2], 'maori' => 0, 'pacific' => 0, 'international' => 0, 'new' => 0],
            ['id' => 21, 'userid' => 170265, 'username' => '23019437', 'firstname' => 'Yue', 'lastname' => 'Cheng','email' => '2137259435@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 22, 'userid' => 170655, 'username' => '23020019', 'firstname' => 'Eva', 'lastname' => 'Zhang','email' => '594875761@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 23, 'userid' => 170860, 'username' => '23020427', 'firstname' => 'Xin', 'lastname' => 'Jiang','email' => '1328380788@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 24, 'userid' => 176310, 'username' => '24002851', 'firstname' => 'Ziyan', 'lastname' => 'Pei','email' => '13356711666@126.com', 'lastaccessed_timestamp' => 1740137163, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 25, 'userid' => 181552, 'username' => '24005036', 'firstname' => 'Yuping', 'lastname' => 'Tao','email' => 'taoangela@iCloud.com', 'lastaccessed_timestamp' => 1739779335, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 26, 'userid' => 177320, 'username' => '24005979', 'firstname' => 'Xiya Yang', 'lastname' => 'Yang','email' => 'yxy13708700696@163.com', 'lastaccessed_timestamp' => 1740045039, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 1],
            ['id' => 27, 'userid' => 181534, 'username' => '24007252', 'firstname' => 'Yiwei', 'lastname' => 'Zhang','email' => '170736543@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 28, 'userid' => 179539, 'username' => '24012134', 'firstname' => 'Ziming', 'lastname' => 'Wang','email' => 'Zimingwang66610@gmail.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [1], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 29, 'userid' => 181009, 'username' => '24013023', 'firstname' => 'Qingyang', 'lastname' => 'Xu','email' => 'xqy210@outlook.com', 'lastaccessed_timestamp' => 1740198884, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 30, 'userid' => 181925, 'username' => '24021634', 'firstname' => 'Yuhang', 'lastname' => 'Xiang','email' => '1691134747@qq.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 0],
            ['id' => 31, 'userid' => 188621, 'username' => '24023204', 'firstname' => 'Shenyu', 'lastname' => 'Li','email' => 'lsy0404222191@163.com', 'lastaccessed_timestamp' => 1740205863, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 1],
            ['id' => 32, 'userid' => 189019, 'username' => '24023414', 'firstname' => 'Yanxin', 'lastname' => 'Xu','email' => '757931560@qq.com', 'lastaccessed_timestamp' => 1740221685, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 1],
            ['id' => 33, 'userid' => 180274, 'username' => '25001640', 'firstname' => 'Caterina', 'lastname' => 'Molinari','email' => 'kate2411@live.it', 'lastaccessed_timestamp' => 1739917971, 'groups' => [], 'cohortgroups' => [2], 'maori' => 0, 'pacific' => 0, 'international' => 0, 'new' => 0],
            ['id' => 34, 'userid' => 187369, 'username' => '25005829', 'firstname' => 'Hannah', 'lastname' => 'Li','email' => 'hannahlict@gmail.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 1],
            ['id' => 35, 'userid' => 183869, 'username' => '25008167', 'firstname' => 'Mu Ka Hbaw', 'lastname' => 'Saw Mu Ka Hbaw Aye','email' => 'sawmukahbaw191@gmail.com', 'lastaccessed_timestamp' => 1739534548, 'groups' => [], 'cohortgroups' => [2], 'maori' => 0, 'pacific' => 0, 'international' => 0, 'new' => 1],
            ['id' => 36, 'userid' => 188686, 'username' => '25017886', 'firstname' => 'Mizuki', 'lastname' => 'Hamamoto','email' => '230ww058@st.nufs.ac.jp', 'lastaccessed_timestamp' => 1740172293, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 0, 'international' => 1, 'new' => 1],
            ['id' => 37, 'userid' => 189380, 'username' => '25018838', 'firstname' => 'Oshendra Perera', 'lastname' => 'Perera','email' => 'oshendraperera@gmail.com', 'lastaccessed_timestamp' => 1740047838, 'groups' => [], 'cohortgroups' => [2], 'maori' => 0, 'pacific' => 0, 'international' => 0, 'new' => 1],
            ['id' => 38, 'userid' => 189037, 'username' => '25019152', 'firstname' => 'April', 'lastname' => 'Matautia','email' => 'apsmatautia54@gmail.com', 'lastaccessed_timestamp' => -1, 'groups' => [], 'cohortgroups' => [3], 'maori' => 0, 'pacific' => 1, 'international' => 0, 'new' => 1],
                         
        ];
        
        return $data;
    }
}
