<?php

// This file is part of the X5GON Activity plugin for Moodle

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
require_once($CFG->libdir . '/filelib.php');

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
$currenttab = 'recommend';

echo $xfgonoutput->header($xfgon, $cm, $currenttab, '', null);
echo '<div class="x5plgncer"><span class="x5loadingicon"><i class="fa fa-sync fa-spin fa-4x"></i></span>';

// xfgon currently has no content. A message for display has been prepared and will be displayed by the header method
// of the xfgon renderer.
$cncheck= 0;
$recitemspost = new stdClass();
$recitems = array();
$recitemsrks = array();
while ($cncheck < 2 and !property_exists($recitemspost, "pddata")) {
    $cncheck += 1;
    $recitemspost = send_post_request("https://wp3.x5gon.org/others/moodle/x5plgn/frepte",
                                    array(
                                          "fre"=> "x5recommend",
                                          "max_pdres"=> 10,
                                          "grpgcrta"=> (object) array(
                                            "name"=> "crsuri",
                                            "value"=> $GLOBALS['GLBMDL_CFG']->wwwroot."/course/view.php?id=".$cm->course,
                                          ),
                                          "grpgcrta_data"=> (object) array(
                                            "crstitle"=> $xfgon->courserecord->fullname,
                                            "crssummary"=> $xfgon->courserecord->summary,
                                            "modtitle"=> $xfgon->properties()->name,
                                            "modsummary"=> $xfgon->properties()->intro,
                                            "modx5init"=> str_replace(",", " ", unserialize($xfgon->properties()->contentsettings)->x5discoverystgs->init),
                                            "customhint"=> ""
                                          ),
                                          "insid_data"=> (object) array(
                                            "crsurl"=> $GLOBALS['GLBMDL_CFG']->wwwroot."/course/view.php?id=".$cm->course,
                                            "modurl"=> $GLOBALS['GLBMDL_CFG']->wwwroot."/mod/".$cm->modname."/view.php?id=".$cm->id
                                          )
                                    ));
    if(property_exists($recitemspost, "pddata")){
        $recitems= $recitemspost->pddata;
        $recitemsrks= $recitemspost->rkdata;
    }
}

// ideally to have a standard ui template
$recui = '<div id="x5recctr" class="x5ctrcs x5recctrcs">';
$recui.= '<div id="reccr" class="reccrcs">';
$recui.= '<table>';
foreach (array_keys($recitems) as $key){
    $item = $recitems[$key];
    $itemrk = $recitemsrks[$key];

    if ($item->url != '' and $item->url != null){
        $recui.= '<tr class="recim" data-oerid="'.$item->id.'">';
        if(isset($item->thumbnail) and $item->thumbnail != ''){
            $recui.= '<td class="x5itemovw">';
            $recui.= '<img src="'.$item->thumbnail.'">';
            $recui.= '</td>';
        }else {
            $recui.= '<td class="x5itemovw">';
            $recui.= '<img src="pix/icon1.svg">';
            $recui.= '</td>';
        }
        $recui.= '<td class="x5itemactinfos recimactinfos">';
        $recui.= '<span class="x5itemactinfo recimvws" title="viewed '. $itemrk->nbacess .' times"><i class="x5itemactinfoelm x5vwsicon" ><img src="pix/vws/vws.png"></i><small><i class="x5itemactinfoelm x5vwscont">'. $itemrk->nbacess .'</i></small></span>';
        $recui.= '<span class="x5itemactinfo recimlks" title="liked 0 times" style="display:none;"><i class="x5itemactinfoelm x5lksicon"><img src="pix/lks/lks.png"></i><small><i class="x5itemactinfoelm x5vwscont"></i>0</small></span>';
        $recui.= '<span class="x5itemactinfo recimdlks" title="disliked 0 times" style="display:none;"><i class="x5itemactinfoelm x5dlksicon"><img src="pix/dlks/dlks.png"><small></i><i class="x5itemactinfoelm x5vwscont">0</i></small></span>';
        $recui.= '</td>';
        $recui.= '<td class="x5itemcont">';
        $recui.= '<table class="x5itemdtls" style="table-layout:fixed;width:100%;">';
        $recui.= '<tr>';
        $recui.= '<td>';
        $recui.= '<span class="x5itemicon recimicon" title="'.$item->mimetype.'"><i class="fa '.get_res_awesomeicon($item->mimetype).'"></i></span>';
        $recui.= '<strong class="x5itemtle recimtle" title="'. $item->title .'">'. $item->title .'</strong></br>';
        $recui.= '<small class="recimurl" style="display:none;"><a href="'.$item->url.'" target="_blank">'. $item->url .'</a></small>';
        $recui.= '</td>';
        $recui.= '</tr>';
        $recui.= '<tr>';
        $recui.= '<td>';
        $recui.= '<small class="recimlge"><b>Language:</b>'. $item->orig_lang .'</small>';
        $recui.= '<small class="x5itemkwdscr"><small class="x5itemkwds recimkwds"><i class="x5kwdsicon" title="Keywords"><img src="pix/kwds/kwds.png"></i><span class="x5kwdsct" title="'. join(", ", array_reverse(array_column($item->keywords, 'label'))) .'">'. join(", ", array_reverse(array_column($item->keywords, 'label'))) .'</span></small><small class="x5kwdsshmcr"><span class="x5kwdsshm">see more...</span></small></small>';
        $recui.= '</td>';
        $recui.= '</td>';
        $recui.= '<tr>';
        $recui.= '<td>';
        $recui.= '<small class="recimpr"><b>Provider:</b>'. $item->provider .'</small>';
        $recui.= '<small class="x5itemcptscr"><small class="x5itemcpts recimcpts"><i class="x5cptsicon" title="Concepts"><img src="pix/cpts/cpts.png"></i><span class="x5cptsct" title="'. join(", ", array_reverse(array_column($item->concepts, 'label'))) .'">'. join(", ", array_reverse(array_map(function($ix, $x) { return "<a class='x5cptsctelm' title='".$x->label."' data-cptelmix='".$ix."' target='_blank' href='".$x->url."'>".$x->label."</a>"; }, array_keys($item->concepts), $item->concepts))) .'</span></small><small class="x5cptsshmcr"><span class="x5cptsshm">see more...</span></small></small>';
        $recui.= '</td>';
        $recui.= '</tr>';
        $recui.= '</table>';
        $recui.= '</td>';
        $recui.= '</tr>';
    }
}
$recui.= '</table>';
$recui.= '</div>';
$recui.= '</div>';
$recui.= '</div>';
$recui.= '<script language="javascript">window.x5rec_lst='.json_encode($recitems).';</script>';
echo $recui;

echo $xfgonoutput->footer();
exit();
