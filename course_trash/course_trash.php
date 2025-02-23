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
use local_course_trash\CourseTransformerUnenrolSelf;


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
    require_capability('local/course_trash:unsubscribe', $context);
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

if ($form->is_cancelled()) {
    // Form was canceled.
    redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);

} else if ($data = $form->get_data()) {
    // Form was confirmed.

    if (isset($data->submit_trash))  {
        // Если нажата кнопка "Удалить курс в корзину".
        $transformer = new CourseTransformer($course, true);
        $str_starting = 'deletingcourse';
        $str_done = 'deletedcourse';
    } elseif (isset($data->submit_unsubscribe)) {
        // Если нажата кнопка "Отписаться от этого курса".
        $transformer = new CourseTransformerUnenrolSelf($course, true);
        $str_starting = 'unsubscribedfromcourse';
        $str_done = 'unsubscribingfromcourse';
    }

    $strdeletingcourse = get_string($str_starting, 'local_course_trash') . $course->shortname;
    // $categoryurl = new moodle_url('/course/index.php', ['categoryid' => $course->category]);
    // $course_url = new moodle_url('/course/view.php', ['id' => $course->id]);
    $home_url = new moodle_url('/');
    $PAGE->navbar->add($strdeletingcourse);
    $PAGE->set_title("$SITE->shortname: $strdeletingcourse");
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strdeletingcourse);
    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();
    // We do this here because it splits out feedback as it goes.
    // local_course_trash_trash_course($course);

    $transformer->transform_course();

    // echo $transformer->log_text;

    echo $OUTPUT->heading(get_string($str_done, 'local_course_trash') . $course->shortname);
    // Update course count in categories.
    // fix_course_sortorder();
    echo $OUTPUT->continue_button($home_url);
    echo $OUTPUT->footer();
    exit; // We must exit here!!!

} else {
    // If it was the first time.
    echo $OUTPUT->header();

    // Choose caption for form depending on $USER's capability.
    if (has_capability('local/course_trash:manage', $context)) {
        $str_heading = 'course_trash';
    } elseif (has_capability('local/course_trash:unsubscribe', $context)) {
        $str_heading = 'unsubscribe';
    }

    // Show caption & form.
    echo $OUTPUT->heading(get_string($str_heading, 'local_course_trash'));
    $form->display();
    echo $OUTPUT->footer();
}
