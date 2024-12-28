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
 * Class for suspending course paricipants by role.
 */
class TransformationSuspendByRole extends Transformation {

    public function get_name(): string {
        // return 'base Transformation';
        return get_string('suspendmode_help', 'local_course_trash');
    }

    /**
     * Apply transformation (do main processing).
     */
    public function apply($course_transformer): bool {
        global $DB;
        
        // Получить данные в соответствии с направлением обработки (удаление/восстановление).
        $user_enrols_to_update = null;

        $update_user_enrols = true;
        $update_enrol_methods = true;

        if ($course_transformer->is_trashing) {
            $target_enrol_status = ENROL_USER_SUSPENDED;

            [$user_enrols_to_update, $update_user_enrols, $update_enrol_methods] = self::find_user_enrols_to_update($course_transformer->course->id);
            
        } else {
            $target_enrol_status = ENROL_USER_ACTIVE;

            $key = 'user_enrols_suspended';
            $restored_data = &$course_transformer->data['restored'];
            $user_enrol_ids = array_key_exists($key, $restored_data) ? $restored_data[$key] : null;

            if ($user_enrol_ids) {
                // Restore only 'ue's suspended while trashing.
                $user_enrols_to_update = self::enrols_on_course($course_transformer->course->id, [], false, $user_enrol_ids, ENROL_USER_SUSPENDED);
            } else {
                // Restore what is on plugin settings.
                [$user_enrols_to_update, $update_user_enrols, $update_enrol_methods] = self::find_user_enrols_to_update($course_transformer->course->id, ENROL_USER_SUSPENDED);
            }
        }
        
        // Выполнить преобразование и зафиксировать информацию о сделанных изменениях.
        if ($user_enrols_to_update) {

            $n = count($user_enrols_to_update);
            $course_transformer->log("Updating $n enrolments.");

            if ($update_user_enrols) {
                self::update_user_enrols_status($user_enrols_to_update, $target_enrol_status);
            }

            if ($update_enrol_methods) {
                self::update_enrols_status($user_enrols_to_update, $target_enrol_status);
            }

            // Extract ids of user_enrolments rows.
            $user_enrol_ids = array_keys($user_enrols_to_update);

            $course_transformer->data['to_keep']  ['user_enrols_suspended'] = $user_enrol_ids;

        } elseif ($user_enrols_to_update != []) {
            $course_id = $course_transformer->course->id;
            $course_transformer->log("`user_enrols_to_update` for course with id=$course_id is empty, probably due to bad sql query.");
            
            return false;  // Fail.
        }
        
        return true;  // Success.
    }


