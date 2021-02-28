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

use tool_policy\api;
use tool_policy\policy_version;

function xmldb_xfgon_install() {
    global $DB;

    //Add X5GON privacy policy if possible
    try {
          $dbman = $DB->get_manager();
          $moodle_version=$version = get_config('moodle', 'version');
          if($moodle_version>=3.5 && ( $dbman->table_exists('tool_policy_versions') && $dbman->table_exists('tool_policy') ) && !$DB->get_record('tool_policy_versions', array('name' => 'X5GON Privacy Policy')) ){

                //Get X5GON privacy policy
                $x5gonprivacypolicy=getX5gon_privacypolicy('https://platform.x5gon.org/privacy');
                $newprivacypolicyX5gon=[
                    'name' => 'X5GON Privacy Policy',
                    'revision' => 'v1',
                    'type' => 1,
                    'summary' => 'X5GON Privacy Policy for the H2020 Europeen Project',
                    'summaryformat' => 1,
                    'content' => $x5gonprivacypolicy,
                    'contentformat' => 1,
                ];
                $createdversion=create_versions(1, $newprivacypolicyX5gon);
                api::make_current($createdversion[0]->id);

          }

      } catch (\Exception $e) {

      }

    return true;
}

function getX5gon_privacypolicy($x5gonpolicyurl){

      $defaultx5gonprivacypolicy='<p></p><p>The X5GON project is creating a solution that will help users/students find what they need not just in Open Educational Resources (OER) repositories, but across all open educational resources on the web. This solution will adapt to the user’s needs and learn how to make ongoing customized recommendations and suggestions through a truly interactive and impactful learning experience. This new AI-driven platform will deliver OER content from everywhere, for the students’ need at the right time and place.</p><h4>About the Data We Collect</h4><p>Within the project we collect learning materials data that are openly licensed, and user activity data that is acquired by the X5GON snippet integrated in different OER repositories. The user data we acquire through the snippet is anonimized and consists of the following values:</p><ul><li><strong>User ID.</strong> This value is the identifier of the user accessing the learning material. It is created using the X5GON snippet and stored as a cookie in the users browser. The value is randomly generated, anonimized, and cannot be used to get back to the user.</li><li><strong>Material URL.</strong> This value is the material identifier and the link that the user visited.</li><li><strong>Referrer URL.</strong> This URL is the link from which the user arrived to the material.</li><li><strong>Access Date.</strong> The date at which the material was accessed.</li><li><strong>User Agents.</strong> This attribute contains information about the techonology the user used to access the material.</li><li><strong>Language.</strong> The language configuration used in users technology.</li><li><strong>User Location.</strong> The geographical location from which the user accessed the material, e.g. city, country and continent. <b>NOTE:</b> This value will be calculated using the users IP. The user IP is <b>NOT</b> stored in our databases.</li></ul><p>For more information about the acquired data access the X5GON snippet documentation available <a href="https://platform.x5gon.org/docs/x5gon-docs.pdf" target="_blank" rel="noreferrer noopener">HERE</a>.</p><h4>How is the Data Processed</h4><p>The acquired user data is used to identify users learning interests and give personalized recommendations. The material URL, refferer URL, access date and language will be given to the learning analytics engine and recommendation engine which will return a list of learning materials that the user might be interested in. Additionally, it will be used to identify which OER materials are highly requested and frequently viewed (giving an indication of its quality), and to find learning pathways, e.g. sequence of materials the users tend to follow.</p><p>User agents value is used to distinguish data of real users from bots allowing us to improve learning analytics and recommendation results by using only real users data.</p><h4>How Can a User Stop Participating</h4><p>To stop participating the user can delete the cookie named <b>x5gonTrack</b> which contains the user generated ID. This can be done in the the users browser. In addition, on the OER repositories that are a member of the X5GON Network, you must disable providing your user activity information. <b>NOTE:</b> this will also stop giving the user personalized recommendations on OER repositories that included the X5GON snippet.</p><h4>Who are Processing Your Data</h4><p>Your data is processed by the X5GON Project consortium which include:</p><ul><li><a href="https://www.ucl.ac.uk/">UNIVERSITY COLLEGE LONDON <i></i></a></li><li><a href="https://www.ijs.si/ijsw">INSTITUT JOŽEF STEFAN <i></i></a></li><li><a href="http://www.k4all.org/">KNOWLEDGE 4 ALL FOUNDATION <i></i></a></li><li><a href="http://www.univ-nantes.fr/english-version/welcome-to-universite-de-nantes-714591.kjsp">UNIVERSITÉ DE NANTES <i></i></a></li><li><a href="https://www.posta.si/zasebno">POSTA SLOVENIJE <i></i></a></li><li><a href="http://www.upv.es/index-en.html">UNIVERSITAT POLITECNICA DE VALENCIA <i></i></a></li><li><a href="https://www.uni-osnabrueck.de/en/home.html">UNIVERSITAET OSNABRUECK <i></i></a></li><li><a href="http://www.mizs.gov.si/">MINISTRY OF EDUCATION OF SLOVENIA <i></i></a></li></ul><br />';

      $ch = curl_init();
      $timeout = 10;
      //curl_setopt($ch, CURLOPT_HEADER, false);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_URL, $x5gonpolicyurl);
      //$response = filter_content(curl_exec($ch));
      $response = curl_exec($ch);
      curl_close($ch);
      if($response==""){
            $response=$defaultx5gonprivacypolicy;
      }

      return $response;
}

function filter_content($content){
		$contentText = '';
		$output = preg_match_all('/\<main.+role=\"main\"\>(.*)\<\/main\>/is',$content,$matches);
		$contentText = isset($matches[1][0])?$matches[1][0]:'';
		return $contentText;
}

function create_versions($numversions, $params) {
    // Prepare a policy document with some versions.
    $policy = add_policy($params);

    for ($i = 2; $i <= $numversions; $i++) {
        $formdata = api::form_policydoc_data($policy);
        $formdata->revision = 'v'.$i;
        api::form_policydoc_update_new($formdata);
    }

    $list = api::list_policies($policy->get('policyid'));

    return $list[0]->draftversions;
}

function add_policy($params){

      $counter = 0;
      $counter++;
      $defaults = [
          'name' => 'Policy '.$counter,
          'summary_editor' => ['text' => $params['summary'], 'format' => FORMAT_HTML, 'itemid' => 0],
          'content_editor' => ['text' => $params['content'], 'format' => FORMAT_HTML, 'itemid' => 0],
      ];

      $params = (array)$params + $defaults;
      $formdata = api::form_policydoc_data(new policy_version(0));
      foreach ($params as $key => $value) {
          $formdata->$key = $value;
      }
      return api::form_policydoc_add($formdata);
}
