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
$currenttab = 'discovery';

echo $xfgonoutput->header($xfgon, $cm, $currenttab, '', null);
// xfgon currently has no content. A message for display has been prepared and will be displayed by the header method
// of the xfgon renderer.
echo '<div class="x5plgncer"><span class="x5loadingicon"><i class="fa fa-sync fa-spin fa-4x"></i></span>';

$discprsedqries = send_post_request("https://wp3.x5gon.org/others/moodle/x5plgn/frepte",
                                array(
                                      "fre"=> "x5discovery",
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
                                        "customhint"=> ""
                                      )
                                ))->pddata;
$ilsearchq = $cm->name;
if ( count($discprsedqries) > 0 ){
    $ilsearchq = $discprsedqries[0]->query;
}

$cncheck= 0;
$discitemspost = new stdClass();
$discitems = array();
while ($cncheck < 2 and !property_exists($discitemspost, "result")) {
    $cncheck += 1;
    $discitemspost = send_post_request("https://wp3.x5gon.org/others/modelsdsh/search",
                                    array(
                                          "q"=> $ilsearchq,
                                          "max_resources"=> 10,
                                          "max_concepts"=> 5
                                    ));
    if(property_exists($discitemspost, "result")){
        $discitems= $discitemspost->result;
    }
}


$discitemsacs = send_post_request("https://wp3.x5gon.org/others/moodle/x5plgn/oeracs",
                                array(
                                      "fre"=> "x5discovery",
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
                                        "customhint"=> ""
                                      ),
                                      "oers"=> array_column($discitems, 'id'),
                                ))->pddata;


// ideally to have a standard ui template
$discui = '<div id="x5discctr" class="x5ctrcs x5discctrcs">';

$discui.= '<div id="discprqries" class="discprqriescs">';
// $discui.= '<span id="discprqrieslab" class="discprqrieslabcs"><b>Proposed queries</b></span>';
$discui.= '<div id="discprqrieslab" class="discprqrieslabcs">';
$discui.= '<span class="pdlabctcs"><b>X5 course trends</b></span>';
$discui.= '<span id="pdlabicn" class="pdlabicncs"><i class="fa fa-redo"></i></span>';
$discui.= '</div>';
$discui.= '<div id="discprqriescont" class="discprqriescontcs">';
foreach ($discprsedqries as $qkey => $qvalue) {
      $discui.= '<span class="discprqriesim" data-prqrieix="'.$qkey.'" title="`'. $qvalue->query .'` searched `'.$qvalue->nbsearch.'` times">';
      $discui.= '<span class="prqriesimicon"><i class="fa fa-hashtag"></i></span>';
      $discui.= '<span class="prqriesimcont">';
      $discui.= '<small class="prqriesimq">'. $qvalue->query .'</small>';
      $discui.= '</span>';
      $discui.= '</span></br>';
}
$discui.= '</div>';
$discui.= '</div>';

$discui.= '<div id="disccr" class="disccrcs">';
$discui.= '<div id="discsearch" class="discsearchcs">';
$discui.= '<div id="discsch" class="discschcs">';
$discui.= '<input type="text" id="schinpt" class="schinptcs" value="'.$ilsearchq.'" placeholder="Search with X5 Discovery">';
$discui.= '<button type="submit" id="schbtn" class="schbtncs" value="Search"><i class="fa fa-search"></i></button>';
$discui.= '</div>';
$discui.= '</div>';

