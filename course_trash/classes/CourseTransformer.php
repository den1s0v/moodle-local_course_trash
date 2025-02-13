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
 * This file contains the class to manage trashing/restoring of a course.
 *
 * @package   local_course_trash
 * @copyright  2024 Mikhail Denisov, Volgograd State Technical University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_trash;


defined('MOODLE_INTERNAL') || die();



/**
 *  Class to manage trashing/restoring of a course.

 */
class CourseTransformer {
    /**
     * DB record of course being updated.
     *
     * It must not be changed while pocessing; all changes should go to `changed_fields`.
     * @var object
     */
    public $course;

    /**
     * True: trashing (moving course to trash category). False: restoring course to its original location & state.
     * @var bool
     */
    public $is_trashing;

    /**
     * A dictionary-like array: `field name -> new value`. Used to update course record in the DB.
     * @var array
     */
    public $changed_fields;

    /**
     * General-purpose data used by transformations.
     * @var array
     */
    public $data;

    /**
     * Internal cache of transformations to be applied.
     * @var array
     */
    private $_transformations;

    /**
     * Internal log accumulator.
     * @var string
     */
    public $log_text;

    public function __construct($course, $is_trashing) {
        $this->course = $course;
        $this->is_trashing = $is_trashing;

        $this->changed_fields = [];
        $this->data = ['to_keep' => [], 'restored' => [], ];
        $this->_transformations = [];
        $this->log_text = '';
    }

    /**
     * Transform & save course object to DB.
     */
    public function transform_course() {

        if (!get_config('local_course_trash', 'enableplugin')) {
            $this->log('course_trash plugin is OFF');
            return true;
        }


        foreach ($this->get_transformations() as $transform) {
            $is_ok = $transform->preprocess($this);

            if (!$is_ok) {
                $this->log('Failed preprocessing step: ' . $transform->get_name());
            }
        }

        foreach ($this->get_transformations() as $transform) {
            $is_ok = $transform->apply($this);

            if ($is_ok) {
                $this->log(($this->is_trashing ? 'Выполнено: ' : 'Отменено: ') . $transform->get_name());
            } else {
                $this->log('Failed transformation step: ' . $transform->get_name());
            }
        }

        foreach ($this->get_transformations() as $transform) {
            $is_ok = $transform->postprocess($this);

            if (!$is_ok) {
                $this->log('Failed postprocess step: ' . $transform->get_name());
            }
        }

        $this->save_course_to_db();

        return true;  // ??
    }

    private function get_transformations() {

        if (!$this->_transformations) {
            // Load from plugin config.
            $this->_transformations = self::init_transformations_from_config( ! $this->is_trashing);
            // Note: when restoring, try applying all transformations since plugin settings could be changed since the moment the course was trashed.
        }
        return $this->_transformations;
    }

    public function log($message) {
        $s = "<p>$message</p>\n";
        echo $s;
        // $this->log_text .= $s;
    }

    private function save_course_to_db() {
        global $DB;

        if ($this->changed_fields) {

            $fields_to_save = ['id' => $this->course->id] + $this->changed_fields;

            update_course((object)$fields_to_save);

            // Stupid update (should not use).
            // $DB->update_record('course', (object)$fields_to_save);
        }
    }

    private static function init_transformations_from_config($all = false) {

        $transformations = [];

        // Load from plugin config.
        if ($all || get_config('local_course_trash', 'movetocategory')) {
            $transformations []= new TransformationMoveToCategory();
        }
        if ($all || get_config('local_course_trash', 'hidecourse')) {
            $transformations []= new TransformationHide();
        }
        if ($all || get_config('local_course_trash', 'suspendmode') != LOCAL_COURSE_TRASH_SUSPEND_NO_ONE) {
            $transformations []= new TransformationSuspendByRole();
        }
        if ($all || get_config('local_course_trash', 'set_enddate')) {
            $transformations []= new TransformationSetEndDate();
        }
        if ($all || get_config('local_course_trash', 'saverestoredata')) {
            $transformations []= new TransformationKeepRestoreInfo();
        }


        return $transformations;
    }


}