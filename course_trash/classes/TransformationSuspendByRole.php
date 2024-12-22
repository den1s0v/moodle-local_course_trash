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

        if ($course_transformer->is_trashing) {
            $target_enrol_status = ENROL_USER_SUSPENDED;
            
            $suspendmode = get_config('local_course_trash', 'suspendmode');

            switch ($suspendmode) {
                case LOCAL_COURSE_TRASH_SUSPEND_ANYONE:
                    self::enrols_on_course($course_transformer->course->id);
                    break;
                    
                case LOCAL_COURSE_TRASH_SUSPEND_SELF_AND_ROLES:
                    $suspendroles_str = get_config('local_course_trash', 'suspendroles');  // E.g. string(3) "3,4".

                    // $suspendroles = $suspendroles_str;
                    $suspendroles = explode(',', $suspendroles_str);

                    $user_enrols_to_update = self::enrols_on_course($course_transformer->course->id, $suspendroles);
                    break;
                
                case LOCAL_COURSE_TRASH_SUSPEND_SELF_ONLY:
                    $user_enrols_to_update = self::enrols_on_course($course_transformer->course->id, [], true);
                    break;
                
                case LOCAL_COURSE_TRASH_SUSPEND_NO_ONE:
                    $user_enrols_to_update = [];
                    break;
                
                default:
                    throw new \RuntimeException("Invalid local_course_trash / suspendmode value: $suspendmode", 1);
            }

        } else {
            $target_enrol_status = ENROL_USER_ACTIVE;

            $user_enrol_ids = $course_transformer->data['restored']['user_enrols_suspended'] ?: null;
            if ($user_enrol_ids) {
                $user_enrols_to_update = self::enrols_on_course($course_transformer->course->id, [], false, $user_enrol_ids, ENROL_USER_SUSPENDED);
            }
        }
        
        // Выполнить преобразование и зафиксировать информацию о сделанных изменениях.
        if ($user_enrols_to_update) {

            self::update_status_in_enrols($user_enrols_to_update, $target_enrol_status);

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
            $role_filter = "(e.roleid $insql)";
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
        
        // Query DB directly.
        $user_enrols = $DB->get_records_sql("SELECT ue.id as ue_id, e.*, ue.userid
        FROM {user_enrolments} ue
        JOIN {enrol} e ON ue.enrolid = e.id
        WHERE e.courseid = :course_id
        AND ($role_filter OR $user_filter OR $ue_ids_filter)
        AND ue.status = :ue_status
        ", $role_inparams + $ue_inparams + [
            'course_id' => $course_id, 
            // 'role_ids' => $role_ids, 
            'ue_status' => $ue_status, 
            'user_id' => $USER->id, 
            // 'explicit_ue_ids' => $explicit_ue_ids, 
        ]);

        // $ue_ids = [];
        // if ($rows) {
        //     foreach($rows as $id) {
        //         $ue_ids []= $id;
        //     }
        // }

        return $user_enrols;
    }
    
    
    private static function update_status_in_enrols($user_enrols_to_update, $target_enrol_status) {
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
}