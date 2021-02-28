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

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/mod/xfgon/locallib.php');
require_once($CFG->libdir . '/grade/constants.php');

$id      = required_param('id', PARAM_INT);             // Course Module ID
$backtocourse = optional_param('backtocourse', false, PARAM_RAW);

$cm = get_coursemodule_from_id('xfgon', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$xfgon = new xfgon($DB->get_record('xfgon', array('id' => $cm->instance), '*', MUST_EXIST), $cm, $course);

require_login($course, false, $cm);

if ($backtocourse) {
    redirect(new moodle_url('/course/view.php', array('id'=>$course->id)));
}

// Apply overrides.
$xfgon->update_effective_access($USER->id);

$url = new moodle_url('/mod/xfgon/view.php', array('id'=>$id));

$PAGE->set_url($url);
$PAGE->force_settings_menu();

$context = $xfgon->context;
$canmanage = $xfgon->can_manage();

$xfgonoutput = $PAGE->get_renderer('mod_xfgon');

// To avoid multiple calls, store the magic property firstpage.
$currenttab = 'view';

// First tab redirect
$firsttaburl = new moodle_url('/mod/xfgon/discovery.php', array('id'=>$id));
redirect($firsttaburl);

echo $xfgonoutput->header($xfgon, $cm, $currenttab, '', null);
// xfgon currently has no content. A message for display has been prepared and will be displayed by the header method
// of the xfgon renderer.
echo "welcome to x5gon view page";
echo $xfgonoutput->footer();
exit();
