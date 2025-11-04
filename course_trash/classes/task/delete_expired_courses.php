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
 * Scheduled task to delete courses with expired retention period.
 *
 * @package   local_course_trash
 * @copyright 2024 Mikhail Denisov, Volgograd State Technical University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_course_trash\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/local/course_trash/classes/TransformationSaveToDatabase.php');

/**
 * Scheduled task class for deleting expired courses from trash.
 */
class delete_expired_courses extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_delete_expired', 'local_course_trash');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Check if plugin is enabled.
        if (!get_config('local_course_trash', 'enableplugin')) {
            mtrace('local_course_trash plugin is disabled. Skipping task.');
            return;
        }

        // Get retention period in seconds.
        $retention_seconds = get_config('local_course_trash', 'retention_days');
        if (empty($retention_seconds)) {
            $retention_seconds = 40 * 24 * 60 * 60; // Default value: 40 days.
        }

        // Limits from settings.
        $maxruntime = (int)get_config('local_course_trash', 'max_runtime_seconds');
        if ($maxruntime <= 0) { $maxruntime = 15 * 60; }
        $maxcount = (int)get_config('local_course_trash', 'max_delete_per_run');
        if ($maxcount < 0) { $maxcount = 0; }

        mtrace('Starting deletion of expired courses...');
        mtrace('Retention period: ' . format_time($retention_seconds));
        mtrace('Max runtime: ' . format_time($maxruntime) . '; Max per run: ' . ($maxcount ? $maxcount : 'no limit'));

        // Calculate timestamp for expiration.
        $expiration_time = time() - $retention_seconds;

        // Find all courses in trash that have expired.
        // Status 0 = in trash.
        $sql = "SELECT lct.*, c.id as course_exists
                FROM {local_course_trash} lct
                LEFT JOIN {course} c ON c.id = lct.courseid
                WHERE lct.status = :status
                AND lct.timetrashed < :expiration_time
                ORDER BY lct.timetrashed ASC";

        $params = [
            'status' => \local_course_trash\TransformationSaveToDatabase::STATUS_IN_TRASH,
            'expiration_time' => $expiration_time
        ];

        $expired_records = $DB->get_records_sql($sql, $params);

        if (empty($expired_records)) {
            mtrace('No expired courses found.');
            return;
        }

        mtrace('Found ' . count($expired_records) . ' expired course(s).');

        $deleted_count = 0;
        $error_count = 0;
        $starttime = time();

        foreach ($expired_records as $record) {
            // Stop if time limit reached.
            if ((time() - $starttime) >= $maxruntime) {
                mtrace('Time limit reached, stopping further deletions.');
                break;
            }
            // Stop if count limit reached (when > 0).
            if ($maxcount > 0 && $deleted_count >= $maxcount) {
                mtrace('Count limit reached (' . $maxcount . '), stopping further deletions.');
                break;
            }
            try {
                // Check if course still exists in the database.
                if ($record->course_exists) {
                    $course = $DB->get_record('course', ['id' => $record->courseid]);

                    if ($course) {
                        mtrace('Deleting course: ' . $course->shortname . ' (ID: ' . $course->id . ')');

                        // Delete the course permanently.
                        delete_course($course, false); // false = don't show feedback.

                        $deleted_count++;
                    }
                } else {
                    mtrace('Course ID ' . $record->courseid . ' no longer exists in database.');
                }

                // Update the record status to "deleted".
                $record->status = \local_course_trash\TransformationSaveToDatabase::STATUS_DELETED;
                $record->timedeleted = time();
                $DB->update_record('local_course_trash', $record);

            } catch (\Exception $e) {
                mtrace('Error deleting course ID ' . $record->courseid . ': ' . $e->getMessage());
                $error_count++;
            }
        }

        mtrace('Task completed. Deleted: ' . $deleted_count . ', Errors: ' . $error_count);
    }
}

