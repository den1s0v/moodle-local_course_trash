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
 * Migration script for old trashed courses to local_course_trash table.
 * 
 * This script finds courses that were trashed before the database table
 * was added and migrates them to the new table structure.
 *
 * @package     local_course_trash
 * @copyright   2024 Mikhail Denisov, Volgograd State Technical University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/course_trash/locallib.php');
require_once($CFG->dirroot.'/local/course_trash/classes/TransformationKeepRestoreInfo.php');  // TODO: ckeck if really required.
require_once($CFG->dirroot.'/local/course_trash/classes/TransformationRenameCourse.php');  // TODO: ckeck if really required.
require_once($CFG->dirroot.'/course/lib.php');

use local_course_trash\TransformationKeepRestoreInfo;
use local_course_trash\TransformationRenameCourse;

// Security check.
require_login();
require_capability('moodle/site:config', context_system::instance());

// Get constants from TransformationKeepRestoreInfo using reflection.
$reflection = new ReflectionClass(TransformationKeepRestoreInfo::class);
$mark_json_begin = $reflection->getConstant('MARK_JSON_BEGIN');
$mark_json_end = $reflection->getConstant('MARK_JSON_END');
$stored_data_mark_begin = $reflection->getConstant('STORED_DATA_MARK_BEGIN');

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/course_trash/migrate_old_trashed_courses.php');
$PAGE->set_title(get_string('pluginname', 'local_course_trash') . ' - ' . 'Migration');
$PAGE->set_heading('Migration of old trashed courses');

echo $OUTPUT->header();
echo $OUTPUT->heading('Migration of old trashed courses to database table');

// Check if plugin is enabled.
if (!local_course_trash_enabled()) {
    echo $OUTPUT->notification('Plugin is disabled. Please enable it first.', 'error');
    echo $OUTPUT->footer();
    exit;
}

// Get trash category from settings.
$config = get_config('local_course_trash');
if (empty($config->coursecat) || empty($config->movetocategory)) {
    echo $OUTPUT->notification('Trash category is not configured. Please configure plugin settings first.', 'error');
    echo $OUTPUT->footer();
    exit;
}

$trash_category_id = $config->coursecat;

echo '<p>Trash category ID: ' . $trash_category_id . '</p>';
echo '<hr>';

// Find old trashed courses.
// Criteria:
// 1. Course is in trash category
// 2. Course is hidden (visible = 0)
// 3. Course summary contains the JSON marker
// 4. Course doesn't have a record in local_course_trash table
$sql = "SELECT c.id, c.shortname, c.fullname, c.idnumber, c.summary, c.visible, c.category
        FROM {course} c
        WHERE c.category = :category
        AND c.visible = 0
        AND c.summary LIKE :marker
        AND c.id NOT IN (
            SELECT courseid FROM {local_course_trash}
        )
        ORDER BY c.id";

$params = [
    'category' => $trash_category_id,
    'marker' => '%' . $mark_json_begin . '%'
];

$courses = $DB->get_records_sql($sql, $params);

if (empty($courses)) {
    echo '<p><strong>No old trashed courses found.</strong></p>';
    echo $OUTPUT->footer();
    exit;
}

echo '<p><strong>Found ' . count($courses) . ' old trashed course(s) to migrate.</strong></p>';
echo '<hr>';

// Statistics.
$processed = 0;
$skipped = 0;
$errors = 0;
$errors_list = [];

