# This file is part of Moodle - http://moodle.org/
#
# Moodle is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Moodle is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

# Tests for the report_dashboard course module settings.
#
# @package    report_dashboard
# @copyright  2026 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@report @report_dashboard @javascript
Feature: Flag an activity as an early engagement activity
  In order to show the completion status of an activity on the course dashboard
  As a teacher
  I need to flag the activity as an early engagement activity in its settings

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname     | shortname | enablecompletion |
      | Completion   | C1        | 1                |
      | NoCompletion | C2        | 0                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher1 | C2     | editingteacher |
    And the following "activities" exist:
      | activity | name   | course | completion |
      | page     | Page 1 | C1     | 0          |
      | page     | Page 2 | C2     | 0          |

  Scenario: The early engagement setting depends on activity completion conditions
    Given I am on the "Page 1" "page activity editing" page logged in as "teacher1"
    When I expand all fieldsets
    Then I should see "Course dashboard preferences"
    And the field "Show in course dashboard as an early engagement activity" matches value "No"
    And the "Show in course dashboard as an early engagement activity" "field" should be disabled
    And I set the field "Students must manually mark the activity as done" to "1"
    And the "Show in course dashboard as an early engagement activity" "field" should be enabled
    And I set the field "Show in course dashboard as an early engagement activity" to "Yes"
    And I press "Save and return to course"
    And I am on the "Page 1" "page activity editing" page
    And I expand all fieldsets
    And the field "Show in course dashboard as an early engagement activity" matches value "Yes"
    And the "Show in course dashboard as an early engagement activity" "field" should be enabled

  Scenario: The early engagement setting is disabled when course completion tracking is disabled
    Given I am on the "Page 2" "page activity editing" page logged in as "teacher1"
    When I expand all fieldsets
    Then I should see "Course dashboard preferences"
    And the field "Show in course dashboard as an early engagement activity" matches value "No"
    And the "Show in course dashboard as an early engagement activity" "field" should be disabled
