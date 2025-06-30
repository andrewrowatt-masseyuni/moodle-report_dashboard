# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Moodle plugin (`report_dashboard`) that provides a Course Engagement Dashboard for Course Coordinators to monitor student engagement during the semester. The plugin integrates into Moodle's reporting system and displays data using DataTables for interactive filtering and visualization.

## Development Commands

### Testing
- **PHPUnit Tests**: `moodle-plugin-ci phpunit --fail-on-warning`
- **Behat Tests**: `moodle-plugin-ci behat --profile chrome --scss-deprecations`
- **Run Single Test**: Execute via Moodle's PHPUnit setup (tests located in `tests/`)

### Quality Assurance
- **PHP Lint**: `moodle-plugin-ci phplint`
- **PHP Code Sniffer**: `moodle-plugin-ci phpcs --max-warnings 0`
- **PHP Mess Detector**: `moodle-plugin-ci phpmd`
- **PHPDoc Checker**: `moodle-plugin-ci phpdoc --max-warnings 0`
- **Validate Plugin**: `moodle-plugin-ci validate`
- **Check Savepoints**: `moodle-plugin-ci savepoints`
- **Mustache Lint**: `moodle-plugin-ci mustache`
- **Grunt Build**: `moodle-plugin-ci grunt --max-lint-warnings 3`

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
- Main table ID: `#report_dashboard_dashboard`

**Templates** (`templates/`)
- Mustache templates for header filters, rows, and layout components
- Separate templates for different filter types (assessments, early engagements, groups, names)

### Data Flow

1. **SQL Configuration**: Master SQL queries stored in plugin config (`get_config('report_dashboard', 'mastersql')`)
2. **Data Retrieval**: Dashboard class methods append specific subqueries to master SQL
3. **Permission Checks**: All data access requires `report/dashboard:view` capability
4. **Frontend Display**: JavaScript initializes DataTables with server-side data

### Key Files

- `index.php`: Main report page with course context and permission checks
- `lib.php`: Navigation integration - adds dashboard link to course navigation
- `classes/dashboard.php`: Core data access methods
- `classes/modinfohelper.php`: Module information utilities
- `settings.php`: Plugin configuration interface
- `db/access.php`: Capability definitions
- `version.php`: Plugin metadata and version information

### Testing Structure

- **PHPUnit**: `tests/dashboard_test.php` (currently contains template structure)
- **Behat**: `tests/behat/dashboard.feature` with `behat_report_dashboard.php` step definitions
- **CI Pipeline**: GitHub Actions with Moodle Plugin CI testing multiple PHP/Moodle versions

### Configuration

- Plugin requires Moodle 4.01+ (`requires = 2022112800`)
- Currently in ALPHA maturity state
- Master SQL queries configurable via plugin settings
- Supports both regular groups and cohort groups

### Privacy

- **Privacy Provider**: Implements `null_provider` interface (`classes/privacy/provider.php`)
- **Data Storage**: Plugin does not store any personal data - it only displays existing Moodle data
- **GDPR Compliance**: No data export/deletion needed as no personal data is stored