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
import {setUserPreference} from 'core_user/repository';
import Chartjs from 'core/chartjs';

export const init = () => {
    $(function() {
        window.console.log('Report dashboard initialising'); // Debug log to confirm script is running
        var table = new DataTable('#report_dashboard_dashboard',
            {
                orderCellsTop: true,
                responsive: false,
                autoWidth: false,
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
                                    let s = '';
                                    // eslint-disable-next-line array-callback-return, no-unused-vars
                                    dt.rows({selected: true}).every((rowIdx, tableLoop, rowLoop) => {
                                        const node = this.cell(rowIdx, 2).node();
                                        s += node.dataset.formattedEmail + ';';
                                    });

                                    navigator.clipboard.writeText(s);
                                }
                            },
                            {
                                text: 'Create email to selected students...',
                                className: 'customButton customButtonCreateEmail btn btn-secondary',
                                action: function(e, dt) {
                                    var s = '';
                                    // eslint-disable-next-line array-callback-return, no-unused-vars
                                    dt.rows({selected: true}).every((rowIdx, tableLoop, rowLoop) => {
                                        const node = this.cell(rowIdx, 2).node();
                                        s += node.dataset.formattedEmail + ';';
                                    });
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
                    // Make any adjustments to the columns when the table is initialised.
                    this.api().columns([2]).visible(false); // Hide email column

                    // Reveal the table and hide the skeleton loader.
                    document.querySelector('.dashboard_container').classList.add('dt-ready');
                }
            }
        );

        document.getElementById('report_dashboard_fontsize').addEventListener('change', function() {
            const dashboardTable = document.getElementById('report_dashboard_dashboard');
            dashboardTable.className = dashboardTable.className.replace(/\bsz-\d+\b/, 'sz-' + this.value);
            setUserPreference('report_dashboard_fontsize', this.value);
        });

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
            updateVisibleCharts();
        }

        // Chart size constant (px).
        const CHART_SIZE = 200;

        // Ordered list of facet rings (inner to outer).
        const FACET_ORDER = ['engagement', 'submission', 'extension'];

        // Colors matching the CSS filter-category colours.
        const CATEGORY_COLORS = {
            notdue: '#e0e0e0',
            submitted: '#c5e0b4',
            completed: '#c5e0b4',
            overdue: '#fbe4d5',
            passed: '#c5e0b4',
            failed: '#fbe4d5',
            extension: '#d9edf7',
            notcompleted: '#d9edf7',
            viewed: '#c5e0b4',
            notviewed: '#bdd7ee',
            none: '#f5f5f5',
        };

        // Store Chart.js instances so we can destroy before re-rendering.
        const chartInstances = {};

        /**
         * Render a multi-ring doughnut chart inside the given container.
         * Rings are built from data-facet attributes on the table cells.
         *
         * @param {string} containerId
         */
        function renderChart(containerId) {
            const container = document.getElementById(containerId);
            if (!container || container.style.display === 'none') {
                return;
            }

            // Derive the column class from the container id, e.g. "chart_assessment5" -> "assessment5".
            const scope = containerId.replace('chart_', '');

            // Only count rows that pass ALL current filters (groups, last accessed, etc.).
            const visibleRows = table.rows({search: 'applied'}).nodes().toArray();
            if (visibleRows.length === 0) {
                return;
            }

            // Count occurrences grouped by facet then by filter-category.
            const facetCounts = {};
            FACET_ORDER.forEach(function(f) {
                facetCounts[f] = {};
            });

            var cellCount = 0;
            visibleRows.forEach(function(row) {
                const cell = row.querySelector('td.' + scope);
                if (!cell) {
                    return;
                }
                cellCount++;
                cell.querySelectorAll('[data-facet]').forEach(function(span) {
                    const facet = span.dataset.facet;
                    const category = span.dataset.filterCategory;
                    if (facet && category && facetCounts[facet] !== undefined) {
                        facetCounts[facet][category] = (facetCounts[facet][category] || 0) + 1;
                    }
                });
            });

            // For the extension ring, add a "none" complement so the ring is meaningful.
            if (facetCounts.extension && Object.keys(facetCounts.extension).length > 0) {
                const extCount = facetCounts.extension.extension || 0;
                facetCounts.extension.none = cellCount - extCount;
            }

            // Build one dataset per facet that has data.
            const datasets = [];
            FACET_ORDER.forEach(function(facet) {
                const counts = facetCounts[facet];
                const categories = Object.keys(counts);
                if (categories.length === 0) {
                    return;
                }
                datasets.push({
                    data: categories.map(function(c) {
                        return counts[c];
                    }),
                    backgroundColor: categories.map(function(c) {
                        return CATEGORY_COLORS[c] || '#cccccc';
                    }),
                    borderWidth: 1,
                    // Custom property used by the tooltip callback.
                    _labels: categories,
                    _facet: facet,
                });
            });

            if (datasets.length === 0) {
                return;
            }

            // Destroy any previous chart instance for this container.
            if (chartInstances[containerId]) {
                chartInstances[containerId].destroy();
            }

            container.innerHTML = '';
            const canvas = document.createElement('canvas');
            canvas.width = CHART_SIZE;
            canvas.height = CHART_SIZE;
            container.appendChild(canvas);

            chartInstances[containerId] = new Chartjs(canvas, {
                type: 'doughnut',
                data: {datasets: datasets},
                options: {
                    responsive: false,
                    maintainAspectRatio: true,
                    cutout: '20%',
                    plugins: {
                        legend: {display: false},
                        tooltip: {
                            callbacks: {
                                title: function(items) {
                                    if (items.length > 0) {
                                        return items[0].dataset._facet || '';
                                    }
                                    return '';
                                },
                                label: function(context) {
                                    var label = context.dataset._labels
                                        ? context.dataset._labels[context.dataIndex]
                                        : '';
                                    return label + ': ' + context.raw;
                                }
                            }
                        }
                    },
                    animation: false,
                }
            });
        }

        /**
         * Re-render all currently visible charts.
         */
        function updateVisibleCharts() {
            document.querySelectorAll('.rdb-chart-container').forEach(function(container) {
                if (container.style.display !== 'none') {
                    renderChart(container.id);
                }
            });
        }

        // Master chart toggle button – toggles ALL charts at once.
        var chartsVisible = false;
        document.getElementById('rdb-chart-toggle').addEventListener('click', function() {
            chartsVisible = !chartsVisible;
            document.querySelectorAll('.rdb-chart-container').forEach(function(container) {
                if (chartsVisible) {
                    container.style.display = '';
                    renderChart(container.id);
                } else {
                    container.style.display = 'none';
                    if (chartInstances[container.id]) {
                        chartInstances[container.id].destroy();
                        delete chartInstances[container.id];
                    }
                    container.innerHTML = '';
                }
            });
        });
    });
};