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


// We define the web service functions to install.
$functions = array(

        'mod_xfgon_oermetadata_requests'=> array(
                'classname'   => 'mod_xfgon_external',
                'methodname'  => 'oermetadata_requests',
                'classpath'   => 'mod/xfgon/externallib.php',
                'description' => 'Webservices to fetch allowed OERs meta-data:</br>
                                  (1) <b>oerinfo</b>: webservice to fetch open educative resource metadata</br>
                                  (2) <b>courseoers</b>: webservice to fetch course metadata</br>
                                  (3) <b>oerslist</b>: webservice to fetch all open educative resources metadata',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'OER metadata API' => array(
                'functions' => array ('mod_xfgon_oermetadata_requests'),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
