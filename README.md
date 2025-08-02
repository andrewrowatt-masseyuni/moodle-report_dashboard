[![Moodle Plugin CI](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/andrewrowatt-masseyuni/moodle-report_dashboard/actions/workflows/moodle-ci.yml)

# Course engagement dashboard

Provides a report ("dashboard") to support Course Coordinators to monitor student engagement during the semester.

Note this is a "hybrid" plugin in that it heavily utilises SQL to directly query the status of Moodle activities. The reason for this is the precursor to the plugin was a report using the fantastic [Ad-hoc database queries](https://moodle.org/plugins/report_customsql) plugin. Over time, more of the plugin will transition to use of native Moodle API calls. Actual timeframe is TBA.

## Installing via uploaded ZIP file

1.  Log in to your Moodle site as an admin and go to *Site administration \> Plugins \> Install plugins*.

2.  Upload the ZIP file with the plugin code. You should only be prompted to add extra details if your plugin type is not automatically detected.

3.  Check the plugin validation report and finish the installation.

## Installing manually

The plugin can be also installed by putting the contents of this directory to

```
{your/moodle/dirroot}/report/dashboard
```

Afterwards, log in to your Moodle site as an admin and go to *Site administration \> Notifications* to complete the installation.

Alternatively, you can run

```
$ php admin/cli/upgrade.php
```

to complete the installation from the command line.

## License

2024 Andrew Rowatt [A.J.Rowatt@massey.ac.nz](mailto:A.J.Rowatt@massey.ac.nz)

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
