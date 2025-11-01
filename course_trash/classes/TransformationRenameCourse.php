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
 * This file contains the class for a specific transformation applied to a course while trashing/restoring it.
 *
 * @package   local_course_trash
 * @copyright  2024 Mikhail Denisov, Volgograd State Technical University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_trash;


defined('MOODLE_INTERNAL') || die();



/**
 * Class for renaming course (adding/removing suffix to course names).
 */
class TransformationRenameCourse extends Transformation {

    public function get_name(): string {
        return get_string('renamecourse', 'local_course_trash');
    }

    /**
     * Apply transformation (do main processing).
     */
    public function apply($course_transformer): bool {
        global $DB;

        // Получить данные в соответствии с направлением обработки (удаление/восстановление).
        if ($course_transformer->is_trashing) {
            // При удалении: получить суффикс из языкового файла и сохранить его.
            $suffix = get_string('course_suffix', 'local_course_trash');

            // Сохранить оригинальные значения.
            $course_transformer->data['to_keep']['shortname'] = $course_transformer->course->shortname;
            $course_transformer->data['to_keep']['fullname'] = $course_transformer->course->fullname;
            $course_transformer->data['to_keep']['idnumber'] = $course_transformer->course->idnumber;
            $course_transformer->data['to_keep']['rename_suffix'] = $suffix;

            // Применить суффикс к именам курса.
            $course_transformer->changed_fields['shortname'] = $course_transformer->course->shortname . $suffix;
            $course_transformer->changed_fields['fullname'] = $course_transformer->course->fullname . $suffix;
            if (!empty($course_transformer->course->idnumber)) {
                $course_transformer->changed_fields['idnumber'] = $course_transformer->course->idnumber . $suffix;
            }

        } else {
            // При восстановлении: вернуть оригинальные значения.
            $restored_data = &$course_transformer->data['restored'];

            // Восстановить оригинальные значения, если они были сохранены.
            if (array_key_exists('shortname', $restored_data)) {
                $course_transformer->changed_fields['shortname'] = $restored_data['shortname'];
            }
            if (array_key_exists('fullname', $restored_data)) {
                $course_transformer->changed_fields['fullname'] = $restored_data['fullname'];
            }
            if (array_key_exists('idnumber', $restored_data)) {
                $course_transformer->changed_fields['idnumber'] = $restored_data['idnumber'];
            }
        }

        return true;  // Success.
    }
}

