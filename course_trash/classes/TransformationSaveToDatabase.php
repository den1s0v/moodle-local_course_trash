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
 * This file contains the class for saving/updating course trash information in the database.
 *
 * @package   local_course_trash
 * @copyright  2024 Mikhail Denisov, Volgograd State Technical University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_trash;


defined('MOODLE_INTERNAL') || die();


// Status constants.
const STATUS_IN_TRASH = 0;
const STATUS_DELETED = 1;
const STATUS_RESTORED = 2;


/**
 * Class for saving course information to database when trashing/restoring.
 */
class TransformationSaveToDatabase extends Transformation {

    public function get_name(): string {
        return 'Save to database';
    }

    /**
     * Do something after main processing.
     * We use postprocess to ensure all other transformations have completed.
     */
    public function postprocess($course_transformer): bool {
        global $DB, $USER;

        if ($course_transformer->is_trashing) {
            // При удалении: записать данные о курсе в таблицу.

            // Check if record already exists for this course.
            $existing = $DB->get_record('local_course_trash',
                ['courseid' => $course_transformer->course->id, 'status' => STATUS_IN_TRASH]);

            if ($existing) {
                // Update existing record.
                $existing->shortname = $course_transformer->course->shortname;
                $existing->fullname = $course_transformer->course->fullname;
                $existing->idnumber = $course_transformer->course->idnumber;
                $existing->category = $course_transformer->course->category;
                $existing->userid = $USER->id;
                $existing->timetrashed = time();

                $DB->update_record('local_course_trash', $existing);
            } else {
                // Create new record.
                $record = new \stdClass();
                $record->courseid = $course_transformer->course->id;
                $record->shortname = $course_transformer->course->shortname;
                $record->fullname = $course_transformer->course->fullname;
                $record->idnumber = $course_transformer->course->idnumber;
                $record->category = $course_transformer->course->category;
                $record->userid = $USER->id;
                $record->status = STATUS_IN_TRASH;
                $record->timetrashed = time();
                $record->timedeleted = null;

                $DB->insert_record('local_course_trash', $record);
            }

        } else {
            // При восстановлении: обновить статус на "восстановлен".

            // Find the most recent trash record for this course.
            $records = $DB->get_records('local_course_trash',
                ['courseid' => $course_transformer->course->id, 'status' => STATUS_IN_TRASH],
                'timetrashed DESC', '*', 0, 1);

            if (!empty($records)) {
                $record = reset($records);
                $record->status = STATUS_RESTORED;
                $DB->update_record('local_course_trash', $record);
            }
        }

        return true;  // Success.
    }
}

