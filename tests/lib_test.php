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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/dashboard/lib.php');

/**
 * Tests for the course module preferences callbacks.
 *
 * @package     report_dashboard
 * @category    test
 * @copyright   2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class lib_test extends \advanced_testcase {
    /**
     * Tests saving the early engagement setting.
     *
     * @covers ::report_dashboard_coursemodule_edit_post_actions
     */
    public function test_coursemodule_edit_post_actions(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);

        // Not flagged when created, so nothing is saved.
        $page1 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
            'report_dashboard_earlyengagement' => 0,
        ]);
        $this->assertFalse($DB->record_exists('report_dashboard_cm', ['cmid' => $page1->cmid]));

        // Flagged when created.
        $page2 = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
            'report_dashboard_earlyengagement' => 1,
        ]);
        $this->assertEquals(1, $DB->get_field('report_dashboard_cm', 'earlyengagement', ['cmid' => $page2->cmid]));

        // The setting is not submitted while disabled, so the saved value is kept.
        report_dashboard_coursemodule_edit_post_actions((object) ['coursemodule' => $page2->cmid], $course);
        $this->assertEquals(1, $DB->get_field('report_dashboard_cm', 'earlyengagement', ['cmid' => $page2->cmid]));

        // Unflagged.
        report_dashboard_coursemodule_edit_post_actions(
            (object) ['coursemodule' => $page2->cmid, 'report_dashboard_earlyengagement' => 0],
            $course
        );
        $this->assertEquals(0, $DB->get_field('report_dashboard_cm', 'earlyengagement', ['cmid' => $page2->cmid]));

        // Flagged when updated.
        report_dashboard_coursemodule_edit_post_actions(
            (object) ['coursemodule' => $page1->cmid, 'report_dashboard_earlyengagement' => 1],
            $course
        );
        $this->assertEquals(1, $DB->get_field('report_dashboard_cm', 'earlyengagement', ['cmid' => $page1->cmid]));
    }

    /**
     * Tests saving the Show on course dashboard report and Title override settings.
     *
     * @covers ::report_dashboard_coursemodule_edit_post_actions
     */
    public function test_coursemodule_edit_post_actions_assessment(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        // Defaults when created, so nothing is saved.
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'report_dashboard_showondashboard' => dashboard::SHOW_AUTO,
            'report_dashboard_titleoverride' => '  ',
        ]);
        $this->assertFalse($DB->record_exists('report_dashboard_cm', ['cmid' => $assign->cmid]));

        // Always shown, with a title override.
        report_dashboard_coursemodule_edit_post_actions(
            (object) [
                'coursemodule' => $assign->cmid,
                'report_dashboard_showondashboard' => dashboard::SHOW_ALWAYS,
                'report_dashboard_titleoverride' => ' Short title ',
            ],
            $course
        );
        $record = $DB->get_record('report_dashboard_cm', ['cmid' => $assign->cmid]);
        $this->assertEquals(dashboard::SHOW_ALWAYS, $record->showondashboard);
        $this->assertEquals('Short title', $record->titleoverride);
        $this->assertEquals(0, $record->earlyengagement);

        // Never shown, and the title override is cleared.
        report_dashboard_coursemodule_edit_post_actions(
            (object) [
                'coursemodule' => $assign->cmid,
                'report_dashboard_showondashboard' => dashboard::SHOW_NEVER,
                'report_dashboard_titleoverride' => '',
            ],
            $course
        );
        $record = $DB->get_record('report_dashboard_cm', ['cmid' => $assign->cmid]);
        $this->assertEquals(dashboard::SHOW_NEVER, $record->showondashboard);
        $this->assertNull($record->titleoverride);

        // A title override alone is saved for any activity.
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'report_dashboard_titleoverride' => 'Short page',
        ]);
        $record = $DB->get_record('report_dashboard_cm', ['cmid' => $page->cmid]);
        $this->assertEquals('Short page', $record->titleoverride);
        $this->assertEquals(dashboard::SHOW_AUTO, $record->showondashboard);
    }

    /**
     * Tests the course dashboard preferences are copied when an activity is duplicated (backup and restore).
     *
     * @covers \backup_report_dashboard_plugin
     * @covers \restore_report_dashboard_plugin
     */
    public function test_duplicate_module(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $flagged = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
            'report_dashboard_earlyengagement' => 1,
        ]);
        $notflagged = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'report_dashboard_showondashboard' => dashboard::SHOW_ALWAYS,
            'report_dashboard_titleoverride' => 'Short title',
        ]);

        $modinfo = get_fast_modinfo($course);
        $newflagged = duplicate_module($course, $modinfo->get_cm($flagged->cmid));
        $newnotflagged = duplicate_module($course, $modinfo->get_cm($notflagged->cmid));
        $newassign = duplicate_module($course, $modinfo->get_cm($assign->cmid));

        $this->assertEquals(1, $DB->get_field('report_dashboard_cm', 'earlyengagement', ['cmid' => $newflagged->id]));
        $this->assertFalse($DB->record_exists('report_dashboard_cm', ['cmid' => $newnotflagged->id]));
        $record = $DB->get_record('report_dashboard_cm', ['cmid' => $newassign->id]);
        $this->assertEquals(dashboard::SHOW_ALWAYS, $record->showondashboard);
        $this->assertEquals('Short title', $record->titleoverride);
    }

    /**
     * Tests the course module preferences are deleted with the course module or course.
     *
     * @covers ::report_dashboard_pre_course_module_delete
     * @covers \report_dashboard\hook_callbacks::before_course_deleted
     */
    public function test_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course1 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $course2 = $this->getDataGenerator()->create_course(['enablecompletion' => COMPLETION_ENABLED]);
        $pages = [];
        foreach ([$course1, $course1, $course2] as $course) {
            $pages[] = $this->getDataGenerator()->create_module('page', [
                'course' => $course->id,
                'completion' => COMPLETION_TRACKING_MANUAL,
                'report_dashboard_earlyengagement' => 1,
            ]);
        }
        $this->assertEquals(3, $DB->count_records('report_dashboard_cm'));

        course_delete_module($pages[0]->cmid);
        $this->assertFalse($DB->record_exists('report_dashboard_cm', ['cmid' => $pages[0]->cmid]));
        $this->assertEquals(2, $DB->count_records('report_dashboard_cm'));

        delete_course($course1, false);
        $this->assertFalse($DB->record_exists('report_dashboard_cm', ['cmid' => $pages[1]->cmid]));
        $this->assertTrue($DB->record_exists('report_dashboard_cm', ['cmid' => $pages[2]->cmid]));
    }
}
