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

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/xfgon/locallib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('xfgon', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$xfgon = new xfgon($DB->get_record('xfgon', array('id' => $cm->instance), '*', MUST_EXIST));

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/xfgon:manage', $context);

$mode    = optional_param('mode', get_user_preferences('xfgon_view', 'collapsed'), PARAM_ALPHA);
// Ensure a valid mode is set.
if (!in_array($mode, array('single', 'full', 'collapsed'))) {
    $mode = 'collapsed';
}
$PAGE->set_url('/mod/xfgon/edit.php', array('id'=>$cm->id,'mode'=>$mode));
$PAGE->force_settings_menu();

if ($mode != get_user_preferences('xfgon_view', 'collapsed') && $mode !== 'single') {
    set_user_preference('xfgon_view', $mode);
}

$xfgonoutput = $PAGE->get_renderer('mod_xfgon');
$PAGE->navbar->add(get_string('edit'));
echo $xfgonoutput->header($xfgon, $cm, $mode, false, null, get_string('edit', 'xfgon'));

if (!$xfgon->has_pages()) {
    // There are no pages; give teacher some options
    require_capability('mod/xfgon:edit', $context);
    echo $xfgonoutput->add_first_page_links($xfgon);
} else {
    switch ($mode) {
        case 'collapsed':
            echo $xfgonoutput->display_edit_collapsed($xfgon, $xfgon->firstpageid);
            break;
        case 'single':
            $pageid =  required_param('pageid', PARAM_INT);
            $PAGE->url->param('pageid', $pageid);
            $singlepage = $xfgon->load_page($pageid);
            echo $xfgonoutput->display_edit_full($xfgon, $singlepage->id, $singlepage->prevpageid, true);
            break;
        case 'full':
            echo $xfgonoutput->display_edit_full($xfgon, $xfgon->firstpageid, 0);
            break;
    }
}

echo $xfgonoutput->footer();
