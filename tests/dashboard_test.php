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

namespace report_dashboard;

/**
 * The dashboard test class.
 *
 * @package     report_dashboard
 * @category    test
 * @copyright   2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class dashboard_test extends \advanced_testcase {

    // Write the tests here as public funcions.
    // Please refer to {@link https://docs.moodle.org/dev/PHPUnit} for more details on PHPUnit tests in Moodle.

    /**
     * Tests all dataset operations in a single offering ("one to one") course.
     *
     * @covers ::dashboard
     */
    public function test_single_offering_course(): void {
        global $DB;

        $this->resetAfterTest(false);
        $this->setAdminUser();

        $now = time();

        // Basic user test.

        $user1 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186011',
            'firstname' => 'Andy',
            'lastname' => 'Rowatt',
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186013',
            'firstname' => 'Betty',
            'lastname' => 'Rowatt',
        ]);

        $user3 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186012',
            'firstname' => 'Carol',
            'lastname' => 'Rowatt',
        ]);

        $user4 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186014',
            'firstname' => 'David',
            'lastname' => 'Rowatt',
        ]);

        $course1 = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course1->id);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(4, count($userdataset));

        // Basic assessment test.

        $assessment1 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course1->id,
            'name' => 'Assignment 1',
            'duedate' => $now + 86400, ]
        );

        $assessment2 = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course1->id,
            'name' => 'Assignment 2',
            'timeclose' => $now + 86400, ]
        );

        $assessment3 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course1->id,
            'name' => 'Test 1',
            'duedate' => $now + 86400, ]
        );

        $assessmentdataset = dashboard::get_assessments($course1->id);
        $this->assertEquals(3, count($assessmentdataset));

        // User assessment test.

        $userassessmentdataset = dashboard::get_user_assessments($course1->id, '');
        $this->assertEquals(4 * 3, count($userassessmentdataset));

        // Test hiding assessments.
        $userassessmentdataset = dashboard::get_user_assessments($course1->id, "$assessment1->cmid $assessment2->cmid");
        $this->assertEquals(4 * 1, count($userassessmentdataset));

        // Advanced user test - groups.

        $group1 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'group1',
        ]);

        $group2 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'group2',
        ]);

        // This is an unused group so it should be excluded from the dataset.
        $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'group3',
        ]);

        $this->getDataGenerator()->create_group_member([
            'userid' => $user1->id,
            'groupid' => $group1->id,
        ]);

        $this->getDataGenerator()->create_group_member([
            'userid' => $user2->id,
            'groupid' => $group1->id,
        ]);

        $this->getDataGenerator()->create_group_member([
            'userid' => $user2->id,
            'groupid' => $group2->id,
        ]);

        $groupsdataset = dashboard::get_groups($course1->id);

        // Groups with NO members are not included in the dataset.
        $this->assertEquals(2, count($groupsdataset));

        $this->assertEquals(2, $groupsdataset[1]->membercount);
        $this->assertEquals(1, $groupsdataset[2]->membercount);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(4, count($userdataset));

        // Note: The groups field is a comma separated list of group row_indexes NOT group IDs.
        $this->assertEquals('1', $userdataset[1]->groups);

        // Note: User #3 as users were (intentionally) created in a different order.
        $this->assertEquals('1, 2', $userdataset[3]->groups);
    }

    /**
     * Tests targetted dataset operations in a multiple offering course.
     *
     * @covers ::dashboard
     */
    public function test_metalink_course(): void {
        $this->resetAfterTest(false);
        $this->setAdminUser();


        // Basic user test.

        $user1 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186021',
            'firstname' => 'Andy',
            'lastname' => 'Rowatt',
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186023',
            'firstname' => 'Betty',
            'lastname' => 'Rowatt',
        ]);

        $user3 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186022',
            'firstname' => 'Carol',
            'lastname' => 'Rowatt',
        ]);

        $user4 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186024',
            'firstname' => 'David',
            'lastname' => 'Rowatt',
        ]);

        // Create master course and cohort groups.
        $course1 = $this->getDataGenerator()->create_course(['shortname' => 'mastercourse']);
        $cohortgroup1 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'cohortgroup1',
        ]);
        $cohortgroup2 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'cohortgroup2',
        ]);

        // Create offering courses.
        $course2 = $this->getDataGenerator()->create_course(['shortname' => 'mastercourse-child1']);
        $course3 = $this->getDataGenerator()->create_course(['shortname' => 'mastercourse-child2']);

        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course3->id);

        // Meta enrolment plugin is not enabled by default.
        set_config('enrol_plugins_enabled', 'self,manual,meta');

        // Meta-link the offering courses to the master course.
        $metaplugin = enrol_get_plugin('meta');
        $metaplugin->add_instance($course1, [
            'customint1' => $course2->id,
            'customint2' => $cohortgroup1->id,
        ]);

        $metaplugin->add_instance($course1, [
            'customint1' => $course3->id,
            'customint2' => $cohortgroup2->id,
        ]);

        // Start duplication from the single offering course test.
        // Advanced user test - groups.

        $group1 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'group1',
        ]);

        $group2 = $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'group2',
        ]);

        // This is an unused group so it should be excluded from the dataset.
        $this->getDataGenerator()->create_group([
            'courseid' => $course1->id,
            'name' => 'group3',
        ]);

        $this->getDataGenerator()->create_group_member([
            'userid' => $user1->id,
            'groupid' => $group1->id,
        ]);

        $this->getDataGenerator()->create_group_member([
            'userid' => $user2->id,
            'groupid' => $group1->id,
        ]);

        $this->getDataGenerator()->create_group_member([
            'userid' => $user2->id,
            'groupid' => $group2->id,
        ]);

        $groupsdataset = dashboard::get_groups($course1->id);

        // Groups with NO members are not included in the dataset.
        $this->assertEquals(2, count($groupsdataset));

        $this->assertEquals(2, $groupsdataset[1]->membercount);
        $this->assertEquals(1, $groupsdataset[2]->membercount);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(4, count($userdataset));

        // Note: The groups field is a comma separated list of group row_indexes NOT group IDs.
        $this->assertEquals('1', $userdataset[1]->groups);

        // Note: User #3 as users were (intentionally) created in a different order.
        $this->assertEquals('1, 2', $userdataset[3]->groups);

        // End duplication from the single offering course test.

        // Check cohort groups and membership.
        $cohortgroupsdataset = dashboard::get_cohort_groups($course1->id);

        $this->assertEquals(2, count($cohortgroupsdataset));

        $this->assertEquals('1', $userdataset[1]->cohortgroups);
        $this->assertEquals('1', $userdataset[2]->cohortgroups);
        $this->assertEquals('1', $userdataset[3]->cohortgroups);
        $this->assertEquals('2', $userdataset[4]->cohortgroups);
    }

    /**
     * Tests previous enrolments logic.
     *
     * @covers ::dashboard
     */
    public function test_previous_enrolments(): void {
        $this->resetAfterTest(false);
        $this->setAdminUser();

        // set_config('mastersql', str_replace('mdl_', 'phpu_', dashboard::get_default_mastersql()), 'report_dashboard');

        // Basic user test.

        $user1 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186031',
            'firstname' => 'Andy',
            'lastname' => 'Rowatt',
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186032',
            'firstname' => 'Betty',
            'lastname' => 'Rowatt',
        ]);

        $user3 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186033',
            'firstname' => 'Carol',
            'lastname' => 'Rowatt',
        ]);

        // Create master course and cohort groups.
        $course1 = $this->getDataGenerator()->create_course(['shortname' => '100101_2025_S1FS']);

        // Create offering courses.
        $course1cc1 = $this->getDataGenerator()->create_course(['idnumber' => '100101_2025_S1FS_MTUI']);
        $course1cc2 = $this->getDataGenerator()->create_course(['idnumber' => '100101_2025_S1FS_DISD']);

        $this->getDataGenerator()->enrol_user($user1->id, $course1cc1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1cc1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1cc1->id);

        // Meta enrolment plugin is not enabled by default.
        set_config('enrol_plugins_enabled', 'self,manual,meta');

        // Meta-link the offering courses to the master course.
        $metaplugin = enrol_get_plugin('meta');
        $metaplugin->add_instance($course1, [
            'customint1' => $course1cc1->id,
        ]);

        $metaplugin->add_instance($course1, [
            'customint1' => $course1cc2->id,
        ]);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(3, count($userdataset));

        $this->assertEquals('', $userdataset[1]->previous_enrolments);
        $this->assertEquals('', $userdataset[2]->previous_enrolments);
        $this->assertEquals('', $userdataset[3]->previous_enrolments);

        // Simulate a previous enrolment by creating a new course with older offering(s).
        $course2 = $this->getDataGenerator()->create_course(['idnumber' => '100101_2024_S2FS_MTUI']);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(3, count($userdataset));

        $this->assertEquals('2024 S2', $userdataset[1]->previous_enrolments);
        $this->assertEquals('', $userdataset[2]->previous_enrolments);
        $this->assertEquals('', $userdataset[3]->previous_enrolments);

        // Simulate a previous enrolment by creating a new course with older offering(s).
        $course3 = $this->getDataGenerator()->create_course(['shortname' => '100101_2023_S1FS']);
        $course3cc1 = $this->getDataGenerator()->create_course(['idnumber' => '100101_2023_S1FS_MTUI']);
        $course3cc2 = $this->getDataGenerator()->create_course(['idnumber' => '100101_2023_S1FS_DISD']);

        $metaplugin->add_instance($course3, [
            'customint1' => $course3cc1->id,
        ]);

        $metaplugin->add_instance($course3, [
            'customint1' => $course3cc2->id,
        ]);

        $this->getDataGenerator()->enrol_user($user1->id, $course3cc1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course3cc2->id);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(3, count($userdataset));

        $this->assertEquals('2024 S2, 2023 S1', $userdataset[1]->previous_enrolments);
        $this->assertEquals('2023 S1', $userdataset[2]->previous_enrolments);
        $this->assertEquals('', $userdataset[3]->previous_enrolments);

    }

    /**
     * Tests early engagement logic.
     *
     * @covers ::dashboard
     */
    public function test_early_engagement(): void {
        $this->resetAfterTest(false);
        $this->setAdminUser();

        // set_config('mastersql', str_replace('mdl_', 'phpu_', dashboard::get_default_mastersql()), 'report_dashboard');

        // Basic user test.

        $user1 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186041',
            'firstname' => 'Andy',
            'lastname' => 'Rowatt',
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186042',
            'firstname' => 'Betty',
            'lastname' => 'Rowatt',
        ]);

        $user3 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186043',
            'firstname' => 'Carol',
            'lastname' => 'Rowatt',
        ]);

        // Create master course and cohort groups.
        $course1 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(3, count($userdataset));

        $ee1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'name' => 'Page 1',
            'idnumber' => 'EE1',
            ]
        );

        $ee2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'name' => 'Page 2',
            'idnumber' => 'EE2',
            ]
        );

        $earlyengagements = dashboard::get_early_engagements($course1->id);
        $this->assertEquals(2, count($earlyengagements));

        $userearlyengagements = dashboard::get_user_early_engagements($course1->id, '');
        $this->assertEquals(3 * 2, count($userearlyengagements));

        // Test hiding an early engagement.
        $userearlyengagements = dashboard::get_user_early_engagements($course1->id, $ee1->cmid);
        $this->assertEquals(3 * 1, count($userearlyengagements));

    }

}
