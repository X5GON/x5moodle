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


namespace mod_xfgon\api;

use mod_xfgon\dimensions;

use stdClass;

defined('MOODLE_INTERNAL') || die();

class x5gon extends xfgon {
    public static function insert_tracking() {
        global $CFG, $USER, $OUTPUT;

        $template = new stdClass();

        $template->imagetrack = get_config('mod_xfgon', 'imagetrack');
        $template->siteurl = get_config('mod_xfgon', 'siteurl');
        $template->siteid = get_config('mod_xfgon', 'siteid');

        // Need to add an option for no tracking.
        $template->userid = $USER->id;
        $cleanurl = get_config('mod_xfgon', 'cleanurl');

        if (!empty($template->siteurl)) {
            if ($cleanurl) {
                $template->doctitle = "_paq.push(['setDocumentTitle', '".self::trackurl()."']);\n";
            } else {
                $template->doctitle = "";
            }

            if (self::should_track()) {
                $OUTPUT->render_from_template('mod_xfgon/x5gon', $template);

            }
        }

    }
}
