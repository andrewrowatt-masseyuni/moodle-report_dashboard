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
 * Dashboard module — loads the data table asynchronously and initialises DataTables.
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
import {getStrings} from 'core/str';
import Chartjs from 'core/chartjs';

export const init = (courseid, hiddencmids) => {
    $(function() {
        window.console.log('Report dashboard initialising'); // Debug log to confirm script is running

        // Toggle sticky footer when the footer sentinel scrolls into view.
        const sentinel = document.querySelector('.footersentinel');
        const container = document.querySelector('.dashboard_container');
        if (sentinel && container) {
            const observer = new IntersectionObserver((entries) => {
                container.classList.toggle('sticky', !entries[0].isIntersecting);
            });
            observer.observe(sentinel);
        }

        // Load the dashboard table asynchronously.
        fetch(M.cfg.wwwroot + '/report/dashboard/dashboard.php?id=' + courseid)
            .then(response => response.text())
            .then(html => {
                // Insert the table HTML into the dashboard container.
                document.querySelector('.dashboard_container').insertAdjacentHTML('beforeend', html);

                initDashboard(hiddencmids);
                return true;
            })
            .catch(error => {
                window.console.error('Failed to load dashboard:', error);
            });
    });
};

/**
 * Initialise DataTables and all filter logic after the table has been loaded.
 *
 * @param {number[]} hiddencmids Array of currently hidden cmids
 */
