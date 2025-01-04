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
 * local course_trash settings
 *
 * @package    local_course_trash
 * @copyright  2024 Mikhail Denisov, Volgograd State Technical University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


if ($hassiteconfig) {
    global $CFG;
    require_once($CFG->dirroot . '/local/course_trash/locallib.php');

    $settings = new admin_settingpage('local_course_trash', new lang_string('settings', 'local_course_trash'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('local_course_trash_settings', get_string('editorcommonsettings' /*reuse from lang/moodle*/), get_string('pluginname_desc', 'local_course_trash')));


    $settings->add($setting = new admin_setting_configcheckbox('local_course_trash/enableplugin',
        new lang_string('enableplugin', 'local_course_trash'),
        new lang_string('enableplugin_help', 'local_course_trash'), 1));

    $settings->add($setting = new admin_settings_coursecat_select('local_course_trash/coursecat',
        new lang_string('coursecat', 'local_course_trash'),
        new lang_string('coursecat_help', 'local_course_trash'), 1));


    $settings->add(new admin_setting_heading('local_course_trash_settings', get_string('heading_courseoperations', 'local_course_trash'), get_string('heading_courseoperations_info', 'local_course_trash')));


    $settings->add($setting = new admin_setting_configcheckbox('local_course_trash/movetocategory',
        get_string('movetocategory', 'local_course_trash'), get_string('movetocategory_help', 'local_course_trash'), 1));


    $settings->add($setting = new admin_setting_configcheckbox('local_course_trash/hidecourse',
        get_string('hidecourse', 'local_course_trash'), get_string('hidecourse_help', 'local_course_trash'), 1));


    $options = array(
        LOCAL_COURSE_TRASH_SUSPEND_ANYONE         => get_string('suspend_anyone', 'local_course_trash'),
        LOCAL_COURSE_TRASH_SUSPEND_SELF_AND_ROLES => get_string('suspend_self_and_roles', 'local_course_trash'),
        LOCAL_COURSE_TRASH_SUSPEND_SELF_ONLY      => get_string('suspend_self_only', 'local_course_trash'),
        LOCAL_COURSE_TRASH_SUSPEND_NO_ONE         => get_string('suspend_no_one', 'local_course_trash')
        );

    $settings->add(new admin_setting_configselect('local_course_trash/suspendmode',
        get_string('suspendmode', 'local_course_trash'),
        get_string('suspendmode_help', 'local_course_trash'), LOCAL_COURSE_TRASH_SUSPEND_SELF_AND_ROLES, $options));

    $settings->add($setting = new admin_setting_pickroles('local_course_trash/suspendroles',
        new lang_string('suspendroles', 'local_course_trash'),
        new lang_string('suspendroles_help', 'local_course_trash'), ['editingteacher', 'teacher']));

    $settings->add($setting = new admin_setting_configcheckbox('local_course_trash/set_enddate',
        get_string('set_enddate', 'local_course_trash'), '[EXPERIMENTAL FEATURE!] ' . get_string('set_enddate_help', 'local_course_trash'), 0));

    $settings->add($setting = new admin_setting_configcheckbox('local_course_trash/saverestoredata',
        get_string('saverestoredata', 'local_course_trash'), get_string('saverestoredata_help', 'local_course_trash'), 1));

  
}
