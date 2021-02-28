<?php


// This file is part of X5Moodle (X5GON Activity plugin for Moodle)

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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**

  * X5GON Europeen project: AI based Recommendation System for Open Educative Resources
  * @package    mod_xfgon
  * @copyright  2018-2020 X5GON-Univ_Nantes_Team (https://chaireunescorel.ls2n.fr/, https://www.univ-nantes.fr/)
  * @license    BSD-2-Clause (https://opensource.org/licenses/BSD-2-Clause)

*/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/xfgon/backup/moodle2/restore_xfgon_stepslib.php'); // Because it exists (must)

/**
 * xfgon restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_xfgon_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // xfgon only has one structure step
        $this->add_step(new restore_xfgon_activity_structure_step('xfgon_structure', 'xfgon.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('xfgon', array('intro'), 'xfgon');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('XFGONMEDIAFILE', '/mod/xfgon/mediafile.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('XFGONVIEWBYID', '/mod/xfgon/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('XFGONINDEX', '/mod/xfgon/index.php?id=$1', 'course');
        $rules[] = new restore_decode_rule('XFGONVIEWPAGE', '/mod/xfgon/view.php?id=$1&pageid=$2', array('course_module', 'xfgon_page'));

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * xfgon logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('xfgon', 'add', 'view.php?id={course_module}', '{xfgon}');
        $rules[] = new restore_log_rule('xfgon', 'update', 'view.php?id={course_module}', '{xfgon}');
        $rules[] = new restore_log_rule('xfgon', 'view', 'view.php?id={course_module}', '{xfgon}');
        $rules[] = new restore_log_rule('xfgon', 'start', 'view.php?id={course_module}', '{xfgon}');
        $rules[] = new restore_log_rule('xfgon', 'end', 'view.php?id={course_module}', '{xfgon}');
        $rules[] = new restore_log_rule('xfgon', 'view grade', 'essay.php?id={course_module}', '[name]');
        $rules[] = new restore_log_rule('xfgon', 'update grade', 'essay.php?id={course_module}', '[name]');
        $rules[] = new restore_log_rule('xfgon', 'update email essay grade', 'essay.php?id={course_module}', '[name]');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('xfgon', 'view all', 'index.php?id={course}', null);

        return $rules;
    }


    /**
     * Re-map the dependency and activitylink information
     * If a depency or activitylink has no mapping in the backup data then it could either be a duplication of a
     * xfgon, or a backup/restore of a single xfgon. We have no way to determine which and whether this is the
     * same site and/or course. Therefore we try and retrieve a mapping, but fallback to the original value if one
     * was not found. We then test to see whether the value found is valid for the course being restored into.
     */
    public function after_restore() {
        global $DB;

        $xfgon = $DB->get_record('xfgon', array('id' => $this->get_activityid()), 'id, course, dependency, activitylink');
        $updaterequired = false;

        if (!empty($xfgon->dependency)) {
            $updaterequired = true;
            if ($newitem = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'xfgon', $xfgon->dependency)) {
                $xfgon->dependency = $newitem->newitemid;
            }
            if (!$DB->record_exists('xfgon', array('id' => $xfgon->dependency, 'course' => $xfgon->course))) {
                $xfgon->dependency = 0;
            }
        }

        if (!empty($xfgon->activitylink)) {
            $updaterequired = true;
            if ($newitem = restore_dbops::get_backup_ids_record($this->get_restoreid(), 'course_module', $xfgon->activitylink)) {
                $xfgon->activitylink = $newitem->newitemid;
            }
            if (!$DB->record_exists('course_modules', array('id' => $xfgon->activitylink, 'course' => $xfgon->course))) {
                $xfgon->activitylink = 0;
            }
        }

        if ($updaterequired) {
            $DB->update_record('xfgon', $xfgon);
        }
    }
}
