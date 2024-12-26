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

use Exception;


defined('MOODLE_INTERNAL') || die();



/**
 * Class for storing original info about course to be able to restore it later. It also can read stored info for the restore process.
 */
class TransformationKeepRestoreInfo extends Transformation {

    private const STORED_DATA_MARK_BEGIN = '<br>THIS COURSE IS PLANNED FOR REMOVAL<br>';
    private const MARK_JSON_BEGIN = '<br>↓↓↓DO-NOT-CHANGE-THIS↓↓↓<br><pre>';
    private const MARK_JSON_END = '</pre>↑↑↑DO-NOT-CHANGE-THIS↑↑↑';

    public function get_name(): string {
        // return 'base Transformation';
        return get_string('saverestoredata', 'local_course_trash');
    }

    /**
     * Do something before main processing.
     */
    public function preprocess($course_transformer): bool {
        if ( ! $course_transformer->is_trashing) {
            // Extract stored data (if any).
            $summary = $course_transformer->course->summary;

            $data = self::extract_data_from_text($summary);
            if (!$data) {
                $course_transformer->log('No data can be found for restoring.');
            }
            $course_transformer->data['restored'] = $data;
        }
        
        return true;  // Success.
    }
        
    /**
     * Do something after main processing.
     */
    public function postprocess($course_transformer): bool {
        if ($course_transformer->is_trashing) {
            // Append summary with backup data.

            $url = self::format_url_for_restoring($course_transformer->course->id);
            $restore_url_tag = "<a href='$url'> Restore course / Восстановить курс </a>: $url";

            $text_with_json = self::format_json_to_text($course_transformer->data['to_keep']);
            
            // Concat main parts.
            $text_to_store = self::STORED_DATA_MARK_BEGIN . $restore_url_tag . $text_with_json;

            $summary = $course_transformer->course->summary;

            // Add to the end of course's summary/description.
            $course_transformer->changed_fields['summary'] = $summary . $text_to_store;

        } else {

            // Clean summary off stored data (if any).
            $summary = $course_transformer->course->summary;
            $updated_summary = self::remove_data_from_text($summary);
            
            if ($summary != $updated_summary) {
                $course_transformer->changed_fields['summary'] = $updated_summary;
            }
        }

        return true;  // Success.
    }

    private static function format_url_for_restoring($course_id): string {
        global $CFG;
        $course_id = (int)$course_id;
        return $CFG->wwwroot."/local/course_trash/restore.php?id=$course_id";
    }

    private static function format_json_to_text($arr): string {
        return self::MARK_JSON_BEGIN . json_encode($arr, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE) . self::MARK_JSON_END;
    }

    private static function extract_data_from_text($string): array {
        // Find the most right fragment wrapped with markers.
        $begin_pos = strrpos($string, self::MARK_JSON_BEGIN);

        $end_pos = strrpos($string, self::MARK_JSON_END, $begin_pos);

        if ($begin_pos === false || $end_pos === false) {
            return [];  // Markers not found.
        }

        $data_begin_pos = $begin_pos + strlen(self::MARK_JSON_BEGIN);
        $data_length = $end_pos - $data_begin_pos;
        $json_string = substr($string, $data_begin_pos, $data_length);
        
        return json_decode($json_string, true) ?: [];
        // Tip on json_decode():
        // No exception thrown and `null` is returned in case of invalid JSON data.
    }

    private static function remove_data_from_text($string): string {
        // Find the longest fragment wrapped with markers.
        $begin_pos = strpos($string, self::STORED_DATA_MARK_BEGIN);

        $end_pos = strrpos($string, self::MARK_JSON_END, $begin_pos);

        if ($begin_pos === false || $end_pos === false) {
            return $string;  // Markers not found.
        }

        $second_begin_pos = $end_pos + strlen(self::MARK_JSON_END);

        // Cut the middle fragment.
        return substr($string, 0, $begin_pos)
             . substr($string, $second_begin_pos);
    }

}