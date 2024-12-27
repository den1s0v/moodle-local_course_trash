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
 * Send course to trash with confirmation.
 *
 * @package     local_course_trash
 * @copyright   2021 Marcelo A. Rauh Schmitt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
// require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once('classes/confirm_form.php');

require_once 'locallib.php';
use local_course_trash\CourseTransformer;


$id = required_param('id', PARAM_INT);

// Perform some basic access control checks.
if ($id) {
    if ($id == SITEID) {
        // Don't allow editing of 'site course' using this form.
        throw new moodle_exception('cannoteditsiteform');
    }
    // if (!$course = $DB->get_record('course', ['id' => $id])) {
    if (!$course = get_course($id)) {
        throw new moodle_exception('invalidcourseid');
    }
    require_login($course);

    if ( ! local_course_trash_enabled()) {
        print_error('function is not avaiable', 'local_course_trash', $CFG->wwwroot.'/course/view.php?id='.$course->id);
    }

    $context = context_course::instance($course->id);
    require_capability('local/course_trash:manage', $context);
} else {
    require_login();
    throw new moodle_exception('needcourseid');
}

// Setup PAGE.
$PAGE->set_course($course);
$PAGE->set_url('/local/course_trash/course_trash.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('admin');

$form = new local_course_trash_confirm_form('course_trash.php?id='.$id, ['course' => $course]);

// Se foi cancelado.
if ($form->is_cancelled()) {
    redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
} else if ($data = $form->get_data()) { // Se foi confirmado.
    $strrestoringcourse = get_string("deletingcourse", "local_course_trash") . " " .
        $course->shortname;
    $categoryurl = new moodle_url('/course/index.php', ['categoryid' => $course->category]);
    $PAGE->navbar->add($strrestoringcourse);
    $PAGE->set_title("$SITE->shortname: $strrestoringcourse");
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strrestoringcourse);
    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();
    // We do this here because it spits out feedback as it goes.
    // local_course_trash_trash_course($course);

    $transformer = new CourseTransformer($course, true);
    $transformer->transform_course();

    // echo $transformer->log_text;
    
    echo $OUTPUT->heading(get_string("deletedcourse", "local_course_trash") . $course->shortname);
    // Update course count in categories.
    // fix_course_sortorder();
    echo $OUTPUT->continue_button($categoryurl);
    echo $OUTPUT->footer();
    exit; // We must exit here!!!
}
// Se foi a primeira vez.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_trash', 'local_course_trash'));
$form->display();
echo $OUTPUT->footer();
