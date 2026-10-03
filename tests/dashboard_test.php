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
            'duedate' => $now + 86400, ]);

        $assessment2 = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course1->id,
            'name' => 'Assignment 2',
            'timeclose' => $now + 86400, ]);

        $assessment3 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course1->id,
            'name' => 'Test 1',
            'duedate' => $now + 86400, ]);

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
            ]);

        $ee2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'name' => 'Page 2',
            'idnumber' => 'EE2',
            ]);

        $earlyengagements = dashboard::get_early_engagements($course1->id);
        $this->assertEquals(2, count($earlyengagements));

        $userearlyengagements = dashboard::get_user_early_engagements($course1->id, '');
        $this->assertEquals(3 * 2, count($userearlyengagements));

        // Test hiding an early engagement.
        $userearlyengagements = dashboard::get_user_early_engagements($course1->id, $ee1->cmid);
        $this->assertEquals(3 * 1, count($userearlyengagements));
    }

    /**
     * Tests early engagement activities flagged via the activity setting and the legacy ID number.
     *
     * @covers ::dashboard
     */
    public function test_early_engagement_activity_setting(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);

        // Legacy approach: flagged via the ID number, with or without completion tracking.
        $legacy = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'idnumber' => 'EE1',
        ]);

        // Flagged via the activity setting.
        $flagged = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        // Flagged via both approaches.
        $both = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'idnumber' => 'EE2',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        // Flagged via the activity setting, but without completion conditions.
        $nocompletion = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'completion' => COMPLETION_TRACKING_NONE,
        ]);

        // Not flagged.
        $notflagged = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);

        foreach ([$flagged, $both, $nocompletion] as $page) {
            $DB->insert_record('report_dashboard_cm', ['cmid' => $page->cmid, 'earlyengagement' => 1]);
        }
        $DB->insert_record('report_dashboard_cm', ['cmid' => $notflagged->cmid, 'earlyengagement' => 0]);

        $earlyengagements = dashboard::get_early_engagements($course1->id);
        $this->assertEqualsCanonicalizing(
            [$legacy->cmid, $flagged->cmid, $both->cmid],
            array_column($earlyengagements, 'cmid')
        );

        // Activities flagged via the activity setting require course completion tracking.
        $DB->set_field('course', 'enablecompletion', COMPLETION_DISABLED, ['id' => $course1->id]);

        $earlyengagements = dashboard::get_early_engagements($course1->id);
        $this->assertEqualsCanonicalizing(
            [$legacy->cmid, $both->cmid],
            array_column($earlyengagements, 'cmid')
        );
    }

    /**
     * Tests viewed status for assessments and early engagements.
     *
     * @covers ::dashboard
     */
    public function test_viewed_status(): void {
        global $DB;

        $this->resetAfterTest(false);
        $this->setAdminUser();

        $now = time();

        // Create users.
        $user1 = $this->getDataGenerator()->create_user([
            'email' => 'user1@example.com',
            'username' => '98186051',
            'firstname' => 'Andy',
            'lastname' => 'Rowatt',
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'email' => 'user2@example.com',
            'username' => '98186052',
            'firstname' => 'Betty',
            'lastname' => 'Rowatt',
        ]);

        // Create course.
        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);

        // Create assessment with completion view enabled.
        $assessment1 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course1->id,
            'name' => 'Assignment 1',
            'duedate' => $now + 86400,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
        ]);

        // Create assessment without completion view.
        $assessment2 = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course1->id,
            'name' => 'Quiz 1',
            'timeclose' => $now + 86400,
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 0,
        ]);

        // Create early engagement with completion view enabled.
        $ee1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course1->id,
            'name' => 'Page 1',
            'idnumber' => 'EE1',
            'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionview' => 1,
        ]);

        $completion = new \completion_info($course1);

        // Get user assessments - should include viewed field.
        $items = dashboard::get_user_assessments($course1->id, '');
        $this->assertEquals(2 * 2, count($items)); // 2 users * 2 assessments.

        // Check initial state - no views recorded yet.
        foreach ($items as $ua) {
            if ($ua->assessmentid == 1) {
                $this->assertEquals(0, $ua->viewed);
            }
            if ($ua->assessmentid == 2) {
                $this->assertEquals(-1, $ua->viewed);
            }
        }

        $cm = get_coursemodule_from_id('assign', $assessment1->cmid);
        $completion->set_module_viewed($cm, $user1->id);

        // Get user assessments again.
        $items = dashboard::get_user_assessments($course1->id, '');
        foreach ($items as $ua) {
            if ($ua->assessmentid == 1) {
                if ($ua->userid == 1) {
                    $this->assertTrue($ua->viewed > 0);
                } else {
                    $this->assertEquals(0, $ua->viewed);
                }
            }
            if ($ua->assessmentid == 2) {
                $this->assertEquals(-1, $ua->viewed);
            }
        }

        // Test user early engagements.
        $items = dashboard::get_user_early_engagements($course1->id, '');
        $this->assertEquals(2 * 1, count($items)); // 2 users * 1 early engagement.

        // Check initial state - no views recorded yet.
        foreach ($items as $ua) {
            $this->assertEquals(0, $ua->viewed);
        }

        $cm = get_coursemodule_from_id('page', $ee1->cmid);
        $completion->set_module_viewed($cm, $user2->id);

        $items = dashboard::get_user_early_engagements($course1->id, '');
        foreach ($items as $ua) {
            if ($ua->userid == 2) {
                $this->assertTrue($ua->viewed > 0);
            } else {
                $this->assertEquals(0, $ua->viewed);
            }
        }
    }

    /**
     * Tests the course, Stream and other course last accessed timestamps in the user dataset.
     *
     * @covers ::get_user_dataset
     */
    public function test_lastaccessed_timestamps(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $now = time();
        $generator = $this->getDataGenerator();

        $course1 = $generator->create_course();
        $course2 = $generator->create_course();
        $course3 = $generator->create_course();

        // Never accessed Stream.
        $user1 = $generator->create_user(['username' => '98186061', 'lastaccess' => 0]);

        // Accessed other courses, but not this one.
        $user2 = $generator->create_user(['username' => '98186062', 'lastaccess' => $now - HOURSECS]);
        $generator->create_user_course_lastaccess($user2, $course2, $now - DAYSECS);
        $generator->create_user_course_lastaccess($user2, $course3, $now - 3 * DAYSECS);

        // Accessed this course only.
        $user3 = $generator->create_user(['username' => '98186063', 'lastaccess' => $now - HOURSECS]);
        $generator->create_user_course_lastaccess($user3, $course1, $now - 5 * DAYSECS);

        $generator->enrol_user($user1->id, $course1->id);
        $generator->enrol_user($user2->id, $course1->id);
        $generator->enrol_user($user3->id, $course1->id);

        $userdataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(3, count($userdataset));

        $this->assertEquals(-1, $userdataset[1]->lastaccessed_timestamp);
        $this->assertEquals(-1, $userdataset[1]->site_lastaccessed_timestamp);
        $this->assertEquals(-1, $userdataset[1]->othercourse_lastaccessed_timestamp);

        $this->assertEquals(-1, $userdataset[2]->lastaccessed_timestamp);
        $this->assertEquals($now - HOURSECS, $userdataset[2]->site_lastaccessed_timestamp);
        $this->assertEquals($now - DAYSECS, $userdataset[2]->othercourse_lastaccessed_timestamp);

        $this->assertEquals($now - 5 * DAYSECS, $userdataset[3]->lastaccessed_timestamp);
        $this->assertEquals($now - HOURSECS, $userdataset[3]->site_lastaccessed_timestamp);
        $this->assertEquals(-1, $userdataset[3]->othercourse_lastaccessed_timestamp);
    }

    /**
     * Tests the last accessed tooltip.
     *
     * @dataProvider lastaccessed_tooltip_provider
     * @covers ::get_lastaccessed_tooltip
     * @param int|null $courseago Seconds since this course was accessed, or null if never
     * @param int|null $siteago Seconds since Stream was accessed, or null if never
     * @param int|null $othercourseago Seconds since another course was accessed, or null if never
     * @param string $expected
     */
    public function test_get_lastaccessed_tooltip(?int $courseago, ?int $siteago, ?int $othercourseago, string $expected): void {
        $now = 1700000000;
        $timestamp = fn(?int $ago): int => $ago === null ? -1 : $now - $ago;

        $this->assertEquals($expected, dashboard::get_lastaccessed_tooltip(
            $timestamp($courseago),
            $timestamp($siteago),
            $timestamp($othercourseago),
            $now
        ));
    }

    /**
     * Data provider for {@see test_get_lastaccessed_tooltip}.
     *
     * @return array
     */
    public static function lastaccessed_tooltip_provider(): array {
        return [
            'Never accessed Stream' => [null, null, null, 'Never accessed Stream'],
            'Never accessed course, accessed Stream only' => [
                null, 5 * DAYSECS, null,
                'Last accessed Stream 5 days ago',
            ],
            'Never accessed course, accessed another course' => [
                null, 3 * DAYSECS, 3 * DAYSECS,
                'Last accessed another course in Stream 3 days ago',
            ],
            'Never accessed course, accessed another course and Stream since' => [
                null, HOURSECS, 10 * DAYSECS,
                "Last accessed another course in Stream 10 days ago\nLast accessed Stream in the last 24 hrs",
            ],
            'Accessed another course more recently' => [
                10 * DAYSECS, 36 * HOURSECS, 36 * HOURSECS,
                'Last accessed another course in Stream 1 day ago',
            ],
            'Accessed another course less recently' => [
                10 * DAYSECS, 3 * HOURSECS, 20 * DAYSECS,
                'Last accessed Stream in the last 24 hrs',
            ],
            'Accessed another course within 48 hrs of course' => [
                3 * DAYSECS, 2 * DAYSECS, 2 * DAYSECS,
                '',
            ],
            'Accessed Stream within 48 hrs of course' => [
                5 * DAYSECS, 4 * DAYSECS, null,
                '',
            ],
            'Accessed course recently' => [HOURSECS, HOURSECS, 10 * DAYSECS, ''],
        ];
    }

    /**
     * Tests the Stream access category.
     *
     * @dataProvider stream_access_category_provider
     * @covers ::get_stream_access_category
     * @param int|null $courseago Seconds since this course was accessed, or null if never
     * @param int|null $siteago Seconds since Stream was accessed, or null if never
     * @param int|null $othercourseago Seconds since another course was accessed, or null if never
     * @param string $expected
     */
    public function test_get_stream_access_category(?int $courseago, ?int $siteago, ?int $othercourseago, string $expected): void {
        $now = time();
        $timestamp = fn(?int $ago): int => $ago === null ? -1 : $now - $ago;

        $category = dashboard::get_stream_access_category(
            $timestamp($courseago),
            $timestamp($siteago),
            $timestamp($othercourseago)
        );
        $this->assertEquals($expected, $category);

        if ($category) {
            $this->assertArrayHasKey($category, dashboard::get_stream_access_categories());
        }
    }

    /**
     * Data provider for {@see test_get_stream_access_category}.
     *
     * @return array
     */
    public static function stream_access_category_provider(): array {
        return [
            'Never accessed Stream' => [null, null, null, 'streamnever'],
            'Never accessed course, accessed Stream only' => [null, 5 * DAYSECS, null, 'streamonly'],
            'Never accessed course, accessed another course' => [null, 3 * DAYSECS, 3 * DAYSECS, 'streamothercourse'],
            'Accessed another course and Stream since' => [null, HOURSECS, 10 * DAYSECS, 'streamothercourse'],
            'Accessed another course less recently' => [10 * DAYSECS, 3 * HOURSECS, 20 * DAYSECS, 'streamonly'],
            'Accessed another course within 48 hrs of course' => [3 * DAYSECS, 2 * DAYSECS, 2 * DAYSECS, ''],
            'Accessed course recently' => [HOURSECS, HOURSECS, 10 * DAYSECS, ''],
        ];
    }

    /**
     * Tests the Show on course dashboard report and Title override settings for assessments.
     *
     * @covers ::get_assessments
     * @covers ::get_user_assessments
     * @covers ::get_early_engagements
     */
    public function test_show_on_dashboard(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $now = time();
        $generator = $this->getDataGenerator();

        $course = $generator->create_course(['numsections' => 2, 'enablecompletion' => 1]);
        $user = $generator->create_user(['username' => '98186071']);
        $generator->enrol_user($user->id, $course->id);

        // Activities in course order: name, module, section, close/due date, show on dashboard, title override.
        // Only dated activities are shown by default.
        $activities = [
            ['Undated always first', 'assign', 1, 0, dashboard::SHOW_ALWAYS, ''],
            ['Dated later', 'assign', 1, $now + 2 * DAYSECS, dashboard::SHOW_AUTO, ''],
            ['Undated always after dated later', 'quiz', 1, 0, dashboard::SHOW_ALWAYS, ''],
            ['Dated never', 'quiz', 1, $now + DAYSECS, dashboard::SHOW_NEVER, ''],
            ['Undated auto', 'assign', 1, 0, dashboard::SHOW_AUTO, ''],
            ['Dated sooner', 'quiz', 2, $now + DAYSECS, dashboard::SHOW_AUTO, ''],
            ['Undated always after dated sooner', 'assign', 2, 0, dashboard::SHOW_ALWAYS, 'Short title'],
        ];
        $cmids = [];
        foreach ($activities as [$name, $module, $section, $date, $showondashboard, $titleoverride]) {
            $cmids[$name] = $generator->create_module($module, [
                'course' => $course->id,
                'section' => $section,
                'name' => $name,
                ($module == 'assign' ? 'duedate' : 'timeclose') => $date,
                'report_dashboard_showondashboard' => $showondashboard,
                'report_dashboard_titleoverride' => $titleoverride,
            ])->cmid;
        }

        // Undated activities follow the dated activity that precedes them in the course, ignoring those never shown.
        $expected = [
            'Undated always first',
            'Dated sooner',
            'Undated always after dated sooner',
            'Dated later',
            'Undated always after dated later',
        ];
        $items = array_values(dashboard::get_assessments($course->id));
        $this->assertEquals(
            array_map(fn($name) => $cmids[$name], $expected),
            array_column($items, 'cmid')
        );
        $this->assertEquals([1, 0, 1, 0, 1], array_map('intval', array_column($items, 'noduedate')));
        $this->assertEquals([null, null, 'Short title', null, null], array_column($items, 'titleoverride'));

        // Undated activities are never due.
        $statuses = array_column(array_values(dashboard::get_user_assessments($course->id, '')), 'status');
        $this->assertEquals(['notdue', 'notdue', 'notdue', 'notdue', 'notdue'], $statuses);

        // Title override for an early engagement activity.
        $page = $generator->create_module('page', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
            'report_dashboard_earlyengagement' => 1,
            'report_dashboard_titleoverride' => 'Short page',
        ]);
        $items = array_values(dashboard::get_early_engagements($course->id));
        $this->assertEquals($page->cmid, $items[0]->cmid);
        $this->assertEquals('Short page', $items[0]->titleoverride);
    }
}
