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

/**
 * Version details
 *
 * @package   local_course_trash
 * @copyright 2021, Marcelo A. Rauh Schmitt <marcelo.rauh@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_course_trash';  // Recommended since 2.0.2 (MDL-26035). Required since 3.0 (MDL-48494).
$plugin->version = 2025021100;  // YYYYMMDDHH (year, month, day, 24-hr time).
$plugin->requires = 2019111800;
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '2.2';
