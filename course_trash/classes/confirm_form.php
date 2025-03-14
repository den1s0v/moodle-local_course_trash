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
 * Class to confirm couse deletion.
 *
 * @package     local_course_trash
 * @copyright   2024 Marcelo Augusto Rauh Schmitt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_course_trash_confirm_form extends moodleform {

    /**
     * This method just overrides the default in order to create the
     * alert message before deleting the course.
     *
     * @see clean_param()
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $course = $this->_customdata['course'];

        $context = context_course::instance($course->id);

        if (has_capability('local/course_trash:manage', $context)) {

            $mform->addElement('static', 'alert', get_string('alert', 'local_course_trash'));
        
            // Добавляем кнопку "Удалить курс в корзину"
            $mform->addGroup([
                $mform->createElement('submit', 'submit_trash', get_string('course_trash', 'local_course_trash')),
                $mform->createElement('cancel')
            ], 'buttonar2', '', ' ', false);        

        }
        
        if (has_capability('local/course_trash:unsubscribe', $context)) {
            
            $mform->addElement('static', 'alert', get_string('unsubscribe_alert', 'local_course_trash'));

            // Добавляем кнопку "Отписаться от этого курса"
            $mform->addGroup([
                $mform->createElement('submit', 'submit_unsubscribe', get_string('unsubscribe', 'local_course_trash')),
                $mform->createElement('cancel')
            ], 'buttonar2', '', ' ', false);
        }
    }
}