// Process each course.
foreach ($courses as $course) {
    try {
        echo '<p>Processing course: ' . htmlspecialchars($course->shortname) . ' (ID: ' . $course->id . ')... ';
        
        // Extract JSON data from summary.
        $restored_data = extract_json_data_from_summary($course->summary);
        
        if (empty($restored_data)) {
            echo '<span style="color: orange;">SKIPPED</span> - No JSON data found</p>';
            $skipped++;
            continue;
        }
        
        // Extract date from summary.
        $timetrashed = extract_date_from_summary($course->summary);
        
        // Get original category from JSON data.
        $original_category = isset($restored_data['category']) ? (int)$restored_data['category'] : $course->category;
        
        // Check if record already exists (additional check for safety).
        $existing = $DB->get_record('local_course_trash', ['courseid' => $course->id]);
        if ($existing) {
            echo '<span style="color: orange;">SKIPPED</span> - Already exists in database</p>';
            $skipped++;
            continue;
        }
        
        // Create record.
        $record = new stdClass();
        $record->courseid = $course->id;
        $record->shortname = $course->shortname;
        $record->fullname = $course->fullname;
        $record->idnumber = $course->idnumber;
        $record->category = $original_category;
        $record->userid = 0; // Unknown for old courses.
        $record->status = \local_course_trash\STATUS_IN_TRASH;
        $record->timetrashed = $timetrashed;
        $record->timedeleted = null;
        
        $DB->insert_record('local_course_trash', $record);
        
        // Optionally apply rename transformation after saving original names.
        $should_rename = (int)get_config('local_course_trash', 'renamecourse') === 1;
        if ($should_rename) {
            // Idempotent guard: skip if already renamed (has suffix at the end).
            $suffix = get_string('course_suffix', 'local_course_trash');
            $has_suffix_short = $suffix !== '' && substr($course->shortname, -strlen($suffix)) === $suffix;
            $has_suffix_full = $suffix !== '' && substr($course->fullname, -strlen($suffix)) === $suffix;

            if (!($has_suffix_short || $has_suffix_full)) {
                // Build a minimal transformer-like context.
                $ctx = (object)[
                    'course' => $course,
                    'is_trashing' => true,
                    'changed_fields' => [],
                    'data' => ['to_keep' => [], 'restored' => []],
                ];

                // Apply rename transformation to fill changed_fields.
                $rename = new TransformationRenameCourse();
                $rename->apply($ctx);

                if (!empty($ctx->changed_fields)) {
                    $fields_to_save = ['id' => $course->id] + $ctx->changed_fields;
                    update_course((object)$fields_to_save);

                    // Update local $course to reflect renamed values for subsequent checks/logs.
                    foreach ($ctx->changed_fields as $k => $v) {
                        $course->$k = $v;
                    }
                }
            }
        }

        echo '<span style="color: green;">OK</span></p>';
        $processed++;
        
    } catch (Exception $e) {
        echo '<span style="color: red;">ERROR</span> - ' . htmlspecialchars($e->getMessage()) . '</p>';
        $errors++;
        $errors_list[] = [
            'courseid' => $course->id,
            'shortname' => $course->shortname,
            'error' => $e->getMessage()
        ];
    }
}

echo '<hr>';
echo '<h3>Migration Summary</h3>';
echo '<ul>';
echo '<li><strong>Total found:</strong> ' . count($courses) . '</li>';
echo '<li><strong>Processed:</strong> <span style="color: green;">' . $processed . '</span></li>';
echo '<li><strong>Skipped:</strong> <span style="color: orange;">' . $skipped . '</span></li>';
echo '<li><strong>Errors:</strong> <span style="color: red;">' . $errors . '</span></li>';
echo '</ul>';

if (!empty($errors_list)) {
    echo '<h4>Errors Details</h4>';
    echo '<ul>';
    foreach ($errors_list as $error) {
        echo '<li>Course ID ' . $error['courseid'] . ' (' . htmlspecialchars($error['shortname']) . '): ' . htmlspecialchars($error['error']) . '</li>';
    }
    echo '</ul>';
}

echo '<p><strong>Migration completed!</strong></p>';

echo $OUTPUT->footer();

/**
 * Extract JSON data from course summary using the same logic as TransformationKeepRestoreInfo.
 * 
 * @param string $summary Course summary text
 * @return array Extracted data or empty array if not found
 */
function extract_json_data_from_summary($summary) {
    global $mark_json_begin, $mark_json_end;
    
    // Find the most right fragment wrapped with markers.
    $begin_pos = strrpos($summary, $mark_json_begin);
    
    if ($begin_pos === false) {
        return [];
    }
    
    $end_pos = strrpos($summary, $mark_json_end, $begin_pos);
    
    if ($end_pos === false) {
        return [];
    }
    
    $data_begin_pos = $begin_pos + strlen($mark_json_begin);
    $data_length = $end_pos - $data_begin_pos;
    $json_string = substr($summary, $data_begin_pos, $data_length);
    
    $data = json_decode($json_string, true);
    
    return $data ?: [];
}

/**
 * Extract date from course summary and convert to timestamp.
 * 
 * @param string $summary Course summary text
 * @return int Timestamp or current time if parsing fails
 */
function extract_date_from_summary($summary) {
    global $stored_data_mark_begin;
    
    // Find the marker.
    $marker_pos = strpos($summary, $stored_data_mark_begin);
    
    if ($marker_pos === false) {
        return time(); // Fallback to current time.
    }
    
    // Find date after the marker. Format: "Y-m-d H:i:s"
    // The date should be after the marker, possibly with HTML tags and whitespace.
    // Look for pattern matching "YYYY-MM-DD HH:MM:SS" in the text after the marker.
    $text_after_marker = substr($summary, $marker_pos + strlen($stored_data_mark_begin));
    
    // Try to find date pattern: 4 digits - 2 digits - 2 digits space 2 digits : 2 digits : 2 digits
    if (preg_match('/\b(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\b/', $text_after_marker, $matches)) {
        $date_string = $matches[1];
        
        // Try to parse date in format "Y-m-d H:i:s".
        $timestamp = strtotime($date_string);
        
        if ($timestamp === false) {
            // Try with DateTime for more precise parsing.
            $date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $date_string);
            if ($date_obj !== false) {
                $timestamp = $date_obj->getTimestamp();
            }
        }
        
        if ($timestamp !== false) {
            return $timestamp;
        }
    }
    
    // Fallback to current time if parsing failed.
    return time();
}

