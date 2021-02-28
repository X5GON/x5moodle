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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/xfgon/locallib.php');

class mod_xfgon_mod_form extends moodleform_mod {

    protected $course = null;

    public function __construct($current, $section, $cm, $course) {
        $this->course = $course;
        parent::__construct($current, $section, $cm, $course);
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function mod_xfgon_mod_form($current, $section, $cm, $course) {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct($current, $section, $cm, $course);
    }

    function definition() {
        global $CFG, $COURSE, $DB, $OUTPUT;

        $mform    = $this->_form;

        $xfgonconfig = get_config('xfgon');

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        $mform->addElement('text', 'x5discinit', get_string('x5discinit', 'xfgon'), array('size'=>'64', 'placeholder'=>'Please specify 3 to 5 terms about your course separated by commas'));
        $mform->setType('x5discinit', PARAM_TEXT);
        $mform->addRule('x5discinit', null, 'required', null, 'client');
        $mform->addRule('x5discinit', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('x5discinit', 'x5discinit', 'xfgon');

        $mform->addElement('header', 'content', get_string('content'));
        $mform->setExpanded('content');

        $mform->addElement('hidden', 'dtx5tab');
        $mform->setType('dtx5tab', PARAM_INT);
        $mform->setDefault('dtx5tab', 1);
        $mform->addElement('advcheckbox',
                           'enablex5discovery',
                           get_string('enablex5discovery', 'xfgon'),
                           null,
                           array('group' => 1));
        $mform->setDefault('enablex5discovery', 1);
        $mform->disabledIf('enablex5discovery', 'dtx5tab', 'eq', '1');
        $mform->addHelpButton('enablex5discovery', 'enablex5discovery', 'xfgon');

        $mform->addElement('advcheckbox',
                           'enablex5recommend',
                           get_string('enablex5recommend', 'xfgon'),
                           null,
                           array('group' => 1));
        $mform->setDefault('enablex5recommend', 1);
        $mform->addHelpButton('enablex5recommend', 'enablex5recommend', 'xfgon');
        $mform->addElement('advcheckbox',
                           'enablex5playlist',
                           get_string('enablex5playlist', 'xfgon'),
                           null,
                           null
                         );
         $mform->addHelpButton('enablex5playlist', 'enablex5playlist', 'xfgon');

         $mform->addElement('html', '<div class="gx5lnplst">');
         $plstx5ln = array();
         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplstnw">');
         $plstx5ln[] = $mform->createElement('button', 'pst5lnftchnew', '<span class="x5lnplstnew"> <a href="https://x5learn.org/create_playlist" target="_blank">'.get_string("pst5lncrte", 'xfgon').'</a></span>');
         $plstx5ln[] = $mform->createElement('html', '</div>');

         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplsturl">');
         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplsturlclcr">');
         $plstx5ln[] = $mform->createElement('radio', 'pst5lnftch', '', '', 'pst5lnftchfmurl',  array());
         $plstx5ln[] = $mform->createElement('html', '</div>');

         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplsturlctcr">');
         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplsturlct">');
         $plstx5ln[] = $mform->createElement('text', 'pst5lnurl', get_string('pst5lnurl', 'xfgon'), array('size'=>'31.5', 'placeholder'=>"x5learn.org/search?q=pl:7"));
         $mform->setType('pst5lnurl', PARAM_TEXT);
         $plstx5ln[] = $mform->createElement('html', '</div>');
         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplsturlcl">');
         $plstx5ln[] = $mform->createElement('submit', 'pst5lnftchfmurl', get_string("pst5lnftch", 'xfgon'), array('size'=>'40'));
         $mform->registerNoSubmitButton("pst5lnftchfmurl");
         $plstx5ln[] = $mform->createElement('html', '</div>');
         $plstx5ln[] = $mform->createElement('html', '</div>');

         $plstx5ln[] = $mform->createElement('html', '</div>');

         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplstmbz">');
         $filemanager_options = array();
         $filemanager_options['return_types'] = 3;
         $filemanager_options['accepted_types'] = '.mbz';
         $filemanager_options['maxbytes'] = get_max_upload_file_size($CFG->maxbytes);
         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplstmbzclcr">');
         $plstx5ln[] = $mform->createElement('radio', 'pst5lnftch', '', '', 'pst5lnftchfmmbz', array());
         $plstx5ln[] = $mform->createElement('html', '</div>');

         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplstmbzctcr">');
         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplstmbzct">');
         $plstx5ln[] = $mform->createElement('filepicker',
                                             'plstln5mbzatmt',
                                             get_string('plstln5mbzatmt', 'xfgon'),
                                             null,
                                             $filemanager_options);
         $plstx5ln[] = $mform->createElement('html', '</div>');
         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplstmbzcl">');
         $plstx5ln[] = $mform->createElement('submit', 'pst5lnftchfmmbz', get_string("pst5lnftchfmmbz", 'xfgon'), array('size'=>'40'));
         $mform->registerNoSubmitButton("pst5lnftchfmmbz");
         $plstx5ln[] = $mform->createElement('html', '</div>');
         $plstx5ln[] = $mform->createElement('html', '</div>');
         $plstx5ln[] = $mform->createElement('html', '</div>');

         $plstx5ln[] = $mform->createElement('html', '<div class="gx5lnplstels">');

         if ( optional_param('update', null, PARAM_RAW) != null and optional_param('update', null, PARAM_RAW) != "0" ){

              $cm = get_coursemodule_from_id('xfgon', $this->_cm->id, 0, false, MUST_EXIST);
              $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
              $xfgon = new xfgon($DB->get_record('xfgon', array('id' => $cm->instance), '*', MUST_EXIST), $cm, $course);

              $plstfced = unserialize($xfgon->x5playlist);

         }else{
             $cm = new stdClass();
             $plstfced = new stdClass();
             $plstfced->plst = new stdClass();
             $plstfced->plst->playlist_items = array();
             $plstfced->fetch = "failure";
             $plstfced->fetchdtls = "no playlist needed to be fetched";
         }
         if ( (optional_param('pst5lnftchfmmbz', null, PARAM_RAW) != null
              or optional_param('pst5lnftchfmurl', null, PARAM_RAW) != null)
              and optional_param('submitbutton', null, PARAM_RAW) == null
            ){

                $fetch_methods = array('pst5lnftchfmmbz' => optional_param('pst5lnftchfmmbz', null, PARAM_RAW) ?? null,
                                        'pst5lnftchfmurl'=> optional_param('pst5lnftchfmurl', null, PARAM_RAW) ?? null );
                $fetch_md_used = key(array_filter($fetch_methods, function ($val, $key) { return ($val != null); }, ARRAY_FILTER_USE_BOTH ));
                $pstfetch_obj = array( 'pst5lnftchfmmbz' => optional_param('plstln5mbzatmt', null, PARAM_RAW) ?? null,
                                       'pst5lnftchfmurl'=> optional_param('pst5lnurl', null, PARAM_RAW) ?? null,
                                       'pst5lnftchmd' => $fetch_md_used );

                $plstfced = get_plst_items($pstfetch_obj);

         }

         $plstx5lnui = '';
         if ($plstfced->fetch == "success"){
             // Plst gen infos container
             $plstgeninfos = $plstfced->plst->playlist_general_infos;
             $plstx5lnui .= '<div class="plstedtgen">';
             $plstx5lnui.= '<span class="x5itemicon plstedtgenicon"><i class="fa fa-link"></i></span>';
             $plstx5lnui.= '<span class="x5itemcont plstedtgencont" data-plstid="'.$plstgeninfos->pst_id.'">';
             $plstx5lnui.= '<strong class="discimtle">'. $plstgeninfos->pst_name .'</strong></br>';
             $plstx5lnui.= '<small class="discimurl" style="display:none;"><a href="http://'.$plstgeninfos->pst_url.'" target="_blank">'. $plstgeninfos->pst_url .'</a></small>';
             $plstx5lnui.= '<small class="discimlge"><b>Author:</b>'. $plstgeninfos->pst_author .'</small></br>';
             $plstx5lnui.= '<small class="discimpr"><b>License:</b>'. $plstgeninfos->pst_license .'</small>';
             $plstx5lnui .= '</span>';
             $plstx5lnui .= '</div>';
             // Plst items container
             $plstx5lnui .= '<div class="plstedtims">';
             foreach ($plstfced->plst->playlist_items as $key => $value) {
                   $plstx5lnui.= '<span class="discim plstedtim" data-oerid="'.$value->x5gon_id.'">';
                   $plstx5lnui.= '<span class="x5itemicon plstedtimicon"><i class="fa '.get_res_awesomeicon($value->mediatype).'"></i></span>';
                   $plstx5lnui.= '<span class="x5itemcont plstedtimcont">';
                   $plstx5lnui.= '<strong class="discimtle plstedtimtle">'. $value->title .'</strong></br>';
                   $plstx5lnui.= '<small class="discimurl plstedtimurl" style="display:none;"><a href="'.$value->url.'" target="_blank">'. $value->url .'</a></small>';
                   $plstx5lnui.= '<small class="discimlge plstedtimar"><b>Author:</b>'. $value->author .'</small></br>';
                   $plstx5lnui.= '<small class="discimpr plstedtimpr"><b>Provider:</b>'. $value->provider .'</small>';
                   $plstx5lnui.= '</span>';
                   $plstx5lnui.= '</span></br>';

             }
             $plstx5lnui .= '</div>';

         }else{
           $plstx5lnui = '<div class="plstedtgen">';
           $plstx5lnui .= '</div>';
           $plstx5lnui .= '<div class="plstedtims">';
           $plstx5lnui .= '<span class="discim plstedtim plstftchdtls">'.$plstfced->fetchdtls.'</span>';
           $plstx5lnui .= '</div>';

         }
         $plstx5ln[] = $mform->createElement('html', $plstx5lnui);
         $plstx5ln[] = $mform->createElement('html', '</div>');

         $mform->addElement('group', 'pst5lngp', get_string('pst5lngp', 'xfgon'), $plstx5ln, ' ', false);
         $mform->addElement('html', '</div>');
         $mform->addHelpButton('pst5lngp', 'pst5lngp', 'xfgon');

          //-------------------------------------------------------------------------------
          $this->standard_coursemodule_elements();
          //-------------------------------------------------------------------------------
          // buttons
          $this->add_action_buttons();
    }


    public function definition_after_data() {
        global $COURSE, $DB;

        parent::definition_after_data();

    }


    /**
     * Enforce defaults here
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {

        // ideally having the same names as dataindb to set auto. defaultvalues(form auto.)
        // see the following example
        if (isset($defaultvalues['contentsettings']))
        {
            $contentsettings = unserialize($defaultvalues['contentsettings']);
            $defaultvalues['enablex5discovery'] =  $contentsettings->enablex5discovery;
            $defaultvalues['x5discinit'] =  $contentsettings->x5discoverystgs->init;
            $defaultvalues['enablex5recommend'] =  $contentsettings->enablex5recommend;
            $defaultvalues['enablex5playlist'] =   $contentsettings->enablex5playlist;
            $defaultvalues['pstit'] = $contentsettings->x5playlistitems;
            $defaultvalues['pstittle'] = $contentsettings->x5playlistitemstles;
            if (property_exists($contentsettings->x5playliststgs, "pst5lnftch")){
                $defaultvalues['pst5lnftch'] = $contentsettings->x5playliststgs->pst5lnftch;
            }else{
                $defaultvalues['pst5lnftch'] = 'pst5lnftchfmmbz';
            }
            $defaultvalues['pst5lnurl'] = $contentsettings->x5playliststgs->pst5lnurl;
            $defaultvalues['plstln5mbzatmt'] = $contentsettings->x5playliststgs->plstln5mbzatmt;
        }

    }


    /**
     * Enforce validation rules here
     *
     * @param object $data Post data to validate
     * @return array
     **/
    function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }

    /**
     * Display module-specific activity completion rules.
     * Part of the API defined by moodleform_mod
     * @return array Array of string IDs of added items, empty array if none
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $mform->addElement('checkbox', 'completionendreached', get_string('completionendreached', 'xfgon'),
                get_string('completionendreached_desc', 'xfgon'));
        // Enable this completion rule by default.
        $mform->setDefault('completionendreached', 1);

        $group = array();
        $group[] =& $mform->createElement('checkbox', 'completiontimespentenabled', '',
                get_string('completiontimespent', 'xfgon'));
        $group[] =& $mform->createElement('duration', 'completiontimespent', '', array('optional' => false));
        $mform->addGroup($group, 'completiontimespentgroup', get_string('completiontimespentgroup', 'xfgon'), array(' '), false);
        $mform->disabledIf('completiontimespent[number]', 'completiontimespentenabled', 'notchecked');
        $mform->disabledIf('completiontimespent[timeunit]', 'completiontimespentenabled', 'notchecked');

        return array('completionendreached', 'completiontimespentgroup');

    }

    /**
     * Called during validation. Indicates whether a module-specific completion rule is selected.
     *
     * @param array $data Input data (not yet validated)
     * @return bool True if one or more rules is enabled, false if none are.
     */
    public function completion_rule_enabled($data) {
      // echo "Bhim5";

        return !empty($data['completionendreached']) || $data['completiontimespent'] > 0;
    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        // Turn off completion setting if the checkbox is not ticked.
        if (!empty($data->completionunlocked)) {
            $autocompletion = !empty($data->completion) && $data->completion == COMPLETION_TRACKING_AUTOMATIC;
            if (empty($data->completiontimespentenabled) || !$autocompletion) {
                $data->completiontimespent = 0;
            }
            if (empty($data->completionendreached) || !$autocompletion) {
                $data->completionendreached = 0;
            }
        }
    }
}
