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

/// This file to be included so we can assume config.php has already been included.
global $DB;
if (empty($xfgon)) {
    print_error('cannotcallscript');
}
if (!isset($currenttab)) {
    $currenttab = '';
}
if (!isset($cm)) {
    $cm = get_coursemodule_from_instance('xfgon', $xfgon->id);
    $context = context_module::instance($cm->id);
}
if (!isset($course)) {
    $course = $DB->get_record('course', array('id' => $xfgon->course));
}

$tabs = $row = $inactive = $activated = array();

/// user attempt count for reports link hover (completed attempts - much faster)
$actd_x5fres= unserialize(($DB->get_record('xfgon', array('id'=>$xfgon->id)))->contentsettings);
foreach ($actd_x5fres as $key => $value) {
   if ($value == '1'){
      $x5fre = explode('x5', $key)[1];
      $row[] = new tabobject($x5fre, "$CFG->wwwroot/mod/xfgon/".$x5fre.".php?id=$cm->id", get_string($x5fre, 'xfgon'), get_string('preview'.$x5fre, 'xfgon', format_string($xfgon->name)));

   }
}
$tabs[] = $row;

print_tabs($tabs, $currenttab, $inactive, $activated);
