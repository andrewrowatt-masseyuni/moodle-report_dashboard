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
import DataTable from 'report_dashboard/dataTables';
import 'report_dashboard/dataTables.bootstrap';
import 'report_dashboard/dataTables.select';
// ... Remove for debugging import 'report_dashboard/dataTables.fixedHeader';
// ... Remove for debugging import 'report_dashboard/dataTables.fixedColumns';

export const init = () => {
    $(function() {
        var table = new DataTable('#report_dashboard_dashboard',
            {
                orderCellsTop: true,
                paging: false,
                language: {
                    search: "Filter:",
                    searchPlaceholder: "Start typing to filter"
                },
                responsive: false,
                autoWidth: false,
                dom: 'irt',
                columnDefs: [
                    {
                        orderable: false,
                        render: DataTable.render.select(),
                        targets: 0
                    }
                ],
                select: {
                    style: 'multi',
                    headerCheckbox: 'select-page'
                },
                order: [[1, 'asc']], /* Removes order symbol from column 0 (checkbox) */
                /* fixedHeader: true, */
                scrollX: true,
                fixedColumns: {
                    start: 2
                },
            }
        );

        /* eslint complexity: ["error", {"max": 25 }] */
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            console.log("Filtering data");

            let row = $(table.row(dataIndex).node());
            /*
                Name filter
            */
            let name = document.getElementById("name_filter").value.toLowerCase();
            let namematch = false;

            if (name == "") {
                namematch = true;
            } else {
                if (row.find("td.tc_name").first().text().toLowerCase().includes(name)) {
                    namematch = true;
                }
            }

            if (!namematch) {
                return false;
            }


            /*
                Last access filter
            */
            let lastaccessed = document.querySelector("input[name='lastaccessed']:checked");
            document.querySelector("#lastaccessed > button > span").textContent = lastaccessed.dataset.label;

            if (lastaccessed.value != "all") {
                if (row.find(`td.tc_lastaccessed span[data-filter-category="${lastaccessed.value}"]`).length == 0) {
                    return false;
                }
            }

            /*
                Groups filter
            */
            let groupmatch = false; // By default we assume that no groups are matching

            let groups = document.querySelectorAll("input[name='groups']");
            let groupschecked = document.querySelectorAll("input[name='groups']:checked").length;
            let anygroupunchecked = groups.length != groupschecked;

            let dropdownlabel = "Unknown"; // Default label for the dropdown

            for (const group of groups) {
                if (group.checked) {
                    dropdownlabel = group.dataset.label;

                    if (row.find(`td.tc_groups span[data-filter-category="${group.value}"]`).length > 0) {
                        groupmatch = true;
                        break;
                    }
                }
            }

            document.getElementById("group_select_all").disabled = !anygroupunchecked;
            document.getElementById("group_select_none").disabled = groupschecked == 0;


            if (groupschecked == 0) {
                dropdownlabel = "None";
            } else if (groupschecked == 1) {
                // dropdownlabel = "1 Group";
            } else if (anygroupunchecked) {
                dropdownlabel = "Multiple Groups";
            } else {
                dropdownlabel = "All";
            }

            document.querySelector("#group > button > span").textContent = dropdownlabel;

            if (!groupmatch) {
                return false;
            }

            /*
                Late assessments filter
            */
            let lateassessments = document.querySelector("input[name='lateassessments']:checked");
            document.querySelector("#lateassessments > button > span").textContent = lateassessments.dataset.label;

            if (lateassessments.value != "all") {
                if (row.find(`td.tc_lateassessments span[data-filter-category="${lateassessments.value}"]`).length == 0) {
                    return false;
                }
            }

            /*
                Assessments filter
            */

            let assessmentFilters = document.querySelectorAll(".assessment_filter");

            for (const assessmentFilter of assessmentFilters) {
                let id = assessmentFilter.dataset.assessmentid;
                let itemMatch = false;
                let items = document.querySelectorAll(`input[name='assessment${id}_filter']`);
                let itemsChecked = document.querySelectorAll(`input[name='assessment${id}_filter']:checked`).length;
                let anyUnchecked = items.length != itemsChecked;

                let dropdownlabel = "Unknown"; // Default label for the dropdown

                for (const item of items) {
                    if (item.checked) {
                        dropdownlabel = item.dataset.label;

                        if (row.find(`td.tc_assessment.assessment${id} span[data-filter-category="${item.value}"]`).length > 0) {
                            itemMatch = true;
                            break;
                        }
                    }
                }

                document.getElementById(`assessment${id}_select_all`).disabled = !anyUnchecked;
                document.getElementById(`assessment${id}_select_none`).disabled = itemsChecked == 0;

                if (itemsChecked == 0) {
                    dropdownlabel = "None";
                } else if (itemsChecked == 1) {
                    // dropdownlabel = "1 Group";
                    console.log("1 item checked!");
                } else if (anyUnchecked) {
                    dropdownlabel = "Multiple criteria";
                } else {
                    dropdownlabel = "All";
                }

                document.querySelector(`#assessment${id} > button > span`).textContent = dropdownlabel;

                if (!itemMatch) {
                    return false;
                }
            }


            return true;
        });

        $("#name_filter").on("input", function() {
            table.draw();
        });

        $("tr.filters .assessment_filter .dropdown-menu").on("click", (e) => {
            let id = e.currentTarget.dataset.assessmentid;

            console.log("event1");
            switch (e.target.id) {
                case `assessment${id}_select_all`:
                    document.querySelectorAll(`input[name='assessment${id}_filter']`).forEach((item) => {
                        item.checked = true;
                    });
                    break;
                case `assessment${id}_select_none`:
                    document.querySelectorAll(`input[name='assessment${id}_filter']`).forEach((item) => {
                        item.checked = false;
                    });
                    break;
            }
        });

        $("tr.filters .dropdown-menu").on("click", (e) => {
            /*
                Special handling cases for filters
            */
                console.log("event2");

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