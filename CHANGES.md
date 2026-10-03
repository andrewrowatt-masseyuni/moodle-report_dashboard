# Changes

## 2026100400 (2026-10-04)

### Added

- New activity settings in a *Course dashboard preferences* section ([#54](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/issues/54)):
  - **Show on course dashboard report** (*Auto*, *Always* or *Never*). Applies to assignments and quizzes only.
    - *Auto* shows the activity if it has a close or due date (as before).
    - *Always* shows it even when it has no close or due date.
    - *Never* hides it even when it has a close or due date.
  - **Title override** replaces the activity name in the dashboard column heading, for example to shorten it.
- Assessments with no close or due date:
  - show a calendar icon in the column heading,
  - are ordered after the dated activity that comes before them in the course,
  - report a status of *Not due* instead of *Not submitted*.
- Early engagement column headings now show a seedling icon with a tooltip instead of the "(Early engagement)" caption.

### Changed

- Early engagement completion status is now a compact tick icon with a tooltip instead of a text label.
- Help for the activity settings is shown as inline text under each field instead of a help button.

## 2026100302 (2026-10-03)

### Added

- The *Last accessed* column shows wider activity in Stream ([#52](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/issues/52)). If a student has used Stream more recently than this course (by at least 2 days), a tag appears next to their last-accessed date:
  - *Active in another course*: accessed another course more recently.
  - *Active on Stream only*: accessed Stream, but no course, more recently.
  - *Never accessed Stream*.

  The tag's tooltip says how many days ago the student was active elsewhere.
- New *Stream access* options in the *Last accessed* filter, with counts.
- A mini chart for the *Last accessed* column, with rings for course access and Stream access.

### Changed

- Chart segments follow the filter order, so they stay in the same position as filters change.
- Tooltips can span multiple lines.
- *Over 3 weeks ago* in the *Last accessed* column now uses the same colour as *Over 1 week ago* and *Over 2 weeks ago*.

### Fixed

- Tooltips on the viewed-status icons for assessments and early engagements no longer flicker.
- Fixed DataTables icons not showing when the web server does not send a character set.

## 2026100300 (2026-10-03)

### Added

- Teachers can flag any activity as an early engagement activity in its settings, using *Show in course dashboard as an early engagement activity* ([#51](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/issues/51)).
  - The setting needs course completion tracking enabled and completion conditions set on the activity. Otherwise it is disabled.
  - The older method, an activity ID number matching `EE` followed by a digit, still works.
- The new `report_dashboard_cm` table stores per-activity preferences.
  - Preferences are included in course and activity backup and restore.
  - Preferences are deleted when their activity or course is deleted.

### Changed

- Early engagement activities that share an expected completion date are now sorted in a consistent order.

## 2025011900 (2026-03-15 to 2026-08-15)

### Added

- Mini charts for each assessment and early engagement column ([#36](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/issues/36)).
  - A new chart button in the toolbar shows or hides all charts.
  - Each chart is a doughnut with up to three rings: engagement (viewed or not viewed), submission or completion status, and extension. Hovering over a segment shows its label, percentage and number of students.
  - Charts count only the students that match the current filters, and update when the filters change.
- A *Course dashboard report viewed* event is logged each time the dashboard is opened.

### Changed

- Activities hidden from students on the course page no longer appear on the dashboard ([#44](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/issues/44)).
- Tooltips on tags, icons and the email link now use Bootstrap tooltips instead of plain browser tooltips ([#49](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/issues/49)).
- The table footer stays at the bottom of the screen while scrolling, and only shows a shadow when it is floating over the table.
- Long column headings are cut off with an ellipsis.
- The groups filter is limited to 70% of the screen height and scrolls when there are many groups.
- The *No groups* tag uses a neutral colour.
