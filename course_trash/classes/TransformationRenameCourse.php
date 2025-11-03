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

            // Сохранить оригинальные значения один раз (не перезаписывать при повторных вызовах).
            if (!isset($course_transformer->data['to_keep']['shortname'])) {
                $course_transformer->data['to_keep']['shortname'] = $course_transformer->course->shortname;
            }
            if (!isset($course_transformer->data['to_keep']['fullname'])) {
                $course_transformer->data['to_keep']['fullname'] = $course_transformer->course->fullname;
            }
            if (!isset($course_transformer->data['to_keep']['idnumber'])) {
                $course_transformer->data['to_keep']['idnumber'] = $course_transformer->course->idnumber;
            }
            $course_transformer->data['to_keep']['rename_suffix'] = $suffix;

            // Идемпотентная проверка: не добавлять суффикс повторно.
            $suffixlen = strlen($suffix);
            $has_suffix_short = $suffixlen > 0 && substr($course_transformer->course->shortname, -$suffixlen) === $suffix;
            $has_suffix_full = $suffixlen > 0 && substr($course_transformer->course->fullname, -$suffixlen) === $suffix;
            $has_suffix_idn = $suffixlen > 0 && !empty($course_transformer->course->idnumber)
                && substr($course_transformer->course->idnumber, -$suffixlen) === $suffix;

            if (!$has_suffix_short) {
                $course_transformer->changed_fields['shortname'] = $course_transformer->course->shortname . $suffix;
            }
            if (!$has_suffix_full) {
                $course_transformer->changed_fields['fullname'] = $course_transformer->course->fullname . $suffix;
            }
            if (!empty($course_transformer->course->idnumber) && !$has_suffix_idn) {
                $course_transformer->changed_fields['idnumber'] = $course_transformer->course->idnumber . $suffix;
            }

        } else {
            // При восстановлении: вернуть оригинальные значения и обработать коллизии имён.
            $restored_data = &$course_transformer->data['restored'];

            $suffix_delete = get_string('course_suffix', 'local_course_trash');
            $suffix_restore = get_string('course_restored_suffix', 'local_course_trash');
            $course = $course_transformer->course;

            // Базовые значения (предпочтительно из сохранённых данных), без суффикса удаления.
            $base_short = array_key_exists('shortname', $restored_data) ? $restored_data['shortname'] : $course->shortname;
            $base_full  = array_key_exists('fullname', $restored_data) ? $restored_data['fullname'] : $course->fullname;
            $base_idn   = array_key_exists('idnumber', $restored_data) ? $restored_data['idnumber'] : $course->idnumber;

            $base_short = self::strip_suffix($base_short, $suffix_delete);
            $base_full  = self::strip_suffix($base_full,  $suffix_delete);
            if (!empty($base_idn)) {
                $base_idn   = self::strip_suffix($base_idn,   $suffix_delete);
            }

            // Проверка занятости shortname и idnumber (кроме текущего курса).
            $need_unique_suffix = false;
            if (self::exists_course_with_field('shortname', $base_short, $course->id)) {
                $need_unique_suffix = true;
            }
            elseif (!empty($base_idn) && self::exists_course_with_field('idnumber', $base_idn, $course->id)) {
                $need_unique_suffix = true;
            }

            if ($need_unique_suffix) {
                // Попытаться с суффиксом восстановления, затем с нумерацией.
                [$new_short, $new_full, $new_idn] = self::generate_unique_triplet($base_short, $base_full, $base_idn, $course->id, $suffix_restore);

                $course_transformer->changed_fields['shortname'] = $new_short;
                $course_transformer->changed_fields['fullname']  = $new_full;
                if (!empty($base_idn)) {
                    $course_transformer->changed_fields['idnumber'] = $new_idn;
                }
            } else {
                // Нет конфликтов — просто вернуть базовые значения.
                $course_transformer->changed_fields['shortname'] = $base_short;
                $course_transformer->changed_fields['fullname']  = $base_full;
                if (!empty($base_idn)) {
                    $course_transformer->changed_fields['idnumber'] = $base_idn;
                }
            }
        }

        return true;  // Success.
    }
    
    /**
     * Strip suffix from value. If suffix is not found, return original value.
     * @param string $value
     * @param string $suffix
     * @return string
     */
    private static function strip_suffix($value, $suffix) {
        if ($suffix === '') {
            return $value;
        }
        $len = strlen($suffix);
        if ($len > 0 && substr($value, -$len) === $suffix) {
            return substr($value, 0, -$len);
        }
        return $value;
    }

    private static function exists_course_with_field($field, $value, $excludeid) {
        global $DB;
        if ($value === '' || $value === null) {
            return false;
        }
        $sql = "SELECT 1 FROM {course} WHERE $field = :v AND id <> :id";
        return $DB->record_exists_sql($sql, ['v' => $value, 'id' => $excludeid]);
    }

    private static function generate_unique_triplet($base_short, $base_full, $base_idn, $excludeid, $restoresuffix) {
        $candidate_short = $base_short . $restoresuffix;
        $candidate_full  = $base_full  . $restoresuffix;
        $candidate_idn   = $base_idn   ? $base_idn . $restoresuffix : '';

        $n = 2;
        while (self::exists_course_with_field('shortname', $candidate_short, $excludeid)
            || ($candidate_idn !== '' && self::exists_course_with_field('idnumber', $candidate_idn, $excludeid))) {
            $suffix_num = get_string('course_restored_suffix_num', 'local_course_trash', $n);
            $candidate_short = $base_short . $suffix_num;
            $candidate_full  = $base_full  . $suffix_num;
            $candidate_idn   = $base_idn   ? $base_idn . $suffix_num : '';
            $n++;
        }

        return [$candidate_short, $candidate_full, $candidate_idn];
    }
}

