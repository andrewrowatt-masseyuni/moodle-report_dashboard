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
     * Dummy test.
     *
     * This is to be replaced by some actually useful test.
     *
     * @covers ::dashboard
     */
    public function test_basics(): void {
        global $DB, $USER;

        $this->resetAfterTest(false);
        $this->setAdminUser();

        set_config('mastersql', str_replace('mdl_','phpu_',dashboard::get_default_mastersql()), 'report_dashboard');

        $now = time();

        $user1 = $this->getDataGenerator()->create_user([
            'email'=>'user1@example.com',
            'username'=>'98186051',
            'firstname'=>'Andy',
            'lastname'=>'Rowatt',
        ]);

        $user2 = $this->getDataGenerator()->create_user([
            'email'=>'user1@example.com',
            'username'=>'98186053',
            'firstname'=>'Betty',
            'lastname'=>'Rowatt',
        ]);

        $user3 = $this->getDataGenerator()->create_user([
            'email'=>'user1@example.com',
            'username'=>'98186052',
            'firstname'=>'Carol',
            'lastname'=>'Rowatt',
        ]);

        $user4 = $this->getDataGenerator()->create_user([
            'email'=>'user1@example.com',
            'username'=>'98186054',
            'firstname'=>'David',
            'lastname'=>'Rowatt',
        ]);

        $course1 = $this->getDataGenerator()->create_course();

        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course1->id);

        $dataset = dashboard::get_user_dataset($course1->id);
        $this->assertEquals(4, count($dataset));

        $assessment1 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course1->id,
            'name' => 'Assignment 1',
            'duedate' => $now + 86400,]
        );

        $assessment2 = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course1->id,
            'name' => 'Assignment 2',
            'timeclose' => $now + 86400,]
        );

        $assessment3 = $this->getDataGenerator()->create_module('assign', [
            'course' => $course1->id,
            'name' => 'Test 1',
            'duedate' => $now + 86400,]
        );

        $dataset =  dashboard::get_assessments($course1->id);
        $this->assertEquals(3, count($dataset));

        $dataset = dashboard::get_user_assessments($course1->id, []);
        $this->assertEquals(4 * 3, count($dataset));

        // TO-DO: Add the remainder of get_XXX

    }
}
