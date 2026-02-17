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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps in plugin report_dashboard
 *
 * @package    report_dashboard
 * @category   test
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_report_dashboard extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * Recognised page names are:
     * | pagetype          | name meaning | description                    |
     * | Dashboard         | Course full  | The dashboard page (index.php) |
     *
     * @param string $type identifies which type of page this is, e.g. 'Dashboard'.
     * @param string $name course short name
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $name): moodle_url {
        global $DB;

        switch (strtolower($type)) {
            case 'dashboard':
                $course = $DB->get_record('course', ['fullname' => $name], '*', MUST_EXIST);
                return new moodle_url('/report/dashboard/index.php', ['id' => $course->id]);
            default:
                throw new Exception('Unrecognised dashboard page type "' . $type . '."');
        }
    }

    /**
     * Changes the DataTables page length via the page-length selector.
     *
     * @When I set the dashboard page length to :length
     * @param string $length The page length value to select (e.g. "5", "25", "All")
     */
    public function i_set_the_dashboard_page_length_to(string $length): void {
        $select = $this->find('css', '.dt-length select');
        $select->selectOption($length);
    }

    /**
     * Checks that the filter count attribute for a given filter option equals the expected value.
     *
     * The filter label element carries a data-filter-count attribute that is updated by JS
     * to reflect the number of matching rows (across all DataTables pages).
     *
     * @Then the filter count for :value in :name should be :count
     * @param string $value  The filter option value (e.g. "overdue")
     * @param string $name   The filter input name (e.g. "assessment1_filter")
     * @param string $count  The expected count
     */
    public function the_filter_count_for_should_be(string $value, string $name, string $count): void {
        $xpath = "//input[@name='{$name}'][@value='{$value}']/parent::label[@data-filter-count='{$count}']";
        $node = $this->find('xpath', $xpath);
        if (!$node) {
            throw new \Behat\Mink\Exception\ExpectationException(
                "Expected filter count '{$count}' for {$name}={$value}, but element not found.",
                $this->getSession()
            );
        }
    }
}
