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


require_once($CFG->libdir . "/externallib.php");
require_once('lib/lib.php');

class mod_xfgon_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function oermetadata_requests_parameters() {
        return new external_function_parameters(
                array(
                  'x5goninfotype' => new external_value(PARAM_TEXT, 'Information type needed to be known by x5gon. By default it is "Specify your request"', VALUE_DEFAULT, 'Specify your request'),
                  'x5goninfoparam' => new external_value(PARAM_TEXT, 'Information parameters. By default it is "Specify your parameters"', VALUE_DEFAULT, 'Specify your parameters')
                )
        );
    }

    /**
     * Returns welcome message
     * @return string welcome message
     */
    public static function oermetadata_requests($x5goninfotype = 'Specify your request',$x5goninfoparam = 'Specify your parameters') {
        global $USER;
        //Parameter validation
        //REQUIRED
        $params = self::validate_parameters(self::oermetadata_requests_parameters(),
                  array(
                    'x5goninfotype'    => $x5goninfotype,
                    'x5goninfoparam'   => $x5goninfoparam
                  )

                );

        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        //Validate plugin accessibility
        $plugin_accessibility=validate_plugin_accessibility();
        // Send back response dependings on X5gon request
        switch ($x5goninfotype) {

              case 'oerinfos':
                //all Moodle module infos: including its course,categ and OER finalFiles(resources) infos
                if($plugin_accessibility->response_notice==1){
                    return json_encode( get_oermdlmodule_infos($x5goninfoparam) );
                }else{
                    return json_encode($plugin_accessibility);
                }
                break;

              case 'courseoers':
                // all course oers and related infos
                if($plugin_accessibility->response_notice==1){
                    return json_encode( get_course_oers_clean($x5goninfoparam ) );
                }else{
                    return json_encode($plugin_accessibility);
                }
                break;

              case 'oerslist':
                //all OER Moodle modules and their infos: including their course,categ and OER finalFiles(resources) infos
                if($plugin_accessibility->response_notice==1){
                    return json_encode( get_oers_list_clean() );
                }else{
                    return json_encode($plugin_accessibility);
                }
                break;

              default:
                break;
        }



    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function oermetadata_requests_returns() {
        //return new external_value(PARAM_TEXT, 'The welcome message + user first name');
        return new external_value(PARAM_RAW, 'The informations Object');
    }


}
