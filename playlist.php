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


// echo "ou don't have any playlists";
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
$xfgonoutput = $PAGE->get_renderer('mod_xfgon');
$currenttab = 'playlist';

echo $xfgonoutput->header($xfgon, $cm, $currenttab, '', null);
echo '<div class="x5plgncer"><span class="x5loadingicon"><i class="fa fa-sync fa-spin fa-4x"></i></span>';

$plstfced = unserialize($xfgon->x5playlist);

// ideally to have a standard ui template
$plstui = '<div id="x5plstctr" class="x5ctrcs x5plstctrcs">';
$plstui.= '<div id="plstcr" class="plstcrcs">';

if ($plstfced->fetch == "success"){
    $playlistitems = $plstfced->plst->playlist_items;
    $plstitemsacs = send_post_request("https://wp3.x5gon.org/others/moodle/x5plgn/oeracs",
                                    array(
                                          "fre"=> "x5playlist",
                                          "max_pdres"=> 15,
                                          "grpgcrta"=> (object) array(
                                            "name"=> "crsuri",
                                            "value"=> $GLOBALS['GLBMDL_CFG']->wwwroot."/course/view.php?id=".$cm->course,
                                          ),
                                          "grpgcrta_data"=> (object) array(
                                            "crstitle"=> $xfgon->courserecord->fullname,
                                            "crssummary"=> $xfgon->courserecord->summary,
                                            "modtitle"=> $xfgon->properties()->name,
                                            "modsummary"=> $xfgon->properties()->intro,
                                            "customhint"=> ""
                                          ),
                                          "oers"=> array_map('intval', array_column($playlistitems, 'x5gon_id')),
                                    ))->pddata;
    $plstui.= '<table>';
    foreach ($playlistitems as $key => $value ){

        $itemacs = $plstitemsacs[$key];
        $plstui.= '<tr class="plstim" data-oerid="'.$value->x5gon_id.'">';
        if(count($value->images) > 0){
            $plstui.= '<td class="x5itemovw">';
            $plstui.= '<img src="http://x5learn.org/files/thumbs/'.$value->images[0].'">';
            $plstui.= '</td>';
        }else {
            $plstui.= '<td class="x5itemovw">';
            $plstui.= '<img src="pix/icon1.svg">';
            $plstui.= '</td>';
        }
        $plstui.= '<td class="x5itemactinfos plstimactinfos">';
        $plstui.= '<span class="x5itemactinfo plstimvws" title="viewed '. $itemacs->nbacess .' times"><i class="x5itemactinfoelm x5vwsicon" ><img src="pix/vws/vws.png"></i><small><i class="x5itemactinfoelm x5vwscont">'. $itemacs->nbacess .'</i></small></span>';
        $plstui.= '<span class="x5itemactinfo plstimlks" title="liked 0 times" style="display:none;"><i class="x5itemactinfoelm x5lksicon"><img src="pix/lks/lks.png"></i><small><i class="x5itemactinfoelm x5vwscont"></i>0</small></span>';
        $plstui.= '<span class="x5itemactinfo plstimdlks" title="disliked 0 times" style="display:none;"><i class="x5itemactinfoelm x5dlksicon"><img src="pix/dlks/dlks.png"><small></i><i class="x5itemactinfoelm x5vwscont">0</i></small></span>';
        $plstui.= '</td>';
        $plstui.= '<td class="x5itemcont">';
        $plstui.= '<table class="x5itemdtls" style="table-layout:fixed;width:100%;">';
        $plstui.= '<tr>';
        $plstui.= '<td>';
        $plstui.= '<span class="x5itemicon plstimicon"  title="'.$value->mediatype.'"><i class="fa '.get_res_awesomeicon($value->mediatype).'"></i></span>';
        $plstui.= '<strong class="x5itemtle plstimtle" title="'. $value->title .'">'. $value->title .'</strong></br>';
        $plstui.= '<small class="plstimurl" style="display:none;"><a href="'.$value->url.'" target="_blank">'. $value->url .'</a></small>';
        $plstui.= '</td>';
        $plstui.= '</tr>';
        $plstui.= '<tr>';
        $plstui.= '<td>';
        $plstui.= '<small class="plstimar"><b>Author:</b>'. $value->author.'</small>';
        $plstui.= '<small class="x5itemkwdscr"><small class="x5itemkwds plstimkwds"><i class="x5kwdsicon" title="Keywords"><img src="pix/kwds/kwds.png"></i><span class="x5kwdsct" title="'. join(", ", array_reverse(explode(',', $value->keywords))) .'">'. join(", ", array_reverse(explode(',', $value->keywords))) .'</span></small><small class="x5kwdsshmcr"><span class="x5kwdsshm">see more...</span></small></small></br>';
        $plstui.= '</td>';
        $plstui.= '</tr>';
        $plstui.= '<tr>';
        $plstui.= '<td>';
        $plstui.= '<small class="plstimpr"><b>Provider:</b>'. $value->provider .'</small>';
        $plstui.= '<small class="x5itemcptscr"><small class="x5itemcpts plstimcpts"><i class="x5cptsicon" title="Concepts"><img src="pix/cpts/cpts.png"></i><span class="x5cptsct" title="'. join(", ", array_reverse(array_column($value->concepts, 'label'))) .'">'. join(", ", array_reverse(array_map(function($ix, $x) { return "<a class='x5cptsctelm' title='".$x->label."' data-cptelmix='".$ix."' target='_blank' href='".$x->url."'>".$x->label."</a>"; }, array_keys($value->concepts), $value->concepts))) .'</span></small><small class="x5cptsshmcr"><span class="x5cptsshm">see more...</span></small></small>';
        $plstui.= '</td>';
        $plstui.= '</tr>';
        $plstui.= '</table>';
        $plstui.= '</td>';
        $plstui.= '</tr>';
    }
}else{
    $plstui .= '<div class="plstedtgen">';
    $plstui .= 'No items configured yet to be shown.';
    $plstui .= '</div>';
}
$plstui.= '</table>';
$plstui.= '</div>';
$plstui.= '</div>';
$plstui.= '</div>';
$plstui.= '<script language="javascript">window.x5plst_lst='.json_encode($playlistitems).';</script>';
echo $plstui;
// xfgon currently has no content. A message for display has been prepared and will be displayed by the header method
// of the xfgon renderer.
echo $xfgonoutput->footer();
exit();
