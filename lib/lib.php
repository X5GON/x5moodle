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



    /**
        Global variables: meant to be loaded from X5GON plugin settings directly , in the future.
    */
        /////Plugin default Parameters
        //Plugin general parameters
        $GLOBALS['glbstg_plugenabled']=1;
        //Moodle Categories: Set globally the array of catagories meant to be considered as 'OER' categories.
        $GLOBALS['glbstg_crs_category']=array();
        //Moodle Courses : Controlling globally ALLOWED course 'visisbility' & 'enrolment types'.
        $GLOBALS['glbstg_crs_allow_typeswith_pass']=0;
        $GLOBALS['glbstg_crs_allowed_enrolment_types']=array('guest');
        $GLOBALS['glbstg_crs_allowhidden_courses']=0;
        //Moodle Modules: Controlling globally ALLOWED module 'visibility' & 'availability'. This is including both SECTION + MODULE.
        $GLOBALS['glbstg_mod_visibility_allowhiddenmodules']=0;
        $GLOBALS['glbstg_mod_availability_ignoreavailabilityrestrictions']=0;
        $GLOBALS['glbstg_mod_finalfilter_concernedmdlmodtypes']=array('resource','url','assign','book','chat','choice','data','feedback','folder','forum','glossary','imscp','label','lesson','lti','page','quiz','scorm','survey','wiki','workshop','xfgon');
        //Moodle Final_Resources(attached files): Controlling globally ALLOWED files licenses.
        $GLOBALS['glbstg_mod_fresallowedlicenses']="public|cc|cc-nd|cc-nc-nd|cc-nc|cc-nc-sa|cc-sa";

        //Load global parameters from plugin settings
        $plugin_configs = get_config('mod_xfgon');
        //Plugin general parameters
        if( property_exists($plugin_configs,'enabled') ) {
              if( (bool)$plugin_configs->enabled == true ){ $GLOBALS['glbstg_plugenabled']=1; }else{ $GLOBALS['glbstg_plugenabled']=0; };
        } else {
              $GLOBALS['glbstg_plugenabled']=0;
        }
        //Moodle Categories
        if( property_exists($plugin_configs,'oercategories') )  {
            $GLOBALS['glbstg_crs_category']=explode(',',trim($plugin_configs->oercategories,","));
        } else {
              $GLOBALS['glbstg_crs_category']=array();
        }
        //Moodle Courses
        if( property_exists($plugin_configs,'courseallowtypeswithpass') )  {
          if( (bool)$plugin_configs->courseallowtypeswithpass == true ){ $GLOBALS['glbstg_crs_allow_typeswith_pass']=1; }else{ $GLOBALS['glbstg_crs_allow_typeswith_pass']=0; };
        } else {
              $GLOBALS['glbstg_crs_allow_typeswith_pass']=0;
        }
        if( property_exists($plugin_configs,'courseallowedenroltypes') )  {
          $GLOBALS['glbstg_crs_allowed_enrolment_types']=explode(',',trim($plugin_configs->courseallowedenroltypes,","));
        } else {
              $GLOBALS['glbstg_crs_allowed_enrolment_types']=array('guest');
        }
        if( property_exists($plugin_configs,'courseallowhidden') )  {
          if( (bool)$plugin_configs->courseallowhidden == true ){ $GLOBALS['glbstg_crs_allowhidden_courses']=1; }else{ $GLOBALS['glbstg_crs_allowhidden_courses']=0; };
        } else {
              $GLOBALS['glbstg_crs_allowhidden_courses']=0;
        }
        //Moodle Modules
        if( property_exists($plugin_configs,'moduleallowhidden') )  {
          if( (bool)$plugin_configs->moduleallowhidden == true ){ $GLOBALS['glbstg_mod_visibility_allowhiddenmodules']=1; }else{ $GLOBALS['glbstg_mod_visibility_allowhiddenmodules']=0; };
        } else {
              $GLOBALS['glbstg_mod_visibility_allowhiddenmodules']=0;
        }
        if( property_exists($plugin_configs,'moduleignoreavailrestric') )  {
          if( (bool)$plugin_configs->moduleignoreavailrestric == true ){ $GLOBALS['glbstg_mod_availability_ignoreavailabilityrestrictions']=1; }else{ $GLOBALS['glbstg_mod_availability_ignoreavailabilityrestrictions']=0; };
        } else {
              $GLOBALS['glbstg_mod_availability_ignoreavailabilityrestrictions']=0;
        }
        if( property_exists($plugin_configs,'moduleconsideredmoduletypes') )  {
          $GLOBALS['glbstg_mod_finalfilter_concernedmdlmodtypes']=explode(',',trim($plugin_configs->moduleconsideredmoduletypes,","));
        } else {
              $GLOBALS['glbstg_mod_finalfilter_concernedmdlmodtypes']=array('resource','url','assign','book','chat','choice','data','feedback','folder','forum','glossary','imscp','label','lesson','lti','page','quiz','scorm','survey','wiki','workshop','xfgon');
        }
        //Moodle Final_Resources(attached files)
        if( property_exists($plugin_configs,'fileallowedlicenses') )  {
              $GLOBALS['glbstg_mod_fresallowedlicenses']=implode("|", array_filter( explode('|', trim(str_replace(',',"|",$plugin_configs->fileallowedlicenses), "|") )) );
        } else {
              $GLOBALS['glbstg_mod_fresallowedlicenses']="public|cc|cc-nd|cc-nc-nd|cc-nc|cc-nc-sa|cc-sa";
        }

        //Moodle GLOBAL VARIABLES
        global $CFG;
        $GLOBALS['GLBMDL_CFG']= $CFG;
        global $DB;
        $GLOBALS['GLBMDL_DB']= $DB;

    /**
        Function to validate plugin accessibility
    */
    function validate_plugin_accessibility(){

          $final_response=new stdClass();
          if( $GLOBALS['glbstg_plugenabled']==0 or $GLOBALS['glbstg_plugenabled']=="0" or $GLOBALS['glbstg_plugenabled']==""){

                $final_response->response_notice="X5GON plugin is not enabled. Check with admin.";
          }else{
                $final_response->response_notice=1;
          }

          return $final_response;
    }

    /**
        Function to fetch all course infos:
          general infos
          category infos
          modules/resources infos
          files infos

    */
    function get_course_infos($courseid, $DB){


         $final_course_infos=new stdClass();

          $course_allowedfields='id,category,fullname,shortname,summary,format,lang,timecreated,timemodified,courseurl';
          $course_allowedfieldsarray=explode(',',$course_allowedfields);
          $category_allowedfields='id,name,description,parent,coursecount,depth,path';
          $category_allowedfieldsarray=explode(',',$category_allowedfields);
          $module_allowedfields='id,course,module,instance,section,added,score,modname,moduleurl';
          $module_allowedfieldsarray=explode(',',$module_allowedfields);
          $moduleinstance_allowedfields='id,course,name,intro,introformat';
          $moduleinstance_allowedfieldsarray=explode(',',$moduleinstance_allowedfields);
          $context_allowedfields='id,contextlevel,instanceid,path,depth';
          $context_allowedfieldsarray=explode(',',$context_allowedfields);
          $file_allowedfields='id,contextid,component,filearea,filepath,filename,filesize,mimetype,source,author,license,timecreated,timemodified,sortorder,referencefileid,fileurl';
          $file_allowedfieldsarray=explode(',',$file_allowedfields);
          $licence_allowedfields='id,shortname,fullname,source,enabled,version';
          $licence_allowedfieldsarray=explode(',',$licence_allowedfields);

          $course_mods = get_course_mods($courseid);
          $final_course_infos->course_gen_infos= $DB->get_record('course', array('id' =>$courseid));
          //Addition of 'CourseURl' as attribute
          $final_course_infos->course_gen_infos->courseurl= $GLOBALS['GLBMDL_CFG']->wwwroot."/course/view.php?id=".$final_course_infos->course_gen_infos->id;
          $final_course_infos->course_cat_infos= $DB->get_record('course_categories', array('id' =>$final_course_infos->course_gen_infos->category ));


          $result = array();
          if($course_mods) {
              foreach($course_mods as $course_mod) {

                  //Addition of 'ModuleURl' as attribute
                  $course_mod->moduleurl= $GLOBALS['GLBMDL_CFG']->wwwroot."/mod/".$course_mod->modname."/view.php?id=".$course_mod->id;
                  $course_mod->course_module_instance = $DB->get_record($course_mod->modname, array('id' =>$course_mod->instance ));
                  $course_mod->course_module_context = $DB->get_record('context', array('instanceid' =>$course_mod->id, 'contextlevel' => 70 ));


                  $course_mod->course_module_file = $DB->get_record('files', array('contextid' =>$course_mod->course_module_context->id, 'sortorder' => 1));
                  $course_mod->course_module_file_licence= null;
                  if($course_mod->course_module_file){

                      $course_mod->course_module_file_licence = $DB->get_record('license', array('shortname' =>$course_mod->course_module_file->license));
                      //Addition of 'FileURl' as attribute
                      $course_mod->course_module_file->fileurl= $GLOBALS['GLBMDL_CFG']->wwwroot."/pluginfile.php/".$course_mod->course_module_context->id."/".$course_mod->course_module_file->component."/".$course_mod->course_module_file->filearea."/".$course_mod->course_module_file->itemid.$course_mod->course_module_file->filepath.$course_mod->course_module_file->filename;

                  }
                  //$course_mod=module_filesandrelatedlicences($DB, $course_mod, array('content'));
                  $result[$course_mod->id] = $course_mod;

              }
          }
          $final_course_infos->course_modules=$result;
          $final_course_infos->response_notice="Response success";

          //Return only the needed infos: allowed infos to the frontEnd side.
          $finalfinal_course_infos=new stdClass();
          $finalfinal_course_infos->course_gen_infos=json_encode( clean_object($final_course_infos->course_gen_infos, $course_allowedfieldsarray));
          $finalfinal_course_infos->course_cat_infos=json_encode( clean_object($final_course_infos->course_cat_infos, $category_allowedfieldsarray));
          $finalfinal_course_infos->course_modules=null;
          foreach ($final_course_infos->course_modules as $key => $value) {

                $finalcoursemodule =new stdClass();
                $finalcoursemodule->course_module_geninfos=clean_object($value, $module_allowedfieldsarray);
                $finalcoursemodule->course_module_instance=clean_object($value->course_module_instance, $moduleinstance_allowedfieldsarray);
                $finalcoursemodule->course_module_context=clean_object($value->course_module_context, $context_allowedfieldsarray);
                $finalcoursemodule->course_module_file=clean_object($value->course_module_file, $file_allowedfieldsarray);
                $finalcoursemodule->course_module_file_licence=clean_object($value->course_module_file_licence, $licence_allowedfieldsarray);

                $finalfinal_course_infos->course_modules["$key"]=$finalcoursemodule;
                $finalcoursemodule=null;
          }


          $finalfinal_course_infos->course_modules=  json_encode( $finalfinal_course_infos->course_modules);

          return $final_course_infos;
    }

    /**
        Function to fetch all related 'files= most compact resources' of a module
    */
    function module_filesandrelatedlicences($DB, $course_mod, $allowed_fileareas=array('content')){

            //If decided to get only the default: just get the first record: change to get_record()
            $course_module_files = $DB->get_records('files', array('contextid' =>$course_mod->course_module_context->id));

            $course_mod->course_module_files=new stdClass();
            $course_mod->course_module_file_licences= new stdClass();
            foreach ($course_module_files as $key => $value) {

                    if(   in_array($value->filearea, $allowed_fileareas) ){

                       if($value->author!="" and $value->license!="" and $value->author!="null" and $value->license!="null" and $value->author!=null and $value->license!=null){

                             $course_mod->course_module_files["$value->id"]=$value;
                             $course_mod->course_module_file_licences["$value->id"]= $DB->get_record('license', array('shortname' =>$value->license));
                       }

                    }
            }


            return $course_mod;
            // Treatment of "file/licence" object is needed to be reverified
    }


    /**
        Function to validate if enrolment course type allows to fetch infos about it:

    */
    function validate_course_enrolment($courseid, $DB, $allowed_enrolment_types, $allow_typeswith_pass){

          $allow_fetch_courseinfos=0;
          $course_enrolments= $DB->get_records('enrol', array("courseid"=>$courseid,"status"=>0));
          foreach ($course_enrolments as $key => $value) {

              if(  in_array($value->enrol, $allowed_enrolment_types) ){

                    if( $value->password !=null and $value->password !='null' and $value->password !='' ){
                        if( $allow_typeswith_pass==1 ){
                            $allow_fetch_courseinfos=1;
                        }
                    }else{
                        $allow_fetch_courseinfos=1;
                    }

              }

          }

          return $allow_fetch_courseinfos;

    }

    /**
        Function to validate course visibility:

    */
    function validate_course_visibility($courseid, $DB, $allowhidden_courses){

          $allow_fetch_courseinfos=0;
          $course_infos= $DB->get_record('course', array("id"=>$courseid));

          if($allowhidden_courses == 1){

                $allow_fetch_courseinfos=1;

          }else {

                if(  $course_infos->visible == 1 ){

                    $allow_fetch_courseinfos=1;

                }

          }
          return $allow_fetch_courseinfos;

    }


    /**
        Function to fetch all course infos: Clean Object Rendered

    */
    function get_course_infos_clean( $courseid ){

          $DB=$GLOBALS['GLBMDL_DB'];
          return cleanresponse_course_infos( get_course_infos($courseid, $DB) );

    }


    /**
        Function to clean up 'courseInfos response' for x5Gon request:

    */
    function cleanresponse_course_infos($courseinfos_response){

          $treated_object=new stdClass();

          $treated_object->course_gen_infos=clean_object_forX5GON($courseinfos_response->course_gen_infos, 'courseinfos');

          $treated_object->course_cat_infos=clean_object_forX5GON($courseinfos_response->course_cat_infos, 'categoryinfos');


          foreach ($courseinfos_response->course_modules as $key => $value) {

                $treated_object->course_modules["$key"] =new stdClass();
                $treated_object->course_modules["$key"] =  cleanresponse_resource_infos($value) ;

          }

          if( property_exists($courseinfos_response,'response_notice') ){
              $treated_object->response_notice=$courseinfos_response->response_notice;
              //This is to be sure to not render anyinfos if it's not permitted
              if($courseinfos_response->response_notice=="No permitted informations to be rendered for this course. Check with admin."){
                  $treated_object=new stdClass();
                  $treated_object->response_notice=$courseinfos_response->response_notice;
              }
          }
          return $treated_object;

    }

    /**
        Function to clean fetched infos from DB: render only the permitted infos

    */
    function clean_object($orig_object, $desired_propreties){

        $treated_object=null;
        if($orig_object){
            $treated_object =new stdClass();
            foreach ($orig_object as $key => $value)
            {

                if( in_array($key, $desired_propreties) ){

                    $treated_object->$key = addslashes($value);
                }
            }
        }
        return $treated_object;
    }


    /**
        Function to clean fetched infos from FinalObjects: render only the permitted infos for X5GON Requests

    */
    function clean_object_forX5GON($orig_object, $target_object){

        $course_allowedfields='id,fullname,shortname,summary,lang,timecreated,timemodified,courseurl';
        $course_allowedfieldsarray=explode(',',$course_allowedfields);
        $category_allowedfields='id,name,description,parent,coursecount';
        $category_allowedfieldsarray=explode(',',$category_allowedfields);
        $module_allowedfields='id,course,added,modname,moduleurl';
        $module_allowedfieldsarray=explode(',',$module_allowedfields);
        $moduleinstance_allowedfields='name,timemodified';
        $moduleinstance_allowedfieldsarray=explode(',',$moduleinstance_allowedfields);
        $context_allowedfields='contextlevel';
        $context_allowedfieldsarray=explode(',',$context_allowedfields);
        $file_allowedfields='filename,filesize,mimetype,author,license,timecreated,timemodified,fileurl';
        $file_allowedfieldsarray=explode(',',$file_allowedfields);
        $licence_allowedfields='shortname,fullname,source,version';
        $licence_allowedfieldsarray=explode(',',$licence_allowedfields);

        $treated_object =new stdClass();

        switch ($target_object) {
          case 'courseinfos':
            $treated_object = clean_object($orig_object, $course_allowedfieldsarray);
            break;

          case 'categoryinfos':
            $treated_object = clean_object($orig_object, $category_allowedfieldsarray);
            break;

          case 'moduleinfos':
            $treated_object = clean_object($orig_object, $module_allowedfieldsarray);
            break;

          case 'moduleinstanceinfos':
            $treated_object = clean_object($orig_object, $moduleinstance_allowedfieldsarray);
            break;

          case 'contextinfos':
            $treated_object = clean_object($orig_object, $context_allowedfieldsarray);
            break;

          case 'fileinfos':
            $treated_object = clean_object($orig_object, $file_allowedfieldsarray);
            break;

          case 'licenceinfos':
            $treated_object = clean_object($orig_object, $licence_allowedfieldsarray);
            break;

          default:
            break;
        }
        return $treated_object;
    }

    /**
        Function to check module/resource visibility & availability

    */
    function check_module_visibilityANDavailability($DB, $module_infos, $visibility_allowhiddenmodules, $availability_ignoreavailabilityrestrictions){

          //Controlled in Moodle Gui: under 'availability (under "Common module settings")'
          $module_restrictions= new stdClass();
          try {

                $module_section_infos= $DB->get_record('course_sections', array('id' =>$module_infos->section));
                $module_section_availability = $module_section_infos->availability;

                //Get all module/section availability restrictions in array format: including those on complexe set of restrictions.
                $module_section_access_restrictions= get_availability_restelements_fromavfield( $module_section_availability);
                $module_access_restrictions= get_availability_restelements_fromavfield( $module_infos->availability );

                //Module visisbility: "module visibility" AND "section visibility"
                $module_restrictions->mod_visibility = $module_infos->visible;
                $module_restrictions->mod_accrestrictions = $module_access_restrictions;
                $module_restrictions->mod_section_accrestrictions = $module_section_access_restrictions;


            } catch (\Exception $e) {
                //$module_infos->course_gen_infos = 'Resource not existant. Verify your request or contact Univ-Nantes Admin.';
                return $e;
                echo "Exception occurred";
            }

            return array($module_restrictions,decide_about_modVisibilityANDAvailability( $module_restrictions, $visibility_allowhiddenmodules, $availability_ignoreavailabilityrestrictions ));

    }

    /**
        Function to check module/resource visibility & availability
        Could be used standalone

    */
    function check_module_visibilityANDavailability_standalone($moduleid){

          $DB=$GLOBALS['GLBMDL_DB'];

          //Controlled in Moodle Gui: under 'availability (under "Common module settings")'
          $module_infos=new stdClass();
          $module_restrictions= new stdClass();
          try {

                $module_infos= $DB->get_record('course_modules', array('id' =>$moduleid));
                $resource_course_infos=new stdClass();
                $resource_course_infos=get_course_infos($module_infos->course, $DB);

                $module_infos=$resource_course_infos->course_modules[$moduleid];

                $module_section_infos= $DB->get_record('course_sections', array('id' =>$module_infos->section));
                $module_section_availability = $module_section_infos->availability;

                //Get all module/section availability restrictions in array format: including those on complexe set of restrictions.
                $module_section_access_restrictions= get_availability_restelements_fromavfield( $module_section_availability);
                $module_access_restrictions= get_availability_restelements_fromavfield( $module_infos->availability );

                //Module visisbility: "module visibility" AND "section visibility"
                $module_restrictions->mod_visibility = $module_infos->visible;
                $module_restrictions->mod_accrestrictions = $module_access_restrictions;
                $module_restrictions->mod_section_accrestrictions = $module_section_access_restrictions;


            } catch (\Exception $e) {
                //$module_infos->course_gen_infos = 'Resource not existant. Verify your request or contact Univ-Nantes Admin.';
                return $e;
                echo "Exception occurred";
            }

            return array($module_restrictions,decide_about_modVisibilityANDAvailability( $module_restrictions, 0, 0 ));

    }

    /**
        Function to decide about 'module' general public aspect(depending on : visibility + availability) : FOR X5GON SPECIFITIES
    */
    function decide_about_modVisibilityANDAvailability( $mod_restrictions, $visibility_allowhiddenmodules, $availability_ignoreavailabilityrestrictions ){

            // Indeed: all those restrictions types must be reviewed and decided abou: if it could be considerd as 'public exposition' disabler or NOT.
            $decidedTobePublic=1;
            //Module visibility: is already considering 'section' visibility:
            $mod_visibility = $mod_restrictions->mod_visibility;
            $mod_accrestrictions = $mod_restrictions->mod_accrestrictions;
            $mod_section_accrestrictions = $mod_restrictions->mod_section_accrestrictions;
            if( $visibility_allowhiddenmodules==0 and ($mod_visibility==0 or $mod_visibility=='0') ){
                $decidedTobePublic=0;

            }
            $i=0;
            while ( $availability_ignoreavailabilityrestrictions==0 and $decidedTobePublic == 1 and $i<count($mod_section_accrestrictions) ) {

                      //Any of 'Availability restrictions' will be considered as 'decidedtobepublic=0'
                      if($mod_section_accrestrictions[$i]->type=='profile'){
                            $decidedTobePublic=0;
                      }
                      if($mod_section_accrestrictions[$i]->type=='date'){

                            $decidedTobePublic=0;
                      }
                      $i++;
            }
            $i=0;
            while ($availability_ignoreavailabilityrestrictions==0 and $decidedTobePublic == 1 and $i<count($mod_accrestrictions)) {

                      //Any of 'Availability restrictions' will be considered as 'decidedtobepublic=0'
                      if($mod_accrestrictions[$i]->type=='profile'){
                            $decidedTobePublic=0;
                      }
                      if($mod_accrestrictions[$i]->type=='date'){

                            $decidedTobePublic=0;

                      }
                      $i++;
            }

            return $decidedTobePublic;
    }


    /**
        Function to return recursivily a module availability restrictions: giving "availability field"

    */
    function get_availability_restelements_fromavfield( $availability_field ){

            $restrction_elmts=array();
            if($availability_field==null or $availability_field=="NULL"){
                  return $restrction_elmts;
            }else{
                foreach (json_decode($availability_field)->c as $key => $value) {
                   if( array_key_exists('c', $value) ){
                        $restrction_elmts = array_merge($restrction_elmts, get_availability_restelements_fromavfield( json_encode($value) ));
                   }else {
                        $restrction_elmts[] = $value;
                   }
                }

            }

            return $restrction_elmts;
    }

    /**
        Function to fetch all Moodle module/resource infos:
          general infos
          category + course indication
          modules/resources infos
          files infos

    */
    function get_mdlmodule_infos($moduleid){

          $DB=$GLOBALS['GLBMDL_DB'];
          $module_infos=new stdClass();
          try {

                $module_infos= $DB->get_record('course_modules', array('id' =>$moduleid));
                $resource_course_infos=new stdClass();
                $resource_course_infos=get_course_infos($module_infos->course, $DB);

                $module_infos=$resource_course_infos->course_modules[$moduleid];
                $module_infos->course_gen_infos = $resource_course_infos->course_gen_infos ;
                $module_infos->course_cat_infos = $resource_course_infos->course_cat_infos ;


            } catch (\Exception $e) {
                //$module_infos->course_gen_infos = 'Resource not existant. Verify your request or contact Univ-Nantes Admin.';
                return $e;
                echo "Exception occurred";
            }

          //Clean function to 'mustRenderedX5gonInfos' must be applied here.
          return cleanresponse_resource_infos($module_infos);

    }

    /**
        Function to fetch all Moodle module/resource infos:
          general infos
          category + course indication
          modules/resources infos
          files infos

        PS: RESPECTING X5GON OER CRITERIAS

    */
    function get_oermdlmodule_infos( $moduleid ){

          $oer_category_id=$GLOBALS['glbstg_crs_category'];
          $DB=$GLOBALS['GLBMDL_DB'];
          $module_infos=new stdClass();
          try {

                $module_infos= $DB->get_record('course_modules', array('id' =>$moduleid));
                $resource_course_infos=new stdClass();
                $resource_course_infos=get_course_infos($module_infos->course, $DB);

                //X5GON 1st OER Criteria: Category + Course Visibility + Course Availability ===> X5GON Snippet integrated
                $nopermittedmodules=0;
                if( in_array($resource_course_infos->course_cat_infos->id, $oer_category_id) and validate_course_enrolment($resource_course_infos->course_gen_infos->id, $DB, $GLOBALS['glbstg_crs_allowed_enrolment_types'], $GLOBALS['glbstg_crs_allow_typeswith_pass']) and validate_course_visibility($resource_course_infos->course_gen_infos->id, $DB, $GLOBALS['glbstg_crs_allowhidden_courses']) ){

                        $course_mod_restrictions=check_module_visibilityANDavailability($DB, $resource_course_infos->course_modules[$moduleid], $GLOBALS['glbstg_mod_visibility_allowhiddenmodules'], $GLOBALS['glbstg_mod_availability_ignoreavailabilityrestrictions']);
                        if($course_mod_restrictions[1]==1){

                              //X5GON 3rd OER Criteria: Final file license.
                              if($resource_course_infos->course_modules[$moduleid]->course_module_file){
                                  //not empty file attribute infos
                                  if( preg_match("/".$GLOBALS['glbstg_mod_fresallowedlicenses']."/", $resource_course_infos->course_modules[$moduleid]->course_module_file->license) ){

                                      $module_infos=$resource_course_infos->course_modules[$moduleid];
                                      $module_infos->course_gen_infos = $resource_course_infos->course_gen_infos ;
                                      $module_infos->course_cat_infos = $resource_course_infos->course_cat_infos ;
                                      $nopermittedmodules=1;
                                  }

                              }else{
                                  // Else: other type of moodle resources + category is "OER category(Nantes case)"
                                  $module_infos=$resource_course_infos->course_modules[$moduleid];
                                  $module_infos->course_gen_infos = $resource_course_infos->course_gen_infos ;
                                  $module_infos->course_cat_infos = $resource_course_infos->course_cat_infos ;
                                  $nopermittedmodules=1;

                              }


                      }

                      //Y1: For FinalResources: We are interested for the moment about 'type=resource' of moodle modules.
                      if ( $nopermittedmodules==1 ){
                            if(is_concerned_resource( $module_infos, $GLOBALS['glbstg_mod_finalfilter_concernedmdlmodtypes'] ) == 0){

                                  $nopermittedmodules=0;
                            }

                      }
                 }


            } catch (\Exception $e) {
                //$module_infos->course_gen_infos = 'Resource not existant. Verify your request or contact Univ-Nantes Admin.';
                //return $e;
                $module_infos->response_notice="No permitted informations to be rendered. Check with admin.";
                echo "Exception occurred";
            }
            if($nopermittedmodules==0){
                 $module_infos->response_notice="No permitted informations to be rendered. Check with admin.";
            }

          //Clean function to 'mustRenderedX5gonInfos' must be applied here.
          return cleanresponse_resource_infos($module_infos);


    }

    /**
        Function to clean up 'resourceInfos response' for x5Gon request:

    */
    function cleanresponse_resource_infos($resourceinfos_response){

          $treated_object=new stdClass();

          $resourcegengen_infos=    clean_object_forX5GON($resourceinfos_response, 'moduleinfos');
          $treated_object=$resourcegengen_infos;

          if( property_exists($resourceinfos_response,'course_module_instance') ){
              $resourceinstance_infos=  clean_object_forX5GON($resourceinfos_response->course_module_instance, 'moduleinstanceinfos');
              $treated_object->course_module_instance=$resourceinstance_infos;
          }

          if( property_exists($resourceinfos_response,'course_module_file') ){
              $resourcefile_infos=      clean_object_forX5GON($resourceinfos_response->course_module_file, 'fileinfos');
              $treated_object->course_module_file=$resourcefile_infos;
          }
          if( property_exists($resourceinfos_response,'course_module_file_licence') ){
              $resourcelicence_infos=   clean_object_forX5GON($resourceinfos_response->course_module_file_licence, 'licenceinfos');
              $treated_object->course_module_file_licence=$resourcelicence_infos;
          }
          if( property_exists($resourceinfos_response,'course_gen_infos') ){
              $resourcecourse_infos=    clean_object_forX5GON($resourceinfos_response->course_gen_infos, 'courseinfos');
              $treated_object->course_gen_infos=$resourcecourse_infos;
          }

          if( property_exists($resourceinfos_response,'course_cat_infos') ){
              $resourcecategory_infos=  clean_object_forX5GON($resourceinfos_response->course_cat_infos, 'categoryinfos');
              $treated_object->course_cat_infos=$resourcecategory_infos;
          }
          //For return status feedback
          if( property_exists($resourceinfos_response,'response_notice') ){
              $treated_object->response_notice=$resourceinfos_response->response_notice;
              //This is to be sure to not render anyinfos if it's not permitted
              if($resourceinfos_response->response_notice=="No permitted informations to be rendered. Check with admin."){
                  $treated_object=new stdClass();
                  $treated_object->response_notice=$resourceinfos_response->response_notice;
              }
          }

          return $treated_object;

    }

    /**
        Function to fetch all OERs of a specific course:
          *$courseid: course id
          *$DB : moodle db instance
          **API Limitation: "To be modified on each moodle conception"
                            * 'OER specification': Here decided on 'categoryid' & 'resourceType file license' & 'module restrictions: visisbility + availability'

    */
    function get_course_oers( $courseid, $DB, $oer_category_id ){
            $oer_category_id=$GLOBALS['glbstg_crs_category'];
            // OERs in Nantes caracterised by 'categoryid=916'

            $oer_modules=new stdClass();
            $course_infos = get_course_infos($courseid, $DB);
            $course_gen_infos= $course_infos->course_gen_infos;
            $course_cat_infos= $course_infos->course_cat_infos;
            $course_mod_infos= $course_infos->course_modules;
            $final_oer_modules =new stdClass();

            //X5GON 1st OER Criteria: Category + Course Visibility + Course Availability ===> X5GON Snippet integrated
            if( in_array($course_gen_infos->category, $oer_category_id) and validate_course_enrolment($courseid, $DB, $GLOBALS['glbstg_crs_allowed_enrolment_types'], $GLOBALS['glbstg_crs_allow_typeswith_pass']) and validate_course_visibility($courseid, $DB, $GLOBALS['glbstg_crs_allowhidden_courses']) ){

                foreach ($course_mod_infos as $key => $value) {
                        //X5GON 2nd OER Criteria: Moodle modules restrictions: Visibility + Availability
                        //Module restrictions + couldbepublic x5gon decision
                        //The next parameters are meant to be loaded from plugin general settings in the future.
                        $course_mod_restrictions=check_module_visibilityANDavailability($DB, $value, $GLOBALS['glbstg_mod_visibility_allowhiddenmodules'], $GLOBALS['glbstg_mod_availability_ignoreavailabilityrestrictions']);
                        if($course_mod_restrictions[1]==1){

                                //X5GON 3rd OER Criteria: Final file license.
                                if($value->course_module_file){
                                    //not empty file attribute infos
                                    if( preg_match("/".$GLOBALS['glbstg_mod_fresallowedlicenses']."/", $value->course_module_file->license) ){

                                        $oer_modules->$key=$value;
                                    }

                                }else{
                                    // Else: other type of moodle resources + category is "OER category(Nantes case)"
                                    $oer_modules->$key=$value;

                                }

                          }

                  }

                //Y1: For FinalResources: We are interested for the moment about 'type=resource' of moodle modules.
                $oer_modules= get_concerned_resources( $oer_modules, $GLOBALS['glbstg_mod_finalfilter_concernedmdlmodtypes'] );
                $final_oer_modules->course_modules=$oer_modules;
                $final_oer_modules->course_gen_infos= $course_gen_infos;
                $final_oer_modules->course_cat_infos= $course_cat_infos;

            }else{

                $final_oer_modules->course_gen_infos=new stdClass();
                $final_oer_modules->course_cat_infos=new stdClass();
                $final_oer_modules->course_modules=new stdClass();
                $final_oer_modules->response_notice="No permitted informations to be rendered for this course. Check with admin.";
            }

            return $final_oer_modules;
    }

    /**
        Function to fetch all OERs of a specific course: Clean object rendered

    */
    function get_course_oers_clean( $courseid ){
        $oer_category_id=$GLOBALS['glbstg_crs_category'];
        $DB=$GLOBALS['GLBMDL_DB'];
        return  cleanresponse_course_infos( get_course_oers($courseid, $DB, $oer_category_id) );

    }


    /**
        Function to fetch all OERs in moodle:
          *general infos
          *category infos
          *modules/resources infos
          *files infos
          **API Limitation: "To be modified on each moodle conception"
                            * 'OER specification': Here decided on 'categoryid' & 'resourceType file license'
    */
    function get_oers_list($DB, $oer_category_id){
        //OERs in Nantes caracterised by 'categoryid=916'
        //Returned modules organized by course
        $all_oers_resources=new stdClass();
        //Multiple choices desired
        $all_mdl_courses= $DB->get_records_list('course', 'category', $oer_category_id);
        foreach ($all_mdl_courses as $key => $value) {

              $courseid=$value->id;
              $course_oers= get_course_oers($value->id, $DB, $oer_category_id);

              if( count( (array)$course_oers->course_modules)  != 0 ){
                    $all_oers_resources->$courseid = get_course_oers($value->id, $DB, $oer_category_id);
              }


        }

        return $all_oers_resources;

    }

    /**
        Function to fetch all OERs in moodle: clean object rendered

    */
    function get_oers_list_clean(){
        $oer_category_id=$GLOBALS['glbstg_crs_category'];
        $DB=$GLOBALS['GLBMDL_DB'];

        $oers=get_oers_list($DB, $oer_category_id);
        $oersclean=new stdClass();
        foreach ($oers as $key => $value) {
            $oersclean->$key = cleanresponse_course_infos( $value );
        }
        if( count( (array)$oersclean)  == 0 ){
            $oersclean->response_notice="No permitted informations to be rendered. Check with admin.";
        }
        return $oersclean;

    }

    /**
        Function to filter which resources must be rendered: depending on their "moodle types"
          *$resources_object: resources objects to be filtered. StdClass object containing all resources to be tested (with attributes like the result of get_courses_infos())
          *$mdl_considered_resources : for the moment we treat only "downloadble moodle resources(pdf, docs, videos..)" , but not the others(activities, Qcm, choices, forums, ...)
          **API Limitation: "To be modified on each moodle conception"
                            * 'Resource type concerned': Here and for the moment only 'Resource moodle type' is concerned
    */
    function get_concerned_resources( $resources_object, $mdl_considered_resources ){

          $concerned_resources=new stdClass();

          foreach ($resources_object as $key => $value) {

                if( in_array($value->modname, $mdl_considered_resources) ){

                    $concerned_resources->$key = $value;
                }
          }

          return $concerned_resources;
    }
    /**
        Function to verify if a specific Moodle module must be rendered: depending on its 'moodle module type'
        *Specifictions of X5GON project
        *Will be extended by treating all Moodle module types.
    */
    function is_concerned_resource( $module_infos,  $mdl_considered_resources ){

          if( in_array($module_infos->modname, $mdl_considered_resources) ){

              return 1;
          }

          return 0;
    }



 ?>