$discui.= '<div id="discrslts" class="discrsltscs">';
$discui.= '<table>';
foreach (array_keys($discitems) as $key){
    $item = $discitems[$key];
    $itemacs = $discitemsacs[$key];
    if ($item->url != ''){
        $discui.= '<tr class="discim" data-oerid="'.$item->id.'">';
        if(isset($item->thumbnail) and $item->thumbnail != ''){
            $discui.= '<td class="x5itemovw">';
            $discui.= '<img src="'.$item->thumbnail.'">';
            $discui.= '</td>';
        }else {
            $discui.= '<td class="x5itemovw">';
            $discui.= '<img src="pix/icon1.svg">';
            $discui.= '</td>';
        }
        $discui.= '<td class="x5itemactinfos discimactinfos">';
        $discui.= '<span class="x5itemactinfo discimvws" title="viewed '. $itemacs->nbacess .' times"><i class="x5itemactinfoelm x5vwsicon" ><img src="pix/vws/vws.png"></i><small><i class="x5itemactinfoelm x5vwscont">'. $itemacs->nbacess .'</i></small></span>';
        $discui.= '<span class="x5itemactinfo discimlks" title="liked 0 times" style="display:none;"><i class="x5itemactinfoelm x5lksicon"><img src="pix/lks/lks.png"></i><small><i class="x5itemactinfoelm x5vwscont"></i>0</small></span>';
        $discui.= '<span class="x5itemactinfo discimdlks" title="disliked 0 times" style="display:none;"><i class="x5itemactinfoelm x5dlksicon"><img src="pix/dlks/dlks.png"><small></i><i class="x5itemactinfoelm x5vwscont">0</i></small></span>';
        $discui.= '</td>';
        $discui.= '<td class="x5itemcont">';
        $discui.= '<table class="x5itemdtls" style="table-layout:fixed;width:100%;">';
        $discui.= '<tr>';
        $discui.= '<td>';
        $discui.= '<span class="x5itemicon discimicon" title="'.$item->mimetype.'"><i class="fa '.get_res_awesomeicon($item->mimetype).'"></i></span>';
        $discui.= '<strong class="x5itemtle discimtle" title="'. $item->title .'">'. $item->title .'</strong></br>';
        $discui.= '<small class="discimurl" style="display:none;"><a href="'.$item->url.'" target="_blank">'. $item->url .'</a></small>';
        $discui.= '</td>';
        $discui.= '</tr>';
        $discui.= '<tr>';
        $discui.= '<td>';
        $discui.= '<small class="discimlge"><b>Language:</b>'. $item->orig_lang .'</small>';
        $discui.= '<small class="x5itemkwdscr"><small class="x5itemkwds discimkwds" title="Keywords"><i class="x5kwdsicon"><img src="pix/kwds/kwds.png"></i><span class="x5kwdsct" title="'. join(", ", array_reverse(array_column($item->keywords, 'label'))) .'">'. join(", ", array_reverse(array_column($item->keywords, 'label'))) .'</span></small><small class="x5kwdsshmcr"><span class="x5kwdsshm">see more...</span></small></small></br>';
        $discui.= '</td>';
        $discui.= '</tr>';
        $discui.= '<tr>';
        $discui.= '<td>';
        $discui.= '<small class="discimpr"><b>Provider:</b>'. $item->provider .'</small>';
        $discui.= '<small class="x5itemcptscr"><small class="x5itemcpts discimcpts" title="Concepts"><i class="x5cptsicon"><img src="pix/cpts/cpts.png"></i><span class="x5cptsct" title="'. join(", ", array_reverse(array_column($item->wikifier, 'label'))) .'">'. join(", ", array_reverse(array_map(function($ix, $x) { return "<a class='x5cptsctelm' title='".$x->label."' data-cptelmix='".$ix."' target='_blank' href='".$x->url."'>".$x->label."</a>"; }, array_keys($item->wikifier), $item->wikifier))) .'</span></small><small class="x5cptsshmcr"><span class="x5cptsshm">see more...</span></small></small>';
        $discui.= '</td>';
        $discui.= '</tr>';
        $discui.= '</table>';
        $discui.= '</td>';
        $discui.= '</tr>';
    }
}
$discui.= '</table>';
$discui.= '</div>';
$discui.= '</div>';
$discui.= '</div>';
$discui.= '</div>';
$discui.= '</div>';
$discui.= '<script language="javascript">window.x5disc_lst='.json_encode($discitems).'; window.x5trnd_lst='.json_encode($discprsedqries).';</script>';
echo $discui;
echo $xfgonoutput->footer();
exit();