    /**
     * Find user-enrolments for a course (having active status by default), filtered by optional roles and optionally including current $USER's enrolments in the result.
     * @param int $course_id mandatory course id.
     * @param array $role_ids array of roles to include in result; [] means find nothing by roles; `null` (the default) means to take all roles (i.e. all active course prticipants).
     * @param boolean $include_current_user including current $USER's enrolments in the result (`true` by default).
     * @param array $explicit_ue_ids if not null, add rows user_enrolments (ue) with given ids (`null` by default).
     * @param int $ue_status including current $USER's enrolments in the result (`true` by default).
     * @return array array where each row includes: all `enrol` table fields with `userid` and `ue_id`, indexed by `ue_id`.
     */
    private static function enrols_on_course($course_id, $role_ids = null, $include_current_user = true, $explicit_ue_ids = null, $ue_status = ENROL_USER_ACTIVE) {
        global $DB;
        global $USER;

        // Insert a condition if roles given.
        $role_inparams = [];
        if ($role_ids) {
            [$insql, $role_inparams] = $DB->get_in_or_equal($role_ids, SQL_PARAMS_NAMED, 'role');
            $role_filter = "(ra.roleid $insql)";
        } elseif ($role_ids === null) {
            $role_filter = '1';
        } else/* if ($role_ids == []) */ {
            $role_filter = '0';
        }
        
        // Insert a condition if current $USER should be included.
        if ($include_current_user) {
            $user_filter = 'ue.userid = :user_id';
        } else {
            $user_filter = '0';
        }
        
        // Insert a condition if user_enrolments with given ids should be found.
        $ue_inparams = [];
        if ($explicit_ue_ids) {
            [$insql, $ue_inparams] = $DB->get_in_or_equal($explicit_ue_ids, SQL_PARAMS_NAMED, 'ue');
            $ue_ids_filter = "(ue.id $insql)";
        } else {
            $ue_ids_filter = '0';
        }

        // Insert a condition if ue_status is given.
        if ($ue_status == ENROL_USER_ACTIVE) {
            $status_filter = 'ue.status = :ue_status AND e.status = :ue_status2';
        } else {
            $status_filter = 'ue.status = :ue_status OR e.status = :ue_status2';
        }

        
        $sql = "SELECT DISTINCT ue.id as ue_id, e.*, ue.userid
        FROM {user_enrolments} ue
        JOIN {enrol} e ON ue.enrolid = e.id
        JOIN {context} cx ON (cx.instanceid = e.courseid
        AND cx.contextlevel = 50)  -- COURSE_CONTEXT = 50
        LEFT JOIN
        {role_assignments} ra ON (ue.userid = ra.userid
            -- AND ue.enrolid = ra.itemid
            AND cx.id = ra.contextid)
        WHERE e.courseid = :course_id
        AND ($role_filter OR $user_filter OR $ue_ids_filter)
        AND ($status_filter)
        ";
        $sql_params = $role_inparams + $ue_inparams + [
            'course_id' => $course_id, 
            // 'role_ids' => $role_ids, 
            'ue_status' => $ue_status, 
            'ue_status2' => $ue_status, 
            'user_id' => $USER->id, 
            // 'explicit_ue_ids' => $explicit_ue_ids, 
        ];

        // var_dump($sql);
        // echo '<br>';
        // var_dump($sql_params);

        // Query DB directly.
        $user_enrols = $DB->get_records_sql($sql, $sql_params);

        return $user_enrols;
    }

    
    private static function find_user_enrols_to_update($course_id, $enrol_status = ENROL_USER_ACTIVE) {
        global $CFG;

        $user_enrols_to_update = null;

        $update_user_enrols = true;
        $update_enrol_methods = true;

        $suspendmode = get_config('local_course_trash', 'suspendmode');

        switch ($suspendmode) {
            case LOCAL_COURSE_TRASH_SUSPEND_ANYONE:
                $user_enrols_to_update = self::enrols_on_course($course_id, null, false /* No matter */, null, $enrol_status);
                $update_user_enrols = false;
                break;
                
            case LOCAL_COURSE_TRASH_SUSPEND_SELF_AND_ROLES:
                $suspendroles_str = get_config('local_course_trash', 'suspendroles');  // E.g. string(3) "3,4".

                $suspendroles = explode(',', $suspendroles_str);

                $user_enrols_to_update = self::enrols_on_course($course_id, $suspendroles, true, null, $enrol_status);
                break;
            
            case LOCAL_COURSE_TRASH_SUSPEND_SELF_ONLY:
                $user_enrols_to_update = self::enrols_on_course($course_id, [], true, null, $enrol_status);
                $update_enrol_methods = false;
                break;
            
            case LOCAL_COURSE_TRASH_SUSPEND_NO_ONE:
                $user_enrols_to_update = [];
                break;
            
            default:
                throw new \RuntimeException("Invalid local_course_trash / suspendmode value: $suspendmode", 1);
        }

        return [$user_enrols_to_update, $update_user_enrols, $update_enrol_methods];
    }

    
    private static function update_user_enrols_status($user_enrols_to_update, $target_enrol_status) {
        global $DB;

        $enrol_plugins_cache = [];

        foreach ($user_enrols_to_update as $enrol_plus_userid) {
            $enrol_plugin_name = $enrol_plus_userid->enrol;
            if (!array_key_exists($enrol_plugin_name, $enrol_plugins_cache)) {
                // Retrieve & cache enrol plugin.
                $enrol_plugin = enrol_get_plugin($enrol_plugin_name);
                $enrol_plugins_cache[$enrol_plugin_name] = $enrol_plugin;
            } else {
                $enrol_plugin = $enrol_plugins_cache[$enrol_plugin_name];
            }

            $enrol_plugin->update_user_enrol($enrol_plus_userid, $enrol_plus_userid->userid, $target_enrol_status);
        }
    }

    
    private static function update_enrols_status($enrols_to_update, $target_enrol_status) {
        global $DB;

        // Remove duplicates of course's enrol methods.
        $unique_enrol_instances = [];
        foreach ($enrols_to_update as $enrol_plus_userid) {
            $instance = $enrol_plus_userid;
            unset($instance->userid);
            unset($instance->ue_id);
            
            $unique_enrol_instances[$instance->id] = $instance;
        }

        $enrol_plugins_cache = [];

        foreach ($unique_enrol_instances as $instance) {

            if ($instance->status == $target_enrol_status)
                continue;  // Already in target state.
            
            $enrol_plugin_name = $instance->enrol;
            if (!array_key_exists($enrol_plugin_name, $enrol_plugins_cache)) {
                // Retrieve & cache enrol plugin.
                $enrol_plugin = enrol_get_plugin($enrol_plugin_name);
                $enrol_plugins_cache[$enrol_plugin_name] = $enrol_plugin;
            } else {
                $enrol_plugin = $enrol_plugins_cache[$enrol_plugin_name];
            }

            $enrol_plugin->update_status($instance, $target_enrol_status);
        }
    }
}
