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



require_once(__DIR__.'/../lib.php');


$rec_data = $_POST;


switch ($rec_data['action']) {
      case 'activity2log':
        echo trace_to_log($rec_data);
        break;
      default:
        // code...
        break;
}


////////////Pilot functions//////////
function trace_to_log($data){

      global $DB;
      $logdata = new stdclass();
      $logdata->timestamp = time();
      $logdata->crsid = $data['crsid'];
      $logdata->crsuri = $data['crsuri'];
      $logdata->mdlid = $data['mdlid'];
      $logdata->mdluri = $data['mdluri'];
      $logdata->usrid = $data['usrid'];
      $logdata->action = $data['act'];
      $logdata->actdata = json_encode($data['actdata']);
      $logid = $DB->insert_record("xfgon_x5".$data['x5cpnt']."log", $logdata);
      return json_encode($logid);

}



///////// pretreatment functions/////////
function search($data){
    $req_data = $data;
    unset($req_data['action']);
    unset($req_data['url']);
    $req_data['max_concepts'] = (int)$req_data['max_concepts'];
    $req_data['max_resources'] = (int)$req_data['max_resources'];

    return json_encode(send_post_request($data['url'], $req_data));

}

function frepte($data){

    $req_data = $data;
    unset($req_data['action']);
    unset($req_data['url']);
    $req_data['max_pdres'] = (int)$req_data['max_pdres'];
    return json_encode(send_post_request($data['url'], $req_data));
}

function freacs($data){
    $req_data = $data;
    unset($req_data['action']);
    unset($req_data['url']);

    return json_encode(send_post_request($data['url'], $req_data));

}

function actacqrqt($data){
    $req_data = $data;
    unset($req_data['action']);
    unset($req_data['url']);

    return json_encode(send_get_request($data['url']));

}
