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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/xfgon/locallib.php');
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_AUTO,
                                   RESOURCELIB_DISPLAY_EMBED,
                                   RESOURCELIB_DISPLAY_OPEN,
                                   RESOURCELIB_DISPLAY_POPUP,
                                  );

}



if (is_siteadmin()) {

        //general plugin settings
        $name = 'mod_xfgon/enabled';
        $title = get_string('enabled', 'mod_xfgon');
        $description = get_string('enabled_desc', 'mod_xfgon');
        $default = true;
        $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
        $settings->add($setting);

        //provider token
        $name = 'mod_xfgon/providertoken';
        $title = get_string('providertoken', 'mod_xfgon');
        $description = get_string('providertoken_desc', 'mod_xfgon');
        $default = 'x5gonPartnerToken';
        $setting = new admin_setting_configtext($name, $title, $description, $default);
        $settings->add($setting);

        //category settings
        $name = 'mod_xfgon/oercategories';
        $title = get_string('oercategories', 'mod_xfgon');
        $description = get_string('oercategories_desc', 'mod_xfgon');
        $default = '';
        $setting = new admin_setting_configtext($name, $title, $description, $default);
        $settings->add($setting);

        //course settings
        $name = 'mod_xfgon/courseallowhidden';
        $title = get_string('courseallowhidden', 'mod_xfgon');
        $description = get_string('courseallowhidden_desc', 'mod_xfgon');
        $default = false;
        $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
        $settings->add($setting);

        $name = 'mod_xfgon/courseallowtypeswithpass';
        $title = get_string('courseallowtypeswithpass', 'mod_xfgon');
        $description = get_string('courseallowtypeswithpass_desc', 'mod_xfgon');
        $default = false;
        $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
        $settings->add($setting);

        $name = 'mod_xfgon/courseallowedenroltypes';
        $title = get_string('courseallowedenroltypes', 'mod_xfgon');
        $description = get_string('courseallowedenroltypes_desc', 'mod_xfgon');
        $default = 'guest';
        $setting = new admin_setting_configtext($name, $title, $description, $default);
        $settings->add($setting);

        //course modules settings
        $name = 'mod_xfgon/moduleconsideredmoduletypes';
        $title = get_string('moduleconsideredmoduletypes', 'mod_xfgon');
        $description = get_string('moduleconsideredmoduletypes_desc', 'mod_xfgon');
        $default = 'resource,xfgon,assign,book,chat,choice,data,feedback,folder,forum,glossary,imscp,label,xfgon,lti,page,quiz,scorm,survey,wiki,workshop';
        $setting = new admin_setting_configtext($name, $title, $description, $default);
        $settings->add($setting);

        $name = 'mod_xfgon/moduleallowhidden';
        $title = get_string('moduleallowhidden', 'mod_xfgon');
        $description = get_string('moduleallowhidden_desc', 'mod_xfgon');
        $default = false;
        $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
        $settings->add($setting);

        $name = 'mod_xfgon/moduleignoreavailrestric';
        $title = get_string('moduleignoreavailrestric', 'mod_xfgon');
        $description = get_string('moduleignoreavailrestric_desc', 'mod_xfgon');
        $default = false;
        $setting = new admin_setting_configcheckbox($name, $title, $description, $default, true, false);
        $settings->add($setting);

        //module files settings
        $name = 'mod_xfgon/fileallowedlicenses';
        $title = get_string('fileallowedlicenses', 'mod_xfgon');
        $description = get_string('fileallowedlicenses_desc', 'mod_xfgon');
        $default = "public,cc,cc-nd,cc-nc-nd,cc-nc,cc-nc-sa,cc-sa";
        $choices = array('public,cc,cc-nd,cc-nc-nd,cc-nc,cc-nc-sa,cc-sa'=>'public,cc,cc-nd,cc-nc-nd,cc-nc,cc-nc-sa,cc-sa');
        $setting = new admin_setting_configselect($name, $title, $description,$default,$choices);

        $settings->add($setting);

}
