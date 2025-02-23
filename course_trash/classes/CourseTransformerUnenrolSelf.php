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
 * This file contains the class to manage trashing/restoring of a course.
 *
 * @package   local_course_trash
 * @copyright  2024 Mikhail Denisov, Volgograd State Technical University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_trash;


defined('MOODLE_INTERNAL') || die();



/**
 *  Class to manage unenrolling self from a course using trashing transformations.
 */
class CourseTransformerUnenrolSelf extends CourseTransformer {

    public function __construct($course, $is_trashing) {
        parent::__construct($course, $is_trashing);
    }

    protected function init_transformations_from_config($all = false) {

        $transformations = [];

        // Use one transformation only.
        if ($all || get_config('local_course_trash', 'suspendmode') != LOCAL_COURSE_TRASH_SUSPEND_NO_ONE) {
            $transformations []= new TransformationSuspendByRole(LOCAL_COURSE_TRASH_SUSPEND_SELF_ONLY);
        }



        return $transformations;
    }


}