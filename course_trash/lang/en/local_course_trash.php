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
 * Classes to enforce the various access rules that can apply to a activity.
 *
 * @package    local_course_trash
 * @copyright  2021 Marcelo Augusto Rauh Schmitt <marcelo.rauh@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

$string['alert'] = 'Attention! If you continue this operation, the course will be sent to the trash, from where it will be permanently deleted (usually it will take at least 7 days before this happens). <p> You will lose access to the course and its content, including all materials, assignments, answers, and student grades. <p>If you later wish to restore the course, please contact your department administrator.';
// $string['alert_old'] = 'Attention! If you continue this operation the course you are in will be deleted. Think about downloading a backup.';
$string['course_trash'] = 'Trash course';
$string['course_restore'] = 'Restore trashed course';
$string['course_trash:manage'] = 'Trash own courses';
$string['deletedcourse'] = 'Moved course to trash: ';
$string['deletingcourse'] = 'Trashing course';
$string['pluginname'] = 'Send course to trash';
$string['restoringcourse'] = 'Restoring course from the trash';
$string['restoredcourse'] = 'Course has been restored from the trash: ';



// settings-specific strings
$string['settings']            = 'Course Trash/Recycle Bin';
$string['pluginname_desc']     = 'Plugin for safely deleting courses to the “trash” (a special category of courses “To delete”) for the possibility of restoring accidentally deleted courses. Actual deletion of courses will be performed by clearing the specified category of courses.';
$string['enableplugin']        = 'Enable plugin';
$string['enableplugin_help']   = 'If disabled, the plugin features will be unavailable';
$string['coursecat']           = 'Course category';
$string['coursecat_help']      = 'The course category "To be deleted". Usually, a new category needs to be created for this purpose during the initial setup.';

$string['heading_courseoperations']   = 'Course operations';
$string['heading_courseoperations_info'] = 'What changes will be made to the course when sent to the trash';
$string['movetocategory']        = 'Move to "To be deleted" category';
$string['movetocategory_help']   = 'If disabled, the course will remain in its original location';
$string['hidecourse']           = 'Hide course from students';
$string['hidecourse_help']      = 'If disabled AND if the next setting does not mandate blocking students, the course will remain accessible to students';

$string['suspend_anyone']       = 'everyone';
$string['suspend_self_and_roles']= 'only self and listed roles';
$string['suspend_self_only']    = 'only self';
$string['suspend_no_one']       = 'no one';
$string['suspendmode']          = 'Suspend…';
$string['suspendmode_help']     = 'Suspend course participants according to their roles';

$string['suspendroles']         = 'Suspend roles';
$string['suspendroles_help']    = 'Suspend course participants with these roles';
$string['set_enddate']          = 'Set course end date';
$string['set_enddate_help']     = 'Set the course end date to the current date (this will help later to know when this action was performed)';
$string['saverestoredata']      = 'Save data for course restoration';
$string['saverestoredata_help'] = 'If disabled, data about the original location and state of the course will be lost, and automatic course restoration will be impossible. The data is saved in the course description text (summary).';

