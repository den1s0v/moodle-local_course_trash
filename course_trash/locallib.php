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


// Constants for suspending-users mode in a trashed course.
const LOCAL_COURSE_TRASH_SUSPEND_ANYONE = 3;
const LOCAL_COURSE_TRASH_SUSPEND_SELF_AND_ROLES = 2;
const LOCAL_COURSE_TRASH_SUSPEND_SELF_ONLY = 1;
const LOCAL_COURSE_TRASH_SUSPEND_NO_ONE = 0;


function local_course_trash_enabled() {
    global $CFG;

    $enabled = get_config('local_course_trash', 'enableplugin');

    return $enabled;
}

/** Check if the course is in target course category for trashed courses.
 * Hidden status is not used since many normal courses can be hidden. */
function local_course_trash_is_course_trashed($course) {
    global $CFG;

    // Plugin setting names:
    // movetocategory
    //     coursecat
    // hidecourse.

    $config = get_config('local_course_trash');

    if ($config->movetocategory && $config->coursecat == $course->category) {
        return true;
    }

    return false;
}
