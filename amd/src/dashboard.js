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

/**
 * TODO describe module dashboard
 *
 * @module     report_dashboard/dashboard
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import $ from 'jquery';
import DataTable from 'report_dashboard/datatables';

export const init = (assessments, course) => {
    console.log(`AJR Init ${assessments} ${course}`);

    $(document).ready(function() {
        var table = new DataTable('#dashboard',
            {
                orderCellsTop: true,
                paging: false,
                language: {
                    search: "Filter:",
                    searchPlaceholder: "Start typing to filter"
                },
                dom: 'firtB',
            }
        );

        /* Custom filter for last access */
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            let row = $(table.row(dataIndex).node());

            /*
                Last access filter
            */
            let lastaccessed = document.querySelector("input[name='lastaccessed']:checked").value;

            if (lastaccessed != "all") {
                if (row.find(`td.tc_lastaccessed span[data-filter-category="${lastaccessed}"]`).length == 0) {
                    return false;
                }
            }

            /*
                Groups filter
            */
            let anygroupmatch = false; // By default we assume that no groups are matching
            let anygroupunchecked = false; // By default we assume that no groups are unchecked
            let anygroupchecked = false; // By default we assume that no groups are checked

            let groups = document.querySelectorAll("input[name='groups']");

            for (const group of groups) {
                if (group.checked) {
                    anygroupchecked = true;
                    if (row.find(`td.tc_groups span[data-filter-category="${group.value}"]`).length > 0) {
                        anygroupmatch = true;
                        break;
                    }
                } else {
                    anygroupunchecked = true;
                }
            }

            document.getElementById("group_select_all").disabled = !anygroupunchecked;
            document.getElementById("group_select_none").disabled = !anygroupchecked;

            if (!anygroupmatch) {
                return false;
            }

            /*
                Late assessments filter
            */
            let lateassessments = document.querySelector("input[name='lateassessments']:checked").value;

            if (lateassessments != "all") {
                if (row.find("td.tc_lateassessments").data("filter-category") != lateassessments) {
                    return false;
                }
            }

            return true;
        });

        $("tr.filters").on("click", (e) => {
            /*
                Special handling cases for filters
            */
            switch (e.target.id) {
                case "group_select_all":
                    document.querySelectorAll("input[name='groups']").forEach((group) => {
                        group.checked = true;
                    });
                    break;
                case "group_select_none":
                    document.querySelectorAll("input[name='groups']").forEach((group) => {
                        group.checked = false;
                    });
                    break;
            }
            table.draw();
        });

    });
};