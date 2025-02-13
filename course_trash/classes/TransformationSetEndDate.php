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

use const block_lk\DAY_SECONDS;


defined('MOODLE_INTERNAL') || die();



/**
 * Class for updating enddate of a course.
 */
class TransformationSetEndDate extends Transformation {

    public function get_name(): string {
        // return 'base Transformation';
        return get_string('set_enddate', 'local_course_trash');
    }

    /**
     * Apply transformation (do main processing).
     */
    public function apply($course_transformer): bool {
        global $DB;

        // Получить данные в соответствии с направлением обработки (удаление/восстановление).
        if ($course_transformer->is_trashing) {
            $enddate = time();
            $startdate = null;

            if ( ! $course_transformer->course->startdate) {
                // Дата окончания курса может быть задана только при установленной дате начала курса.
                $startdate = $enddate - 365 * 86400 /*DAY_SECONDS == 60*60*24*/;
            }
        } else {
            $restored_data = &$course_transformer->data['restored'];

            $key = 'enddate';
            $enddate = array_key_exists($key, $restored_data) ? !!$restored_data[$key] : null;

            $key = 'startdate';
            $startdate = array_key_exists($key, $restored_data) ? !!$restored_data[$key] : null;
        }

        // Выполнить преобразование и зафиксировать информацию о сделанных изменениях.
        if ($startdate !== null && $startdate != $course_transformer->course->startdate) {

            $course_transformer->changed_fields['startdate'] = $startdate;
            $course_transformer->data['to_keep']['startdate'] = $course_transformer->course->startdate;
        }

        if ($enddate !== null && $enddate != $course_transformer->course->enddate) {

            $course_transformer->changed_fields['enddate'] = $enddate;
            $course_transformer->data['to_keep']['enddate'] = $course_transformer->course->enddate;
        }

        return true;  // Success.
    }
}
