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


/**
 * Define all the restore steps that will be used by the restore_xfgon_activity_task
 */

/**
 * Structure step to restore one xfgon activity
 */
class restore_xfgon_activity_structure_step extends restore_activity_structure_step {
    // Store the answers as they're received but only process them at the
    // end of the xfgon
    protected $answers = array();

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('xfgon', '/activity/xfgon');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_xfgon($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->available = $this->apply_date_offset($data->available);
        $data->deadline = $this->apply_date_offset($data->deadline);

        // The xfgon->highscore code was removed in MDL-49581.
        // Remove it if found in the backup file.
        if (isset($data->showhighscores)) {
            unset($data->showhighscores);
        }
        if (isset($data->highscores)) {
            unset($data->highscores);
        }

        // Supply items that maybe missing from previous versions.
        if (!isset($data->completionendreached)) {
            $data->completionendreached = 0;
        }
        if (!isset($data->completiontimespent)) {
            $data->completiontimespent = 0;
        }

        if (!isset($data->intro)) {
            $data->intro = '';
            $data->introformat = FORMAT_HTML;
        }

        // Compatibility with old backups with maxtime and timed fields.
        if (!isset($data->timelimit)) {
            if (isset($data->timed) && isset($data->maxtime) && $data->timed) {
                $data->timelimit = 60 * $data->maxtime;
            } else {
                $data->timelimit = 0;
            }
        }
        // insert the xfgon record
        $newitemid = $DB->insert_record('xfgon', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_xfgon_page($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->xfgonid = $this->get_new_parentid('xfgon');

    }

    protected function process_xfgon_answer($data) {
        global $DB;

        $data = (object)$data;
        $data->xfgonid = $this->get_new_parentid('xfgon');
    }

    protected function process_xfgon_attempt($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->xfgonid = $this->get_new_parentid('xfgon');
    }

    protected function process_xfgon_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->xfgonid = $this->get_new_parentid('xfgon');
        $data->userid = $this->get_mappingid('user', $data->userid);
    }

    protected function process_xfgon_branch($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->xfgonid = $this->get_new_parentid('xfgon');
        $data->userid = $this->get_mappingid('user', $data->userid);

    }

    protected function process_xfgon_highscore($data) {
        // Do not process any high score data.
        // high scores were removed in Moodle 3.0 See MDL-49581.
    }

    protected function process_xfgon_timer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->xfgonid = $this->get_new_parentid('xfgon');
        $data->userid = $this->get_mappingid('user', $data->userid);
        // Supply item that maybe missing from previous versions.
        if (!isset($data->completed)) {
            $data->completed = 0;
        }
    }

    /**
     * Process a xfgon override restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_xfgon_override($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        // Based on userinfo, we'll restore user overides or no.
        $userinfo = $this->get_setting_value('userinfo');

        // Skip user overrides if we are not restoring userinfo.
        if (!$userinfo && !is_null($data->userid)) {
            return;
        }

        $data->xfgonid = $this->get_new_parentid('xfgon');

        if (!is_null($data->userid)) {
            $data->userid = $this->get_mappingid('user', $data->userid);
        }
        if (!is_null($data->groupid)) {
            $data->groupid = $this->get_mappingid('group', $data->groupid);
        }

        $data->available = $this->apply_date_offset($data->available);
        $data->deadline = $this->apply_date_offset($data->deadline);

    }

    protected function after_execute() {
        global $DB;

        // Answers must be sorted by id to ensure that they're shown correctly
        ksort($this->answers);

        // Add xfgon files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_xfgon', 'intro', null);
        $this->add_related_files('mod_xfgon', 'mediafile', null);

    }
}
