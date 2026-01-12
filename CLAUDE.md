# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Moodle plugin (`report_dashboard`) that provides a Course Engagement Dashboard for Course Coordinators to monitor student engagement during the semester. The plugin integrates into Moodle's reporting system and displays data using DataTables for interactive filtering and visualization.

**Current Version**: 1.0 (version 2025011900)
**Supported Moodle**: 4.05 - 5.01 (`requires = 2024100700`)
**Maturity**: STABLE

## Development Commands

### Testing
- **PHPUnit Tests**: `moodle-plugin-ci phpunit --fail-on-warning`
- **Behat Tests**: `moodle-plugin-ci behat --profile chrome --scss-deprecations`
- **Run Single Test**: Execute via Moodle's PHPUnit setup (tests located in `tests/`)

### Quality Assurance
- **PHP Lint**: `moodle-plugin-ci phplint`
- **PHP Code Sniffer**: `moodle-plugin-ci phpcs --max-warnings 0`
- **PHP Mess Detector**: `moodle-plugin-ci phpmd` (non-blocking in CI)
- **PHPDoc Checker**: `moodle-plugin-ci phpdoc --max-warnings 0`
- **Validate Plugin**: `moodle-plugin-ci validate`
- **Check Savepoints**: `moodle-plugin-ci savepoints`
- **Mustache Lint**: `moodle-plugin-ci mustache`
- **Grunt Build**: `moodle-plugin-ci grunt --max-lint-warnings 8`

### Installation
- **Manual Install**: Place contents in `{moodle}/report/dashboard/`
- **CLI Upgrade**: `php admin/cli/upgrade.php`

## Architecture

### Core Components

**Main Dashboard Class** (`classes/dashboard.php`)
- Central data access layer using configurable master SQL queries
- Methods for retrieving course groups, assessments, early engagement activities, and user datasets
- All database queries use parameterized SQL with a master query template from plugin configuration

**Frontend** (`amd/src/dashboard.js`)
- jQuery-based DataTables implementation for interactive data display
- Imports custom DataTables modules with Bootstrap 4 styling and select functionality
- Includes export buttons (HTML5, print) via jszip integration
- Main table ID: `#report_dashboard_dashboard`

**DataTables Modules** (`amd/src/`)
- `dataTables.js` - Core DataTables library
- `dataTables.bootstrap4.js` - Bootstrap 4 styling integration
- `dataTables.select.js` - Row selection functionality
- `dataTables.buttons.js` - Button extension
- `buttons.bootstrap4.js` - Bootstrap 4 button styling
- `buttons.html5.js` - HTML5 export (CSV, Excel, PDF)
- `buttons.print.js` - Print functionality
- `jszip.js` - ZIP compression for Excel export

**Templates** (`templates/`)
- `dashboard.mustache` - Main dashboard layout
- `_header_filter_*.mustache` - Filter dropdowns (assessments, early engagements, groups, names)
- `_header_headings.mustache` - Table column headers
- `_header_end.mustache` - Header closing elements
- `_row.mustache` - Table row template
- `_footer.mustache` - Table footer

Note: Partial templates prefixed with `_` are excluded from Mustache linting via `MUSTACHE_IGNORE_NAMES: '_*.mustache'` in CI.

### Data Flow

1. **SQL Configuration**: Master SQL queries stored in plugin config (`get_config('report_dashboard', 'mastersql')`)
2. **Data Retrieval**: Dashboard class methods append specific subqueries to master SQL
3. **Permission Checks**: All data access requires `report/dashboard:view` capability
4. **Frontend Display**: JavaScript initializes DataTables with server-side data

### Key Files

- `index.php` - Main report page with course context and permission checks
- `lib.php` - Navigation integration (adds dashboard link to course navigation)
- `classes/dashboard.php` - Core data access methods
- `classes/modinfohelper.php` - Module information utilities
- `settings.php` - Plugin configuration interface
- `db/access.php` - Capability definitions
- `lang/en/report_dashboard.php` - Language strings
- `version.php` - Plugin metadata and version information

### Testing Structure

- **PHPUnit**: `tests/dashboard_test.php`
- **Behat**: `tests/behat/dashboard.feature` with `behat_report_dashboard.php` step definitions
- **CI Pipeline**: GitHub Actions (`.github/workflows/moodle-ci.yml`)
  - Tests on PHP 8.1 with Moodle 4.05 stable branch
  - PostgreSQL 16 database
  - Uploads Behat failure dumps as artifacts on test failures

### Configuration

- Master SQL queries configurable via plugin settings
- Supports both regular groups and cohort groups

### Privacy

- **Privacy Provider**: Implements `null_provider` interface (`classes/privacy/provider.php`)
- **Data Storage**: Plugin does not store any personal data - it only displays existing Moodle data
- **GDPR Compliance**: No data export/deletion needed as no personal data is stored