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

# Tests for report_dashboard plugin.
#
# @package    report_dashboard
# @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

@report @report_dashboard @javascript
Feature: Course Dashboard Report
  In order to monitor student progress and engagement
  As a teacher
  I need to view and interact with the course dashboard

  Background:
    Given the following "custom profile fields" exist:
      | datatype | shortname           | name                |
      | text     | InternationalStatus | InternationalStatus |
      | text     | Ethnicity           | Ethnicity           |
      | text     | TotalCreditsEarned  | TotalCreditsEarned  |
    And the following "roles" exist:
      | shortname              |
      | priority_group_support |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email                | profile_field_InternationalStatus | profile_field_Ethnicity | profile_field_TotalCreditsEarned |
      | teacher1 | Teacher   | One      | teacher1@example.com |                                   |                         |                                  |
      | support1 | Support   | One      | support1@example.com |                                   |                         |                                  |
      | 12345601 | 12345601  | Student  | 12345601@example.com |                                   | Māori                   |                                  |
      | 12345602 | 12345602  | Student  | 12345602@example.com |                                   |                         |                                  |
      | 12345603 | 12345603  | Student  | 12345603@example.com | Y                                 | Māori/Samoan            |                                  |
      | 12345604 | 12345604  | Student  | 12345604@example.com | N                                 | Tongan                  |                                  |
      | 12345605 | 12345605  | Student  | 12345605@example.com |                                   |                         |                                  |
      | 12345606 | 12345606  | Student  | 12345606@example.com |                                   |                         |                                  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | support1 | C1     | priority_group_support |
      | support1 | C1     | teacher |
      | 12345601 | C1     | student        |
      | 12345602 | C1     | student        |
      | 12345603 | C1     | student        |
      | 12345604 | C1     | student        |
      | 12345605 | C1     | student        |
      | 12345606 | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
      | Group B | C1     | GB       |
    And the following "group members" exist:
      | user     | group |
      | 12345601 | GA    |
      | 12345602 | GB    |
      | 12345605 | GB    |
      | 12345606 | GB    |
    And the following "activities" exist:
      | activity | name              | course | idnumber | duedate        | completion | completionview |
      | assign   | Test Assignment 1 | C1     | A1       | ##yesterday##  | 2          | 1              |
      | assign   | Test Assignment 2 | C1     | A2       | ##tomorrow##   | 0          | 0              |
      | forum    | Early Forum       | C1     | EE1      |                | 0          | 0              |
      | page     | Early Page        | C1     | EE2      |                | 0          | 0              |
    And the following "last access times" exist:
      | user     | course | lastaccess      |
      | 12345601 | C1     | ##today##       |
      | 12345602 | C1     | ##yesterday##   |
      | 12345604 | C1     | ##8 days ago##  |
      | 12345605 | C1     | ##15 days ago## |
      | 12345606 | C1     | ##22 days ago## |

    # And I log in as "admin"
    And the following config values are set as admin:
      | description    | text_description    | report_dashboard |
      | instructions   | text_instructions   | report_dashboard |
      | limitations    | text_limitations    | report_dashboard |
      | knownissues    | text_knownissues    | report_dashboard |
      | supportcontact | text_supportcontact | report_dashboard |

    And I change the window size to "large"

    And I am on the "A1" Activity page logged in as teacher1
    When I follow "Submissions"
    And I open the action menu in "12345601" "table_row"
    And I follow "Grant extension"
    And I should see "12345601"
    And I set the field "Enable" to "1"
    And I set the following fields to these values:
      | extensionduedate[year] | 2026 |
    And I press "Save changes"

    # Trigger view status
    And I am on the "A1" Activity page logged in as 12345601

  Scenario: Teacher can access the course dashboard
    Given I am on the "Course 1" "Course" page logged in as "teacher1"
    When I navigate to "Reports" in current page administration
    Then I should see "Course Dashboard"
    When I click on "Course Dashboard" "link"
    Then I should see "Course Dashboard"
    And I should see "Student ID / Firstname / Lastname"
    And I should see "accessed"
    And I should see "Groups"
    # Basic individual student information
    And I should see "12345601"
    And I should see "12345602"
    And I should see "12345603"
    And I should see "12345604"
    And I should see "12345605"
    And I should see "12345606"
    And I should see "Student"
    And I should see "Group A"
    And I should see "Group B"
    And I should see "Never"
    And I should see "Last 24 hrs"
    And I should see "~ 1 day ago"
    And I should see "> 1 week ago"
    And I should see "> 2 weeks ago"
    And I should see "> 3 weeks ago"

    Then I should not see "Māori" in the "12345601" "table_row"
    Then I should see "International" in the "12345603" "table_row"

    Then I should not see "Māori" in the "12345603" "table_row"
    Then I should not see "Pacific" in the "12345603" "table_row"

    Then I should see "Group B" in the "12345605" "table_row"
    Then I should see "Group B" in the "12345606" "table_row"

    Then I should see "Viewed" in the "12345601" "table_row"
    And I should not see "Not viewed" in the "12345601" "table_row"
    Then I should not see "Viewed" in the "12345602" "table_row"

  Scenario: Dashboard shows assignment columns
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    Then I should see "Test Assignment 1"
    And I should see "Test Assignment 2"
    And I should see "Lateassessments"

    Then I should see "Extension" in the "12345601" "table_row"
    Then I should not see "Extension" in the "12345602" "table_row"

  Scenario: Dashboard shows early engagement activities
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    Then I should see "Early Forum"
    And I should see "Early Page"

  Scenario: Dashboard shows ethnicity data for those with priority group role
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "support1"
    Then I should see "Māori" in the "12345601" "table_row"
    Then I should see "Māori" in the "12345603" "table_row"
    Then I should see "Pacific" in the "12345603" "table_row"

  Scenario: Teacher can hide an assessment
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    And I should see "Test Assignment 1"
    When I click on "button[title=\"Hide Test Assignment 1\"]" "css_element"
    Then I should not see "Test Assignment 1" in the "report_dashboard_dashboard" "table"
    And I should see "Show assessment Test Assignment 1"

  Scenario: Teacher can show a hidden assessment
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I click on "button[title=\"Hide Test Assignment 1\"]" "css_element"
    And I should see "Show assessment Test Assignment 1"
    When I click on "Show assessment Test Assignment 1" "button"
    Then I should see "Test Assignment 1" in the "report_dashboard_dashboard" "table"
    And I should not see "Show assessment Test Assignment 1"

  Scenario: Teacher can hide an early engagement activity
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    And I should see "Early Forum"
    When I click on "button[title=\"Hide Early Forum\"]" "css_element"
    Then I should not see "Early Forum" in the "report_dashboard_dashboard" "table"
    And I should see "Show early engagement activity Early Forum"

  Scenario: Teacher can show a hidden early engagement activity
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I click on "button[title=\"Hide Early Forum\"]" "css_element"
    And I should see "Show early engagement activity Early Forum"
    When I click on "Show early engagement activity Early Forum" "button"
    Then I should see "Early Forum" in the "report_dashboard_dashboard" "table"
    And I should not see "Show early engagement activity Early Forum"

  Scenario: Name filter works correctly
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I set the field "Start typing to filter by name or ID" to "12345601"
    Then I should see "12345601"
    And I should not see "12345602"
    And I should not see "12345603"

  Scenario: Last accessed filter is available
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I click on "#lastaccessed > button.dropdown-toggle" "css_element"
    Then I should see "Never"
    And I should see "In the last 24hrs"
    And I should see "Over 1 day ago"
    And I should see "In the last 7 days"
    And I should see "Over a week ago"

  Scenario: Groups filter is available
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I click on "#group > button.dropdown-toggle" "css_element"
    Then I should see "Select all"
    And I should see "Deselect all"
    And I should see "Course groups"
    And I should see "Group A"
    And I should see "Group B"
    And I should see "Priority groups"

  Scenario: Assessment filters are available
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I click on "#assessment1 > button.dropdown-toggle" "css_element"
    Then I should see "Select all"
    And I should see "Clear all"
    And I should see "Not viewed"
    And I should see "Viewed"
    And I should see "Not due"
    And I should see "Submitted"
    And I should see "Overdue"
    And I should see "Passed"
    And I should see "Failed"
    And I should see "Extension"

  Scenario: Early engagement filters are available
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I click on "#earlyengagement1 > button.dropdown-toggle" "css_element"
    Then I should see "Select all"
    And I should see "Clear all"
    And I should see "Not due"
    And I should see "Completed"
    And I should see "Not completed"
    And I should see "Overdue"

  Scenario: Late assessments filter is available
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    When I click on "#lateassessments > button.dropdown-toggle" "css_element"
    Then I should see "Yes"
    And I should see "No"

  Scenario: Dashboard shows collapsible information sections
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    And I should see "text_description"
    Then I should see "General notes and instructions"
    And I should see "Limitations"
    And I should see "Known issues"
    And I should see "text_supportcontact"
    Then I should not see "text_instructions"
    Then I should not see "text_limitations"
    Then I should not see "text_knownissues"
    When I click on "General notes and instructions" "button"
    Then I should see "text_instructions"
    When I click on "Limitations" "button"
    Then I should see "text_limitations"
    When I click on "Known issues" "button"
    Then I should see "text_knownissues"

  Scenario: Teacher can select students using checkboxes
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    Then I should see "Copy email addresses of selected students"
    And I should see "Create email to selected students..."
    # Note: The actual email functionality would require additional setup

  Scenario: Teacher can see Copy & Excel buttons
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "teacher1"
    Then I should see "Export to Excel"
    # Note: Testing the feature is out of scope

  Scenario: Student gets access denied when trying direct URL access
    Given I am on the "Course 1" "report_dashboard > dashboard" page logged in as "12345601"
    Then I should see "Sorry, but you do not currently have permissions to do that"
