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

namespace report_dashboard;

/**
 * Class modinfohelper
 *
 * @package    report_dashboard
 * @copyright  2025 2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modinfohelper {
    /**
     * @var \course_modinfo
     */
    protected $modinfo;

    /**
     * modinfohelper constructor.
     *
     * @param \course_modinfo $modinfo
     */
    public function __construct(\course_modinfo $modinfo) {
        $this->modinfo = $modinfo;
    }

    /**
     * Returns true if the given cmid is valid for this course.
     *
     * @param mixed $cmid
     * @return bool
     */
    public function is_cm_valid($cmid) {
        return array_key_exists($cmid, $this->modinfo->cms);
    }

    /**
     * Returns the name of the course module with the given cmid.
     *
     * @param mixed $cmid
     * @return string
     */
    public function get_cm_name($cmid) {
        return $this->modinfo->cms[$cmid]->name;
    }
}
