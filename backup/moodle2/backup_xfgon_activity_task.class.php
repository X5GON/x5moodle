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

require_once($CFG->dirroot . '/mod/xfgon/backup/moodle2/backup_xfgon_stepslib.php');


class backup_xfgon_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the xfgon.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_xfgon_activity_structure_step('xfgon structure', 'xfgon.xml'));
    }

    /**
     * Encodes URLs to various Lesson scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot.'/mod/xfgon','#');

        // This file plays the mediafile set in xfgon settings.
        $pattern = '#'.$base.'/mediafile\.php\?id=([0-9]+)#';
        $replacement = '$@XFGONMEDIAFILE*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // This page lists all the instances of xfgon in a particular course
        $pattern = '#'.$base.'/index\.php\?id=([0-9]+)#';
        $replacement = '$@XFGONINDEX*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Link to one xfgon by cmid
        $pattern = '#'.$base.'/view\.php\?id=([0-9]+)#';
        $replacement = '$@XFGONVIEWBYID*$1@$';
        $content = preg_replace($pattern, $replacement, $content);

        // Return the now encoded content
        return $content;
    }
}
