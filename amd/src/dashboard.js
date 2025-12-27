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
import 'report_dashboard/dataTables.bootstrap4';
import 'report_dashboard/dataTables.select';
import 'report_dashboard/dataTables.buttons';
import 'report_dashboard/buttons.bootstrap4';
import 'report_dashboard/buttons.html5';

export const init = () => {
    $(function() {
        var table = new DataTable('#report_dashboard_dashboard',
            {
                orderCellsTop: true,
                responsive: false,
                autoWidth: true,
                paging: false,
                layout: {
                    topStart: 'info',
                    topEnd: null,
                    bottomStart: {
                        buttons: [
                            {
                                extend: 'excelHtml5',
                                text: 'Export to Excel',
                            },
                            {
                                text: 'Copy email addresses of selected students',
                                className: 'customButton customButtonCopyEmailAddress btn btn-secondary',
                                action: function(e, dt) {
                                    var r = dt.rows({selected: true});
                                    var s = '';
                                    for (var i = 0; i < r.count(); i++) {
                                        s += r.cell(i, 2).node().dataset.formattedEmail + ';';
                                    }
                                    navigator.clipboard.writeText(s);
                                }
                            },
                            {
                                text: 'Create email to selected students...',
                                className: 'customButton customButtonCreateEmail btn btn-secondary',
                                action: function(e, dt) {
                                    var r = dt.rows({selected: true});
                                    var s = '';
                                    for (var i = 0; i < r.count(); i++) {
                                        s += r.cell(i, 2).node().dataset.formattedEmail + ';';
                                    }
                                    location.href = `mailto:?bcc=${encodeURIComponent(s)}`;
                                }
                            },
                            {
                                text: 'Clear selected rows',
                                className: 'customButton customButtonClearSelectedRows btn btn-secondary',
                                action: function(e, dt) {
                                    dt.rows({selected: true}).deselect();
                                }
                            }
                        ]
                    }
                },
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
                initComplete: function() {
                    // Adjust the columns when the table is initialised.
                    // this.api().columns([2]).visible(false);
                }
            }
        );

        updateFilterCounts(true);

        table.on('draw', function() {
            updateFilterCounts(false);
        });

        /* eslint complexity: ["error", {"max": 30 }] */
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            let row = $(table.row(dataIndex).node());

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
                // The dropdownlabel will be set by code above.
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
                Early engagements filter
            */

            let earlyengagementFilters = document.querySelectorAll(".earlyengagement_filter");

            for (const earlyengagementFilter of earlyengagementFilters) {
                let id = earlyengagementFilter.dataset.earlyengagementid;
                let itemMatch = false;
                let items = document.querySelectorAll(`input[name='earlyengagement${id}_filter']`);
                let itemsChecked = document.querySelectorAll(`input[name='earlyengagement${id}_filter']:checked`).length;
                let anyUnchecked = items.length != itemsChecked;

                let dropdownlabel = "Unknown"; // Default label for the dropdown

                for (const item of items) {
                    if (item.checked) {
                        dropdownlabel = item.dataset.label;

                        // eslint-disable-next-line max-len
                        if (row.find(`td.tc_earlyengagement.earlyengagement${id} span[data-filter-category="${item.value}"]`).length > 0) {
                            itemMatch = true;
                            break;
                        }
                    }
                }

                document.getElementById(`earlyengagement${id}_select_all`).disabled = !anyUnchecked;
                document.getElementById(`earlyengagement${id}_select_none`).disabled = itemsChecked == 0;

                if (itemsChecked == 0) {
                    dropdownlabel = "None";
                } else if (itemsChecked == 1) {
                    // The dropdownlabel will be set by code above.
                } else if (anyUnchecked) {
                    dropdownlabel = "Multiple criteria";
                } else {
                    dropdownlabel = "All";
                }

                document.querySelector(`#earlyengagement${id} > button > span`).textContent = dropdownlabel;

                if (!itemMatch) {
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
                let items = document.querySelectorAll(`label:not([data-filter-total="0"]) > input[name='assessment${id}_filter']`);
                // eslint-disable-next-line max-len
                let itemsChecked = document.querySelectorAll(`label:not([data-filter-total="0"]) > input[name='assessment${id}_filter']:checked`).length;
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
                    // The dropdownlabel will be set by code above.
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
            // ... Modern search approach
            table.columns(1).search(this.value).draw();
        });

        $("tr.filters .earlyengagement_filter .dropdown-menu").on("click", (e) => {
            let id = e.currentTarget.dataset.earlyengagementid;

            switch (e.target.id) {
                case `earlyengagement${id}_select_all`:
                    document.querySelectorAll(`input[name='earlyengagement${id}_filter']`).forEach((item) => {
                        if (!item.disabled) {
                            item.checked = true;
                        }
                    });
                    break;
                case `earlyengagement${id}_select_none`:
                    document.querySelectorAll(`input[name='earlyengagement${id}_filter']`).forEach((item) => {
                        item.checked = false;
                    });
                    break;
            }
        });

        $("tr.filters .assessment_filter .dropdown-menu").on("click", (e) => {
            let id = e.currentTarget.dataset.assessmentid;

            switch (e.target.id) {
                case `assessment${id}_select_all`:
                    document.querySelectorAll(`input[name='assessment${id}_filter']`).forEach((item) => {
                        if (!item.disabled) {
                            item.checked = true;
                        }
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

            switch (e.target.id) {
                case "group_select_all":
                    document.querySelectorAll("input[name='groups']").forEach((group) => {
                        if (!group.disabled) {
                            group.checked = true;
                        }
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

        /**
         * Update counts for filters
         *
         * @param {boolean} firstTime
         */
        function updateFilterCounts(firstTime) {
            $('[data-filter-count] > input').each(function(i, e) {
                let filter = e.value;
                let scope = e.name.replace("_filter", "");

                let count = $(`td.${scope} [data-filter-category="${filter}"]`).length;
                e.parentElement.dataset.filterCount = count;
                if (firstTime) {
                    e.parentElement.dataset.filterTotal = count;
                    e.disabled = !count;
                    if (!count) {
                        e.checked = false;
                    }
                    e.parentElement.classList.toggle("text-muted", !count);
                }
            });
        }
    });
};