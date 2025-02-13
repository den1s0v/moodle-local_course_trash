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
 * Class for moving course to category.
 */
class TransformationMoveToCategory extends Transformation {

    public function get_name(): string {
        // return 'base Transformation';
        return get_string('movetocategory', 'local_course_trash');
    }

    /**
     * Apply transformation (do main processing).
     */
    public function apply($course_transformer): bool {
        global $DB;

        // Получить данные в соответствии с направлением обработки (удаление/восстановление).
        if ($course_transformer->is_trashing) {
            $target_coursecat = get_config('local_course_trash', 'coursecat');
        } else {
            $restored_data = &$course_transformer->data['restored'];

            $key = 'category';
            $target_coursecat = array_key_exists($key, $restored_data) ? $restored_data[$key] : null;
        }

        // Выполнить преобразование и зафиксировать информацию о сделанных изменениях.
        if ($target_coursecat) {

            if ($DB->record_exists('course_categories', ['id' => $target_coursecat])) {

                $course_transformer->changed_fields['category'] = $target_coursecat;
                $course_transformer->data['to_keep']['category'] = $course_transformer->course->category;

                return true;  // Success.

            } else {
                    $course_transformer->log('target_coursecat does not exist: ' . var_export($target_coursecat, true));

                    return false;  // Fail.
            }
        }

        return true;  // Success.
    }
}