function initDashboard(hiddencmids) {
    var table = new DataTable('#report_dashboard_dashboard',
        {
            orderCellsTop: true,
            responsive: false,
            autoWidth: false,
            paging: true,
            pageLength: 50,
            lengthMenu: [[5, 25, 50, 100, -1], [5, 25, 50, 100, "All"]],
            layout: {
                topStart: null,
                topEnd: ['info', 'paging', 'pageLength'],
                bottomStart: {
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: 'Export to Excel',
                            className: 'customButton customButtonExportExcel btn btn-secondary btn-sm',
                        },
                        {
                            text: 'Copy email addresses of selected students',
                            className: 'customButton customButtonCopyEmailAddress btn btn-secondary btn-sm',
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
                            className: 'customButton customButtonCreateEmail btn btn-secondary btn-sm',
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
                            className: 'customButton customButtonClearSelectedRows btn btn-secondary btn-sm',
                            action: function(e, dt) {
                                dt.rows({selected: true}).deselect();
                            }
                        }
                    ]
                },
                bottomEnd: ['info', 'paging', 'pageLength']
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

                // Enable widgets that were disabled while the table was loading.
                document.getElementById('report_dashboard_fontsize').disabled = false;
                document.querySelectorAll('.dashboard_container button[disabled]').forEach(btn => {
                    btn.disabled = false;
                });
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

    // Hide column buttons — hide the column instantly and save preference via AJAX.
    document.querySelectorAll('.hide-column-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cmid = parseInt(this.dataset.cmid);
            const name = this.dataset.name;
            const type = this.dataset.type;
            const th = this.closest('th');
            // Use DataTables column(node) to get the correct index regardless of hidden columns.
            const col = table.column(th);

            col.visible(false);
            hiddencmids.push(cmid);
            setUserPreference('report_dashboard_hidden_cmids', JSON.stringify(hiddencmids));

            // Create a dynamic "Show" button in the show/hide container.
            const showLabel = type === 'assessment' ? 'Show assessment' : 'Show early engagement activity';
            const showBtn = document.createElement('button');
            showBtn.type = 'button';
            showBtn.className = 'btn btn-outline-primary btn-sm';
            showBtn.innerHTML = `<i class="fa fa-eye" aria-hidden="true"></i> ${showLabel} ${name}`;
            showBtn.addEventListener('click', function() {
                col.visible(true);
                hiddencmids = hiddencmids.filter(id => id !== cmid);
                setUserPreference('report_dashboard_hidden_cmids', JSON.stringify(hiddencmids));
                this.remove();
            });
            document.getElementById('report_dashboard_showhide').appendChild(showBtn);
        });
    });

    /**
     * Update counts for filters.
     *
     * Uses the DataTables API to access all row nodes (including those on
     * non-visible pages when paging is enabled) so that counts are accurate
     * regardless of the current page.
     *
     * @param {boolean} firstTime
     */
    function updateFilterCounts(firstTime) {
        // On first load count ALL rows; on subsequent draws count only rows matching the current search/filter.
        let rows = firstTime
            ? $(table.rows().nodes())
            : $(table.rows({search: 'applied'}).nodes());

        $('[data-filter-count] > input').each(function(i, e) {
            let filter = e.value;
            let scope = e.name.replace("_filter", "");

            let count = rows.find(`td.${scope} [data-filter-category="${filter}"]`).length;
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
    const CHART_SIZE = 150;

    // Ordered list of facet rings (inner to outer). Every assessment and early engagement chart
    // always renders all of these rings, in this order, so that ring positions are comparable
    // between columns. Other charts list their own rings in the container's data-facets.
    const FACET_ORDER = ['engagement', 'submission', 'extension'];

    // The placeholder ring shown when a facet has no data is drawn as a faint diagonal
    // hatch, so that it reads as absent data rather than as another category colour.
    // Equivalent to a 135deg repeating-linear-gradient of 1px lines every 12px over a
    // barely-there fill, the whole thing at 55% opacity.
    const NO_DATA_HATCH = {
        opacity: 0.55,
        fill: 'rgba(0, 0, 0, 0.03)',
        line: 'rgba(0, 0, 0, 0.1)',
        linewidth: 1,
        spacing: 12,
    };

    // Tile for the hatch, built once and reused by every chart.
    var noDataTile = null;

    // Language string ids used by the charts, keyed by the name used in the code.
    // The first five are the ring titles, and so are keyed by facet name.
    const CHART_STRING_IDS = {
        engagement: 'chartfacet_engagement',
        submission: 'chartfacet_submission',
        extension: 'chartfacet_extension',
        courseaccess: 'chartfacet_courseaccess',
        streamaccess: 'chartfacet_streamaccess',
        nodata: 'chartnodata',
        noextension: 'chartnoextension',
        graded: 'assessmentstatus_graded',
        streamnone: 'streamaccess_none',
    };

    // Resolved chart strings, fetched once and then used by every chart render.
    const chartStrings = {};
    const chartStringsLoaded = getStrings(
        Object.values(CHART_STRING_IDS).map(function(id) {
            return {key: id, component: 'report_dashboard'};
        })
    ).then(function(strings) {
        Object.keys(CHART_STRING_IDS).forEach(function(name, index) {
            chartStrings[name] = strings[index];
        });
        return chartStrings;
    });

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
        today: '#c5e0b4',
        yesterday: '#c5e0b4',
        '1week': '#c5e0b4',
        over1week: '#fff2cc',
        over2week: '#fff2cc',
        over3week: '#fff2cc',
        over4week: '#fbe4d5',
        never: '#fbe4d5',
        streamothercourse: '#fff2cc',
        streamonly: '#ffd966',
        streamnever: '#f4b183',
        streamnone: '#e0e0e0',
    };

    // Store Chart.js instances so we can destroy before re-rendering.
    const chartInstances = {};

    /**
     * Get the diagonal hatch used to fill a facet ring that has no data.
     *
     * @param {CanvasRenderingContext2D} ctx Context of the chart being drawn
     * @return {CanvasPattern} Repeating hatch
     */
    function getNoDataPattern(ctx) {
        if (!noDataTile) {
            // A perpendicular line spacing of n needs a tile of n * sqrt(2).
            const size = Math.round(NO_DATA_HATCH.spacing * Math.SQRT2);
            noDataTile = document.createElement('canvas');
            noDataTile.width = size;
            noDataTile.height = size;

            // Drawing the tile at the target opacity is equivalent to the CSS opacity.
            const tilectx = noDataTile.getContext('2d');
            tilectx.globalAlpha = NO_DATA_HATCH.opacity;
            tilectx.fillStyle = NO_DATA_HATCH.fill;
            tilectx.fillRect(0, 0, size, size);

            // One "/" stripe, plus the corner fragments that let the tile repeat seamlessly.
            tilectx.strokeStyle = NO_DATA_HATCH.line;
            tilectx.lineWidth = NO_DATA_HATCH.linewidth;
            tilectx.beginPath();
            tilectx.moveTo(0, size);
            tilectx.lineTo(size, 0);
            tilectx.moveTo(-1, 1);
            tilectx.lineTo(1, -1);
            tilectx.moveTo(size - 1, size + 1);
            tilectx.lineTo(size + 1, size - 1);
            tilectx.stroke();
        }

        return ctx.createPattern(noDataTile, 'repeat');
    }

    /**
     * Build a map of filter category to its translated label for a given column.
     *
     * The column's filter checkboxes already carry the translated label for each
     * category, correctly worded for the column type (assessment or early engagement),
     * so they are reused here rather than duplicating those strings.
     *
     * Labels are in filter order, which is also the order of the chart segments.
     *
     * @param {string} scope Column class, e.g. "assessment5"
     * @return {Object} Category code to translated label
     */
    function getCategoryLabels(scope) {
        const labels = {};
        // The last accessed filter uses radio buttons named after the column.
        document.querySelectorAll(`[name="${scope}_filter"], [name="${scope}"]`).forEach(function(input) {
            if (input.value && input.dataset.label) {
                labels[input.value] = input.dataset.label;
            }
        });

        // Categories with no filter checkbox to take a label from: "none" and "streamnone" are
        // synthesised for the extension and Stream access rings, and "graded" is a status
        // without its own filter.
        labels.none = chartStrings.noextension;
        labels.streamnone = chartStrings.streamnone;
        labels.graded = chartStrings.graded;
        return labels;
    }

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

        // Chart labels are language strings, so wait for them and then render.
        if (chartStrings.nodata === undefined) {
            chartStringsLoaded
                .then(() => {
                    updateVisibleCharts();
                    return true;
                })
                .catch(error => window.console.error('Failed to load chart strings:', error));
            return;
        }

        // Derive the column class from the container id, e.g. "chart_assessment5" -> "assessment5".
        const scope = containerId.replace('chart_', '');
        const facets = container.dataset.facets ? container.dataset.facets.split(' ') : FACET_ORDER;

        // Only count rows that pass ALL current filters (groups, last accessed, etc.).
        const visibleRows = table.rows({search: 'applied'}).nodes().toArray();

        // Count occurrences grouped by facet then by filter-category.
        const facetCounts = {};
        facets.forEach(function(f) {
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

        // Extensions are only marked up on the cells that have one, so every other cell in
        // the column counts as "no extension". This is only done for columns where an
        // extension can be applied at all, identified by them offering an extension filter;
        // early engagement columns have no such concept and keep an empty extension facet.
        const extensionsapply = document.querySelector(`[name="${scope}_filter"][value="extension"]`) !== null;
        if (extensionsapply) {
            const noextension = cellCount - (facetCounts.extension.extension || 0);
            if (noextension > 0) {
                facetCounts.extension.none = noextension;
            }
        }

        // Likewise, Stream access is only marked up on the cells with something to flag.
        if (facetCounts.streamaccess !== undefined) {
            const flagged = Object.values(facetCounts.streamaccess).reduce(function(a, b) {
                return a + b;
            }, 0);
            if (cellCount - flagged > 0) {
                facetCounts.streamaccess.streamnone = cellCount - flagged;
            }
        }

        // Destroy any previous chart instance for this container and start a fresh canvas.
        // The canvas is created up front because the "no data" hatch is a pattern built
        // from its drawing context.
        if (chartInstances[containerId]) {
            chartInstances[containerId].destroy();
        }

        container.innerHTML = '';
        const canvas = document.createElement('canvas');
        canvas.width = CHART_SIZE;
        canvas.height = CHART_SIZE;
        container.appendChild(canvas);
        const noDataPattern = getNoDataPattern(canvas.getContext('2d'));

        // Translated labels for this column's filter categories, e.g. "Not viewed".
        const categoryLabels = getCategoryLabels(scope);

        // Segments follow the filter order, so they keep their positions as filters change.
        const categoryOrder = Object.keys(categoryLabels);
        const segmentPosition = function(c) {
            const position = categoryOrder.indexOf(c);
            return position === -1 ? categoryOrder.length : position;
        };

        // Build one dataset per facet, always in the same order, so that every chart has
        // the same rings in the same positions. A facet with no data gets a placeholder
        // ring labelled "No data".
        const datasets = facets.map(function(facet) {
            const counts = facetCounts[facet];
            const categories = Object.keys(counts).sort(function(a, b) {
                return segmentPosition(a) - segmentPosition(b);
            });
            if (categories.length === 0) {
                return {
                    data: [1],
                    backgroundColor: [noDataPattern],
                    borderWidth: 1,
                    // Custom properties used by the tooltip callback.
                    _labels: [chartStrings.nodata],
                    _facet: chartStrings[facet],
                    _total: 0,
                    _nodata: true,
                };
            }
            var values = categories.map(function(c) {
                return counts[c];
            });
            var total = values.reduce(function(a, b) {
                return a + b;
            }, 0);
            return {
                data: values,
                backgroundColor: categories.map(function(c) {
                    return CATEGORY_COLORS[c] || '#cccccc';
                }),
                borderWidth: 1,
                // Custom properties used by the tooltip callback.
                _labels: categories.map(function(c) {
                    return categoryLabels[c] || c;
                }),
                _facet: chartStrings[facet],
                _total: total,
                _nodata: false,
            };
        });

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
                                if (context.dataset._nodata) {
                                    return chartStrings.nodata;
                                }
                                var label = context.dataset._labels
                                    ? context.dataset._labels[context.dataIndex]
                                    : '';
                                var total = context.dataset._total || 0;
                                var pct = total > 0
                                    ? Math.round(context.raw / total * 100)
                                    : 0;
                                return label + ' ' + pct + '% (' + context.raw + ')';
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
}